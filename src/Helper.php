<?php

namespace Optilarity\FlatsomeExtra;

class Helper
{
    static function resolveUrlFromPath($path): string
    {
        $path = str_replace("\\", "/", $path);
        $absPath = str_replace('\\', '/', ABSPATH);

        return str_replace($absPath, site_url('/'), $path);
    }

    public static function isUXBuilder(): bool
    {
        return function_exists('ux_builder_is_active') && ux_builder_is_active();
    }

    public static function isEditingOptilarityLayout(): bool
    {
        $post_id = null;
        if (isset($_GET['post'])) {
            $post_id = (int) $_GET['post'];
        } elseif (isset($_POST['post_ID'])) {
            $post_id = (int) $_POST['post_ID'];
        } elseif (isset($_REQUEST['post_id'])) {
            $post_id = (int) $_REQUEST['post_id'];
        }

        if ($post_id) {
            $post = get_post($post_id);
            return $post && $post->post_type === 'optilarity_layout';
        }

        return false;
    }
}
