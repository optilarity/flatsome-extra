<?php
/**
 * Generic Layout Renderer for Optilarity Layouts
 *
 * @package Optilarity\FlatsomeExtra
 */

get_header();

$term = get_queried_object();
$layout_id = get_term_meta($term->term_id, '_optilarity_layout_id', true);
if ($layout_id && !get_post($layout_id)) {
    delete_term_meta($term->term_id, '_optilarity_layout_id');
    $layout_id = false;
}

if (!$layout_id) {
    $layout_id = get_option('optilarity_layout_taxonomy_' . $term->taxonomy);
    if ($layout_id && !get_post($layout_id)) {
        delete_option('optilarity_layout_taxonomy_' . $term->taxonomy);
        $layout_id = false;
    }
}

if ($layout_id && ($post_object = get_post($layout_id))) {
    echo '<!-- Optilarity Taxonomy Layout ID: ' . $layout_id . ' -->';
    echo '<div id="content" class="optilarity-layout-content">';
    echo apply_filters('the_content', $post_object->post_content);
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
