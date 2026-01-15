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
        return [
            'tag' => [
                'type' => 'select',
                'heading' => 'HTML Tag',
                'default' => 'h1',
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'p' => 'P',
                    'div' => 'Div',
                ],
            ],
            'font_size' => [
                'type' => 'textfield',
                'heading' => 'Font Size',
                'default' => '',
            ],
            'color' => [
                'type' => 'colorpicker',
                'heading' => 'Color',
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
            'tag' => 'h1',
            'font_size' => '',
            'color' => '',
            'class' => '',
        ], $atts);

        $title = 'Sample Taxonomy Title';
        if (!Helper::isUXBuilder()) {
            $obj = get_queried_object();
            if ($obj instanceof \WP_Term) {
                $title = $obj->name;
            } elseif ($obj instanceof \WP_Post) {
                $title = get_the_title($obj);
            } else {
                return '';
            }
        }

        $style = [];
        if ($atts['font_size'])
            $style[] = 'font-size:' . $atts['font_size'];
        if ($atts['color'])
            $style[] = 'color:' . $atts['color'];

        $style_attr = !empty($style) ? ' style="' . implode(';', $style) . '"' : '';
        $class_attr = $atts['class'] ? ' class="' . esc_attr($atts['class']) . '"' : '';

        return sprintf(
            '<%1$s%2$s%3$s>%4$s</%1$s>',
            $atts['tag'],
            $class_attr,
            $style_attr,
            esc_html($title)
        );
    }
}
