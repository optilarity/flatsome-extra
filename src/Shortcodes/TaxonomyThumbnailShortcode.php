<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

use Optilarity\FlatsomeExtra\Helper;
use Optilarity\FlatsomeExtra\TaxonomyFeaturedThumbnail;

class TaxonomyThumbnailShortcode extends AbstractShortcode
{
    public function getTag(): string
    {
        return 'taxonomy_thumbnail';
    }

    public function getName(): string
    {
        return __('Taxonomy Thumbnail', 'akselos');
    }

    public function getOptions(): array
    {
        return [
            'size' => [
                'type' => 'select',
                'heading' => 'Size',
                'default' => 'large',
                'options' => [
                    'thumbnail' => 'Thumbnail',
                    'medium' => 'Medium',
                    'large' => 'Large',
                    'full' => 'Full',
                ],
            ]
        ];
    }

    public function isLayoutOnly(): bool
    {
        return true;
    }

    public function render($atts, $content = null): string
    {
        $atts = shortcode_atts([
            'size' => 'large',
        ], $atts);

        if (Helper::isUXBuilder()) {
            return '<img src="https://via.placeholder.com/800x450?text=Taxonomy+Thumbnail" alt="Sample Thumbnail" />';
        }

        $obj = get_queried_object();
        if ($obj instanceof \WP_Term) {
            $thumbnail_url = TaxonomyFeaturedThumbnail::getThumbnailUrl($obj->term_id, $atts['size']);
            if ($thumbnail_url) {
                return '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($obj->name) . '" />';
            }
        }

        return '';
    }
}
