<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

use Optilarity\FlatsomeExtra\Helper;

class TaxonomyDescriptionShortcode extends AbstractShortcode
{
    public function getTag(): string
    {
        return 'taxonomy_description';
    }

    public function getName(): string
    {
        return __('Taxonomy Description', 'akselos');
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
            return 'This is a sample taxonomy description for preview purposes in UX Builder.';
        }

        $obj = get_queried_object();
        if ($obj instanceof \WP_Term) {
            return term_description($obj->term_id, $obj->taxonomy);
        }

        return '';
    }
}
