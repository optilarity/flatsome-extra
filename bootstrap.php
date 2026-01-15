<?php
if (!defined('ABSPATH')) {
    exit;
}
define('OPTILITY_FLATSOME_EXTRA_PATH', __DIR__);

class Optilarity_Flatsome_Extra_Bootstrapper
{
    public function __construct()
    {
        if (did_action('plugins_loaded')) {
            $this->boot();
        } else {
            add_action('plugins_loaded', [$this, 'boot']);
        }
    }

    public function boot()
    {
        if (class_exists(\Optilarity\FlatsomeExtra\FlatsomeExtra::class)) {
            \Optilarity\FlatsomeExtra\FlatsomeExtra::getInstance();
        }
    }
}

$bootstraper = new Optilarity_Flatsome_Extra_Bootstrapper();
