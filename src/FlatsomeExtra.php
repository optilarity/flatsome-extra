<?php
namespace Optilarity\FlatsomeExtra;

use Optilarity\FlatsomeExtra\Shortcodes\DataLayoutShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\QueriedObjectTitleShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\TaxonomyDescriptionShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\PostExcerptShortcode;
use Optilarity\FlatsomeExtra\Shortcodes\TaxonomyThumbnailShortcode;

use Optilarity\FlatsomeExtra\Shortcodes\PostViewsShortcode;

class FlatsomeExtra
{
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

        add_filter('template_include', [$this, 'templateInclude']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_bar_menu', [$this, 'addAdminBarItem'], 100);
        add_action('admin_init', [$this, 'handleLayoutEditRequest']);

        $this->initTaxonomyFeaturedThumbnail();

        add_action('init', [$this, 'registerShortcodes']);

        // Register page templates for layout post types
        add_action('init', [$this, 'registerTemplates'], 30);
    }

    protected function initTaxonomyFeaturedThumbnail()
    {
        $taxonomies = apply_filters(
            'optilarity_featured_thumbnail_taxonomies',
            ['category', 'post_tag', 'product_cat']
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

        if (get_option('optilarity_layout_flushed') !== '1') {
            flush_rewrite_rules();
            update_option('optilarity_layout_flushed', '1');
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
            '1.1.0'
        );

        wp_enqueue_script(
            'flatsome-extra-scripts',
            $this->getAssetUrl('js/optilarity-flatsome-extra.js'),
            [],
            '1.1.0',
            true
        );
    }

    protected function getAssetUrl($path)
    {
        return Helper::resolveUrlFromPath(constant('OPTILITY_FLATSOME_EXTRA_PATH') . '/assets/' . $path);
    }

    public function addAdminBarItem($wp_admin_bar)
    {
        if (is_admin() || !is_tax()) {
            return;
        }

        $term = get_queried_object();
        if (!$term) {
            return;
        }

        $wp_admin_bar->add_node([
            'id' => 'optilarity-edit-layout',
            'title' => 'Edit layout by Optilarity',
            'href' => admin_url('term.php?taxonomy=' . $term->taxonomy . '&tag_ID=' . $term->term_id . '&optilarity_edit=1'),
            'meta' => [
                'class' => 'optilarity-edit-layout-link',
            ],
        ]);
    }

    public function handleLayoutEditRequest()
    {
        if (!isset($_GET['optilarity_edit']) || $_GET['optilarity_edit'] !== '1') {
            return;
        }

        $term = get_queried_object();
        if (!$term && isset($_GET['tag_ID']) && isset($_GET['taxonomy'])) {
            $term = get_term($_GET['tag_ID'], $_GET['taxonomy']);
        }

        if (!$term instanceof \WP_Term) {
            return;
        }

        $layout_id = get_term_meta($term->term_id, '_optilarity_layout_id', true);

        if (!$layout_id || !get_post($layout_id)) {
            $layout_id = wp_insert_post([
                'post_title' => 'Layout for ' . $term->name . ' (' . $term->taxonomy . ')',
                'post_type' => 'optilarity_layout',
                'post_status' => 'publish',
            ]);
            update_term_meta($term->term_id, '_optilarity_layout_id', $layout_id);
        }

        $ux_builder_url = admin_url('post.php?post=' . $layout_id . '&action=edit&app=uxbuilder');
        wp_redirect($ux_builder_url);
        exit;
    }

    public function templateInclude($template)
    {
        if (is_tax()) {
            $term = get_queried_object();
            if ($term && get_term_meta($term->term_id, '_optilarity_layout_id', true)) {
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
                    return dirname(__DIR__) . '/templates/single-layout.php';
                }
            }
        }

        return $template;
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
}
