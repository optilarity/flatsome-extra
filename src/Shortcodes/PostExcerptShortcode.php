<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

use Optilarity\FlatsomeExtra\Helper;

class PostExcerptShortcode extends AbstractShortcode
{
    public function getTag(): string
    {
        return 'post_excerpt';
    }

    public function getName(): string
    {
        return __('Post Excerpt', 'akselos');
    }

    public function getOptions(): array
    {
        return [
            'length' => [
                'type' => 'slider',
                'heading' => 'Excerpt Length',
                'default' => '20',
                'max' => '100',
                'min' => '5',
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
            'length' => 20,
        ], $atts);

        if (Helper::isUXBuilder()) {
            return 'This is a sample post excerpt for preview purposes.';
        }

        $excerpt = get_the_excerpt();
        if (!$excerpt) {
            $obj = get_queried_object();
            if ($obj instanceof \WP_Post) {
                $excerpt = $obj->post_excerpt ?: wp_trim_words($obj->post_content, $atts['length']);
            }
        }

        return $excerpt;
    }
}
