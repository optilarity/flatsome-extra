<?php
/**
 * Generic Layout Renderer for Optilarity Layouts
 *
 * @package Optilarity\FlatsomeExtra
 */

get_header();

$term = get_queried_object();
$layout_id = get_term_meta($term->term_id, '_optilarity_layout_id', true);

if ($layout_id && ($post = get_post($layout_id))) {
    echo '<div id="content" class="optilarity-layout-content">';
    echo do_shortcode($post->post_content);
    echo '</div>';
} else {
    // Fallback
    if (have_posts()):
        while (have_posts()):
            the_post();
            the_content();
        endwhile;
    endif;
}

get_footer();
