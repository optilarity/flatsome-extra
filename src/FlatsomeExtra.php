<?php
namespace Optilarity\FlatsomeExtra;

use Optilarity\FlatsomeExtra\Shortcodes\DataLayoutShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\QueriedObjectTitleShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\TaxonomyDescriptionShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\PostExcerptShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\TaxonomyThumbnailShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\ThoughtLeaderMetaShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\PostViewsShortcode;

class FlatsomeExtra
{
    const VERSION = '1.1.1';

    protected static $instance;
    public $original_query_posts = null;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        // Boot Jankx Data Layout
        if (class_exists(\Jankx\DataLayout\Loader::class)) {
            \Jankx\DataLayout\Loader::boot();
        }

        add_action('init', [$this, 'registerPostType']);

        add_filter('template_include', [$this, 'templateInclude'], 99);
        add_filter('the_posts', [$this, 'overrideMainQueryWithLayout'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('ux_builder_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_bar_menu', [$this, 'addAdminBarItem'], 100);
        add_action('admin_init', [$this, 'handleLayoutEditRequest']);
        add_action('delete_post', [$this, 'cleanupLayoutReferences']);
        add_filter('ux_builder_data', [$this, 'filterUxBuilderData'], 999);

        $this->initTaxonomyFeaturedThumbnail();

        add_action('init', [$this, 'registerShortcodes']);

        // Register page templates for layout post types
        add_action('init', [$this, 'registerTemplates'], 30);
    }

    protected function initTaxonomyFeaturedThumbnail()
    {
        $taxonomies = apply_filters(
            'optilarity_featured_thumbnail_taxonomies',
            ['category', 'post_tag', 'product_cat', 'thought_leader']
        );
        TaxonomyFeaturedThumbnail::getInstance()->register($taxonomies);
    }

    public function registerShortcodes()
    {
        $shortcodes = [
            DataLayoutShortcode::class,
            QueriedObjectTitleShortcode::class,
            TaxonomyDescriptionShortcode::class,
            PostExcerptShortcode::class,
            TaxonomyThumbnailShortcode::class,
            ThoughtLeaderMetaShortcode::class,
            PostViewsShortcode::class,
        ];

        foreach ($shortcodes as $shortcodeClass) {
            $shortcode = new $shortcodeClass();
            $shortcode->register();
        }

        // Ensure UX Builder elements are registered correctly
        add_action('ux_builder_setup', [$this, 'registerUXBuilderElements']);
    }

    public function registerUXBuilderElements()
    {
        if (function_exists('add_ux_builder_shortcode')) {
            // The elements are already registered via the Shortcode classes,
            // but we can add more system-wide registration here if needed.
        }
    }

    public function getLayoutPostTypes()
    {
        return apply_filters('optilarity/page_template/post_types', ['optilarity_layout']);
    }

    public function registerPostType()
    {
        $post_types = $this->getLayoutPostTypes();

        foreach ($post_types as $post_type) {
            if ($post_type === 'optilarity_layout') {
                register_post_type('optilarity_layout', [
                    'labels' => [
                        'name' => 'Optilarity Layouts',
                        'singular_name' => 'Optilarity Layout',
                    ],
                    'public' => true,
                    'show_ui' => true,
                    'capability_type' => 'page',
                    'hierarchical' => false,
                    'supports' => ['title', 'editor', 'revisions', 'thumbnail', 'page-attributes'],
                    'show_in_menu' => 'options-general.php',
                    'exclude_from_search' => true,
                    'show_in_nav_menus' => false,
                    'publicly_queryable' => true,
                    'show_in_rest' => true,
                    'has_archive' => false,
                    'rewrite' => [
                        'slug' => 'optilarity-layout',
                        'with_front' => false,
                    ]
                ]);
            } else {
                add_post_type_support($post_type, 'page-attributes');
            }
        }

        if (get_option('optilarity_layout_flushed') !== '2') {
            flush_rewrite_rules();
            update_option('optilarity_layout_flushed', '2');
        }

        if (function_exists('add_ux_builder_post_type')) {
            foreach ($post_types as $post_type) {
                add_ux_builder_post_type($post_type);
            }
        }
    }

    public function enqueueAssets()
    {
        wp_enqueue_style(
            'flatsome-extra-styles',
            $this->getAssetUrl('css/optilarity-flatsome-extra.css'),
            ['dashicons'],
            static::VERSION
        );

        wp_enqueue_script(
            'flatsome-extra-scripts',
            $this->getAssetUrl('js/optilarity-flatsome-extra.js'),
            [],
            static::VERSION,
            true
        );
    }

    protected function getAssetUrl($path)
    {
        return Helper::resolveUrlFromPath(constant('OPTILITY_FLATSOME_EXTRA_PATH') . '/assets/' . $path);
    }

    public function addAdminBarItem($wp_admin_bar)
    {
        if (is_admin() || !(is_category() || is_tag() || is_tax())) {
            return;
        }

        $term = get_queried_object();
        if (!$term || !isset($term->taxonomy)) {
            return;
        }

        $term_layout_id = get_term_meta($term->term_id, '_optilarity_layout_id', true);
        if ($term_layout_id && !get_post($term_layout_id)) {
            delete_term_meta($term->term_id, '_optilarity_layout_id');
            $term_layout_id = false;
        }

        if ($term_layout_id) {
            $wp_admin_bar->add_node([
                'id' => 'optilarity-edit-layout',
                'title' => 'Edit Layout',
                'href' => admin_url('post.php?post=' . $term_layout_id . '&action=edit&app=uxbuilder'),
                'meta' => [
                    'class' => 'optilarity-edit-layout-link',
                ],
            ]);

            $wp_admin_bar->add_node([
                'id' => 'optilarity-edit-taxonomy-layout',
                'parent' => 'optilarity-edit-layout',
                'title' => 'Edit Taxonomy Layout',
                'href' => admin_url('term.php?taxonomy=' . $term->taxonomy . '&optilarity_edit=1'),
                'meta' => [
                    'class' => 'optilarity-edit-taxonomy-layout-link',
                ],
            ]);
        } else {
            $taxonomy_layout_id = get_option('optilarity_layout_taxonomy_' . $term->taxonomy);
            if ($taxonomy_layout_id && !get_post($taxonomy_layout_id)) {
                delete_option('optilarity_layout_taxonomy_' . $term->taxonomy);
            }

            $wp_admin_bar->add_node([
                'id' => 'optilarity-edit-layout',
                'title' => 'Edit Layout',
                'href' => admin_url('term.php?taxonomy=' . $term->taxonomy . '&optilarity_edit=1'),
                'meta' => [
                    'class' => 'optilarity-edit-layout-link',
                ],
            ]);

            $wp_admin_bar->add_node([
                'id' => 'optilarity-edit-term-layout',
                'parent' => 'optilarity-edit-layout',
                'title' => 'Edit Term Layout (' . $term->name . ')',
                'href' => admin_url('term.php?taxonomy=' . $term->taxonomy . '&term_id=' . $term->term_id . '&optilarity_edit=1'),
                'meta' => [
                    'class' => 'optilarity-edit-term-layout-link',
                ],
            ]);
        }
    }

    public function handleLayoutEditRequest()
    {
        if (!isset($_GET['optilarity_edit']) || $_GET['optilarity_edit'] !== '1') {
            return;
        }

        if (isset($_GET['taxonomy'])) {
            $taxonomy = $_GET['taxonomy'];
        } else {
            return;
        }

        if (isset($_GET['term_id'])) {
            $term_id = (int) $_GET['term_id'];
            $layout_id = get_term_meta($term_id, '_optilarity_layout_id', true);

            if (!$layout_id || !get_post($layout_id)) {
                $term = get_term($term_id);
                $layout_id = wp_insert_post([
                    'post_title' => 'Layout for Term: ' . $term->name,
                    'post_type' => 'optilarity_layout',
                    'post_status' => 'publish',
                ]);
                update_term_meta($term_id, '_optilarity_layout_id', $layout_id);
            }
        } else {
            $layout_id = get_option('optilarity_layout_taxonomy_' . $taxonomy);

            if (!$layout_id || !get_post($layout_id)) {
                $layout_id = wp_insert_post([
                    'post_title' => 'Layout for ' . $taxonomy,
                    'post_type' => 'optilarity_layout',
                    'post_status' => 'publish',
                ]);
                update_option('optilarity_layout_taxonomy_' . $taxonomy, $layout_id);
            }
        }

        $ux_builder_url = admin_url('post.php?post=' . $layout_id . '&action=edit&app=uxbuilder');
        wp_redirect($ux_builder_url);
        exit;
    }

    public function cleanupLayoutReferences($post_id)
    {
        if (get_post_type($post_id) !== 'optilarity_layout') {
            return;
        }

        // Clean up taxonomy level layouts
        global $wpdb;
        $taxonomy_options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'optilarity_layout_taxonomy_%'");
        foreach ($taxonomy_options as $option) {
            if (get_option($option->option_name) == $post_id) {
                delete_option($option->option_name);
            }
        }

        // Clean up term level layouts
        $wpdb->delete($wpdb->termmeta, [
            'meta_key' => '_optilarity_layout_id',
            'meta_value' => $post_id
        ]);
    }

    protected static $layout_cache = [];

    public function getLayoutIdForTerm($term)
    {
        if (!$term || !isset($term->taxonomy)) {
            return false;
        }

        $cache_key = $term->taxonomy . '_' . $term->term_id;
        if (isset(self::$layout_cache[$cache_key])) {
            return self::$layout_cache[$cache_key];
        }

        $layout_id = get_term_meta($term->term_id, '_optilarity_layout_id', true);
        if ($layout_id && get_post($layout_id)) {
            self::$layout_cache[$cache_key] = $layout_id;
            return $layout_id;
        }

        $taxonomy_layout_id = get_option('optilarity_layout_taxonomy_' . $term->taxonomy);
        if ($taxonomy_layout_id && get_post($taxonomy_layout_id)) {
            self::$layout_cache[$cache_key] = $taxonomy_layout_id;
            return $taxonomy_layout_id;
        }

        self::$layout_cache[$cache_key] = false;
        return false;
    }

    public function templateInclude($template)
    {
        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            $layout_id = $this->getLayoutIdForTerm($term);

            if ($layout_id) {
                $custom_template = get_post_meta($layout_id, '_wp_page_template', true);
                if ($custom_template && $custom_template !== 'default') {
                    $located = locate_template($custom_template);
                    if ($located) {
                        return $located;
                    }
                }
                return dirname(__DIR__) . '/templates/generic-layout.php';
            }
        }

        if (is_singular()) {
            $post_type = get_post_type();
            if (in_array($post_type, $this->getLayoutPostTypes())) {
                $custom_template = get_post_meta(get_the_ID(), '_wp_page_template', true);
                if ($custom_template && $custom_template !== 'default') {
                    $located = locate_template($custom_template);
                    if ($located) {
                        return $located;
                    }
                }

                if ($post_type === 'optilarity_layout') {
                    // Check if there is a single-layout.php in templates dir
                    $single_layout = dirname(__DIR__) . '/templates/single-layout.php';
                    if (file_exists($single_layout)) {
                        return $single_layout;
                    }
                }
            }
        }

        return $template;
    }

    private static $is_overriding_query = false;

    /**
     * Override the main query posts with the layout post content
     * This avoids conflicts with the default taxonomy loop.
     */
    public function overrideMainQueryWithLayout($posts, \WP_Query $query)
    {
        if (self::$is_overriding_query || !$query->is_main_query() || is_admin()) {
            return $posts;
        }

        if ($query->is_category() || $query->is_tag() || $query->is_tax()) {
            self::$is_overriding_query = true;

            $term = $query->get_queried_object();
            if (!$term || !isset($term->taxonomy)) {
                // Try manual detection if queried object is not ready
                if ($query->is_category()) {
                    $term = get_term_by('slug', $query->get('category_name'), 'category');
                } elseif ($query->is_tag()) {
                    $term = get_term_by('slug', $query->get('tag'), 'post_tag');
                } elseif ($query->is_tax()) {
                    $taxonomy = $query->get('taxonomy');
                    $term_slug = $query->get($taxonomy);
                    $term = get_term_by('slug', $term_slug, $taxonomy);
                }
            }

            $layout_id = $this->getLayoutIdForTerm($term);

            if ($layout_id) {
                $layout_post = get_post($layout_id);
                if ($layout_post) {
                    // Only save original posts once
                    if ($this->original_query_posts === null) {
                        $this->original_query_posts = $posts;
                    }

                    // Update query properties to reflect a single post
                    $query->post_count = 1;
                    $query->found_posts = 1;
                    $query->max_num_pages = 0;

                    self::$is_overriding_query = false;
                    return [$layout_post];
                }
            }
            self::$is_overriding_query = false;
        }

        return $posts;
    }

    public function registerTemplates()
    {
        $post_types = $this->getLayoutPostTypes();
        foreach ($post_types as $post_type) {
            add_filter("theme_{$post_type}_templates", [$this, 'filterPostTemplates'], 10, 2);
        }
    }

    public function filterPostTemplates($post_templates, $theme)
    {
        $page_templates = $theme->get_page_templates();
        return array_merge($post_templates, $page_templates);
    }

    public function filterUxBuilderData($data)
    {
        if (isset($data['post']['id'])) {
            $post_id = $data['post']['id'];
        } elseif (isset($_GET['post'])) {
            $post_id = $_GET['post'];
        } else {
            return $data;
        }

        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, $this->getLayoutPostTypes())) {
            return $data;
        }

        $templates = wp_get_theme()->get_page_templates($post, $post->post_type);
        $options = [];
        $options[] = ['value' => 'default', 'label' => 'Default Template'];

        foreach ($templates as $file => $name) {
            $options[] = ['value' => $file, 'label' => $name];
        }

        // Ensure meta is an array to avoid stdClass error
        if (isset($data['post']['meta']) && is_object($data['post']['meta'])) {
            $data['post']['meta'] = (array) $data['post']['meta'];
        }

        if (!isset($data['post']['meta']['options'])) {
            $data['post']['meta']['options'] = [];
        }

        // Ensure values is an array
        if (isset($data['post']['meta']['values']) && is_object($data['post']['meta']['values'])) {
            $data['post']['meta']['values'] = (array) $data['post']['meta']['values'];
        } elseif (!isset($data['post']['meta']['values'])) { // Initialize if not set
            $data['post']['meta']['values'] = [];
        }

        $current_template = get_post_meta($post->ID, '_wp_page_template', true) ?: 'default';
        $data['post']['meta']['values']['_wp_page_template'] = $current_template;

        $found = false;
        foreach ($data['post']['meta']['options'] as &$option) {
            if (isset($option['$name']) && $option['$name'] === '_wp_page_template') {
                $option['options'] = $options;
                $option['value'] = $current_template;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['post']['meta']['options'][] = [
                '$name' => '_wp_page_template',
                '$orgName' => '_wp_page_template',
                'type' => 'select',
                'heading' => 'Template',
                'description' => '',
                'default' => 'default',
                'value' => $current_template,
                'options' => $options,
                'reload' => true,
            ];
        }

        return $data;
    }
}
