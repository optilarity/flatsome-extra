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
                // If the main query has been overridden with a layout, return the original posts
                if (class_exists(\Optilarity\FlatsomeExtra\FlatsomeExtra::class)) {
                    $extra = \Optilarity\FlatsomeExtra\FlatsomeExtra::getInstance();
                    if ($extra->original_query_posts !== null) {
                        $restored_query = new \WP_Query();
                        $restored_query->query_vars = $wp_query->query_vars; // Copy all vars to avoid Undefined key warnings
                        $restored_query->posts = $extra->original_query_posts;
                        $restored_query->post_count = count($restored_query->posts);
                        $restored_query->is_archive = true;
                        $restored_query->is_tax = is_tax();
                        $restored_query->is_category = is_category();
                        $restored_query->is_tag = is_tag();
                        return $restored_query;
                    }
                }
                return $wp_query;
            }

            // In UX Builder or Admin, the global query points to the 'optilarity_layout' post.
            // We need to fetch sample data so the builder isn't empty.
            $sample_args = [
                'post_type' => $atts['post_type'] ?? 'post',
                'posts_per_page' => $atts['posts_per_page'] ?? 4,
                'post_status' => 'publish',
                'post__not_in' => [$_GET['post'] ?? get_the_ID()], // Exclude current layout post
            ];

            // Try to be smart: if we are editing a layout for a term, try to find relevant posts.
            $post_id = $_GET['post'] ?? get_the_ID();
            if ($post_id && get_post_type($post_id) === 'optilarity_layout') {
                $layout_post = get_post($post_id);
                if (preg_match('/\((.+)\)$/', $layout_post->post_title, $matches)) {
                    $taxonomy = $matches[1];
                    if (taxonomy_exists($taxonomy)) {
                        // If user hasn't override post_type, auto-detect from tax
                        if (empty($atts['post_type']) || $atts['post_type'] === 'post') {
                            $sample_args['post_type'] = static::getPostTypeByTaxonomy($taxonomy);
                        }

                        // Apply tax query to show relevant items for this taxonomy
                        $sample_args['tax_query'] = [
                            [
                                'taxonomy' => $taxonomy,
                                'field' => 'term_id',
                                'terms' => static::getSampleTermId($taxonomy),
                            ],
                        ];
                    }
                }
            }

            return new WP_Query(apply_filters('optilarity_flatsome_extra_sample_query_args', $sample_args, $atts));
        }

        $args = [
            'post_type' => $atts['post_type'] ?? 'post',
            'posts_per_page' => $atts['posts_per_page'] ?? 10,
            'post_status' => 'publish',
            'paged' => get_query_var('paged') ? get_query_var('paged') : ($atts['paged'] ?? 1),
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

    protected static function getSampleTermId(string $taxonomy): int
    {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'number' => 1,
            'orderby' => 'count',
            'order' => 'DESC',
            'hide_empty' => true,
        ]);

        if (!empty($terms) && !is_wp_error($terms)) {
            return $terms[0]->term_id;
        }

        return 0;
    }
}
