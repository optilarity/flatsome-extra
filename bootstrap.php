<?php
class Optilarity_Flatsome_Extra_Bootstrapper
{
    public function __construct()
    {
    }

    public function boot()
    {
        if (class_exists(\Optilarity\FlatsomeExtra\FlatsomeExtra::class)) {
            new \Optilarity\FlatsomeExtra\FlatsomeExtra();
        }
    }
}

$bootstraper = new Optilarity_Flatsome_Extra_Bootstrapper();
$bootstraper->boot();
