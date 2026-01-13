<?php
namespace Optilarity\FlatsomeExtra;

use WP_Query;

class QueryBuilder
{
    public static function build(array $atts): WP_Query
    {
        $preset = $atts['query_preset'] ?? 'custom';

        if ($preset === 'default') {
            global $wp_query;

            // In frontend archive pages, the global query is what we want.
            if (!is_admin() && !defined('REST_REQUEST')) {
                return $wp_query;
            }

            // In UX Builder or Admin, the global query points to the 'optilarity_layout' post.
            // We need to fetch sample data so the builder isn't empty.
            $sample_args = [
                'post_type' => $atts['post_type'] ?? 'post',
                'posts_per_page' => $atts['posts_per_page'] ?? 4,
                'post_status' => 'publish',
            ];

            // Try to be smart: if we are editing a layout for a term, try to find relevant posts.
            $post_id = $_GET['post'] ?? get_the_ID();
            if ($post_id && get_post_type($post_id) === 'optilarity_layout') {
                $layout_post = get_post($post_id);
                // If the user hasn't manually set a post_type, try to detect it from the layout title
                if (empty($atts['post_type']) || $atts['post_type'] === 'post') {
                    if (preg_match('/\((.+)\)$/', $layout_post->post_title, $matches)) {
                        $taxonomy = $matches[1];
                        if (taxonomy_exists($taxonomy)) {
                            $sample_args['post_type'] = static::getPostTypeByTaxonomy($taxonomy);
                        }
                    }
                }
            }

            return new WP_Query(apply_filters('optilarity_flatsome_extra_sample_query_args', $sample_args, $atts));
        }

        $args = [
            'post_type' => $atts['post_type'] ?? 'post',
            'posts_per_page' => $atts['posts_per_page'] ?? 10,
            'post_status' => 'publish',
        ];

        // Handle Taxonomy query if provided in atts
        if (!empty($atts['taxonomy']) && !empty($atts['term'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => $atts['taxonomy'],
                    'field' => 'slug',
                    'terms' => $atts['term'],
                ],
            ];
        }

        $args = apply_filters('optilarity_flatsome_extra_query_args', $args, $atts);

        return new WP_Query($args);
    }

    protected static function getPostTypeByTaxonomy(string $taxonomy): string
    {
        $tax = get_taxonomy($taxonomy);
        if ($tax && !empty($tax->object_type)) {
            return $tax->object_type[0];
        }
        return 'post';
    }
}
