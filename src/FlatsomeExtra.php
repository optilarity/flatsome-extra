<?php
namespace Optilarity\FlatsomeExtra;

class FlatsomeExtra
{
    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $this->registerPostType();

        add_filter('template_include', [$this, 'templateInclude']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_bar_menu', [$this, 'addAdminBarItem'], 100);
        add_action('admin_init', [$this, 'handleLayoutEditRequest']);
    }

    protected function registerPostType()
    {
        register_post_type('optilarity_layout', [
            'labels' => [
                'name' => 'Optilarity Layouts',
                'singular_name' => 'Optilarity Layout',
            ],
            'public' => true,
            'show_ui' => true,
            'capability_type' => 'page',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'revisions', 'thumbnail'],
            'show_in_menu' => 'options-general.php',
            'exclude_from_search' => true,
            'show_in_nav_menus' => false,
            'publicly_queryable' => true,
            'show_in_rest' => true,
            'has_archive' => false,
        ]);

        // Flush rewrite rules to prevent 404 in UX Builder
        if (get_option('optilarity_layout_flushed') !== '1') {
            flush_rewrite_rules();
            update_option('optilarity_layout_flushed', '1');
        }

        // Enable UX Builder for this post type
        if (function_exists('add_ux_builder_post_type')) {
            add_ux_builder_post_type('optilarity_layout');
        }
    }

    public function enqueueAssets()
    {
        if (is_tax('thought_leader')) {
            wp_enqueue_style(
                'flatsome-extra-thought-leader',
                $this->getAssetUrl('css/optilarity-flatsome-extra.css'),
                [],
                '1.0.0'
            );
        }
    }

    protected function getAssetUrl($path)
    {
        return plugins_url('vendor/optilarity/flatsome-extra/assets/' . $path, dirname(__DIR__, 4) . '/akselos-customizer.php');
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

        // Redirect to UX Builder
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

        if (is_singular('optilarity_layout')) {
            return dirname(__DIR__) . '/templates/single-layout.php';
        }

        return $template;
    }
}
