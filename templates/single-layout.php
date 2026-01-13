<?php
/**
 * Single Layout Template for UX Builder
 *
 * @package Optilarity\FlatsomeExtra
 */

get_header();

if (have_posts()):
    while (have_posts()):
        the_post();
        echo '<div id="content" class="page-wrapper">';
        the_content();
        echo '</div>';
    endwhile;
endif;

get_footer();
