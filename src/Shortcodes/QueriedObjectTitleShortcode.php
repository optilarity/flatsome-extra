<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

use Optilarity\FlatsomeExtra\Helper;

class QueriedObjectTitleShortcode extends AbstractShortcode
{
    public function getTag(): string
    {
        return 'queried_object_title';
    }

    public function getName(): string
    {
        return __('Queried Object Title', 'akselos');
    }

    public function getOptions(): array
    {
        return [];
    }

    public function isLayoutOnly(): bool
    {
        return true;
    }


    public function render($atts, $content = null): string
    {
        if (Helper::isUXBuilder()) {
            return 'Sample Taxonomy Title';
        }

        $obj = get_queried_object();
        if ($obj instanceof \WP_Term) {
            return $obj->name;
        } elseif ($obj instanceof \WP_Post) {
            return get_the_title($obj);
        }

        return '';
    }
}
