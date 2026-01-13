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
}
