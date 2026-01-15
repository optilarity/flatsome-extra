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
            ],
            'width' => [
                'type' => 'textfield',
                'heading' => 'Width',
                'default' => '',
            ],
            'height' => [
                'type' => 'textfield',
                'heading' => 'Height',
                'default' => '',
            ],
            'border_radius' => [
                'type' => 'textfield',
                'heading' => 'Border Radius (%)',
                'default' => '',
            ],
            'class' => [
                'type' => 'textfield',
                'heading' => 'Custom Class',
                'default' => '',
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
            'width' => '',
            'height' => '',
            'border_radius' => '',
            'class' => '',
        ], $atts);

        if (Helper::isUXBuilder()) {
            $show_placeholder = true;
            $obj = get_queried_object();
            $term = null;
            if ($obj instanceof \WP_Term) {
                $term = $obj;
            } elseif (is_tax() || is_category() || is_tag()) {
                $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
            }

            if ($term) {
                $thumbnail_id = get_term_meta($term->term_id, 'taxonomy_thumbnail_id', true);
                if (!$thumbnail_id) {
                    $thumbnail_id = get_term_meta($term->term_id, 'featured_thumbnail_id', true);
                }
                if ($thumbnail_id) {
                    $show_placeholder = false;
                }
            }

            if ($show_placeholder) {
                $style = '';
                if ($atts['width'])
                    $style .= 'width:' . $atts['width'] . ';';
                if ($atts['height'])
                    $style .= 'height:' . $atts['height'] . ';';
                if ($atts['border_radius'])
                    $style .= 'border-radius:' . $atts['border_radius'] . '%;';
                return '<img src="https://via.placeholder.com/800x450?text=Taxonomy+Thumbnail" style="' . $style . '" alt="Sample Thumbnail" />';
            }
        }

        $obj = get_queried_object();
        $term = null;
        if ($obj instanceof \WP_Term) {
            $term = $obj;
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
        }

        if ($term) {
            $thumbnail_id = get_term_meta($term->term_id, 'taxonomy_thumbnail_id', true);
            if (!$thumbnail_id) {
                $thumbnail_id = get_term_meta($term->term_id, 'featured_thumbnail_id', true);
            }

            if ($thumbnail_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, $atts['size']);
                $style = '';
                if ($atts['width'])
                    $style .= 'width:' . $atts['width'] . ';';
                if ($atts['height'])
                    $style .= 'height:' . $atts['height'] . ';';
                if ($atts['border_radius'])
                    $style .= 'border-radius:' . $atts['border_radius'] . '%;';

                return sprintf(
                    '<img src="%s" class="%s" style="%s" alt="%s" />',
                    esc_url($thumbnail_url),
                    esc_attr($atts['class']),
                    $style,
                    esc_attr($term->name)
                );
            }
        }

        return '';
    }
}
