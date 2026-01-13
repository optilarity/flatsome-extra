<?php
class Optilarity_Flatsome_Extra_Bootstrapper
{
    public function __construct()
    {
        $this->boot();
    }

    public function boot()
    {
        if (class_exists(\Optilarity\FlatsomeExtra\Bootstrap::class)) {
            new \Optilarity\FlatsomeExtra\Bootstrap();
        }
    }
}

new Optilarity_Flatsome_Extra_Bootstrapper();
