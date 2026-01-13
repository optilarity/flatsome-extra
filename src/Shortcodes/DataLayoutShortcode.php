<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

use Optilarity\FlatsomeExtra\QueryBuilder;
use Jankx\DataLayout\Renderer;

class DataLayoutShortcode extends AbstractShortcode
{
    public function getTag(): string
    {
        return 'jankx_data_layout';
    }

    public function getName(): string
    {
        return __('Jankx Data Layout', 'akselos');
    }

    public function getOptions(): array
    {
        return [
            'query_preset' => [
                'type' => 'select',
                'heading' => 'Query Preset',
                'default' => 'custom',
                'options' => [
                    'custom' => 'Custom Query',
                    'default' => 'Default Query (Global)',
                ],
            ],
            'post_type' => [
                'conditions' => 'query_preset == "custom"',
                'type' => 'select',
                'heading' => 'Post Type',
                'default' => 'post',
                'options' => $this->getPostTypes(),
            ],
            'posts_per_page' => [
                'conditions' => 'query_preset == "custom"',
                'type' => 'slider',
                'heading' => 'Posts Per Page',
                'default' => '10',
                'max' => '100',
                'min' => '1',
            ],
            'layout' => [
                'type' => 'select',
                'heading' => 'Wrapper Layout',
                'default' => 'grid',
                'options' => [
                    'grid' => 'Grid',
                    'list' => 'List',
                    'carousel' => 'Carousel',
                    'masonry' => 'Masonry',
                    'horizontal' => 'Horizontal',
                ],
            ],
            'columns' => [
                'type' => 'slider',
                'heading' => 'Columns',
                'default' => '4',
                'max' => '12',
                'min' => '1',
                'conditions' => 'layout != "list"',
                'responsive' => true,
            ],
            'content_layout' => [
                'type' => 'select',
                'heading' => 'Content Layout',
                'default' => 'card',
                'options' => [
                    'card' => 'Card',
                    'simple' => 'Simple',
                    'akselos-card' => 'Akselos Modern Card',
                    'native' => 'External Preset (WooCommerce/Flatsome)',
                ],
            ],
            'template_slug' => [
                'conditions' => 'content_layout == "native"',
                'type' => 'select',
                'heading' => 'External Preset Type',
                'default' => 'woocommerce',
                'options' => [
                    'woocommerce' => 'WooCommerce Product Loop',
                    'template-parts/posts/content' => 'Flatsome Post Content (Default)',
                    'template-parts/posts/content-card' => 'Flatsome Post Content Card',
                    'custom' => 'Custom Template Path',
                ],
            ],
            'custom_template_path' => [
                'conditions' => 'template_slug == "custom"',
                'type' => 'textfield',
                'heading' => 'Custom Slug',
                'default' => '',
            ],
            'style_options' => [
                'type' => 'group',
                'heading' => 'Content Style',
                'conditions' => 'content_layout != "native"',
                'options' => [
                    'show_thumb' => [
                        'type' => 'checkbox',
                        'heading' => 'Show Thumbnail',
                        'default' => 'true',
                    ],
                    'thumb_pos' => [
                        'type' => 'select',
                        'heading' => 'Thumbnail Position',
                        'default' => 'top',
                        'options' => [
                            'top' => 'Top',
                            'left' => 'Left',
                            'right' => 'Right',
                        ],
                    ],
                    'show_title' => [
                        'type' => 'checkbox',
                        'heading' => 'Show Title',
                        'default' => 'true',
                    ],
                    'show_excerpt' => [
                        'type' => 'checkbox',
                        'heading' => 'Show Excerpt',
                        'default' => 'true',
                    ],
                    'excerpt_length' => [
                        'type' => 'slider',
                        'heading' => 'Excerpt Length',
                        'default' => '20',
                        'max' => '100',
                        'min' => '5',
                    ],
                    'show_date' => [
                        'type' => 'checkbox',
                        'heading' => 'Show Date',
                        'default' => 'false',
                    ],
                    'show_author' => [
                        'type' => 'checkbox',
                        'heading' => 'Show Author',
                        'default' => 'false',
                    ],
                    'show_category' => [
                        'type' => 'checkbox',
                        'heading' => 'Show Category',
                        'default' => 'false',
                    ],
                    'show_taxonomy' => [
                        'type' => 'checkbox',
                        'heading' => 'Show Taxonomy',
                        'default' => 'false',
                    ],
                    'taxonomy_name' => [
                        'type' => 'textfield',
                        'heading' => 'Taxonomy Name',
                        'default' => 'category',
                    ],
                    'show_tags' => [
                        'type' => 'checkbox',
                        'heading' => 'Show Tags',
                        'default' => 'false',
                    ],
                ],
            ],
        ];
    }

    public function registerUXBuilderElement()
    {
        if (function_exists('add_ux_builder_shortcode')) {
            add_ux_builder_shortcode($this->getTag(), [
                'name' => $this->getName(),
                'category' => $this->getCategory(),
                'thumbnail' => function_exists('flatsome_ux_builder_thumbnail') ? flatsome_ux_builder_thumbnail('post-list') : '',
                'info' => '{{ layout }} - {{ content_layout }}',
                'presets' => [
                    [
                        'name' => __('Default Grid', 'akselos'),
                        'content' => '[' . $this->getTag() . ' layout="grid" content_layout="card"]',
                    ],
                    [
                        'name' => __('Akselos Modern List', 'akselos'),
                        'content' => '[' . $this->getTag() . ' layout="list" content_layout="akselos-card"]',
                    ],
                ],
                'options' => $this->getOptions(),
            ]);
        }
    }

    protected function getPostTypes()
    {
        $post_types = get_post_types(['public' => true], 'objects');
        $options = [];
        foreach ($post_types as $post_type) {
            $options[$post_type->name] = $post_type->label;
        }
        return $options;
    }

    public function render($atts, $content = null): string
    {
        $atts = shortcode_atts([
            'query_preset' => 'custom',
            'post_type' => 'post',
            'posts_per_page' => 10,
            'layout' => 'grid',
            'columns' => 4,
            'columns__md' => 3,
            'columns__sm' => 1,
            'content_layout' => 'card',
            'show_thumb' => 'true',
            'thumb_pos' => 'top',
            'show_title' => 'true',
            'show_excerpt' => 'true',
            'excerpt_length' => 20,
            'show_date' => 'false',
            'show_author' => 'false',
            'show_category' => 'false',
            'show_taxonomy' => 'false',
            'taxonomy_name' => 'category',
            'show_tags' => 'false',
            'template_slug' => 'woocommerce',
            'custom_template_path' => '',
        ], $atts, $this->getTag());

        foreach ($atts as $key => $value) {
            if ($value === 'true')
                $atts[$key] = true;
            if ($value === 'false')
                $atts[$key] = false;
        }

        // Handle native template mapping
        if ($atts['content_layout'] === 'native') {
            if ($atts['template_slug'] === 'custom') {
                $atts['template_slug'] = $atts['custom_template_path'];
            }
        }

        $query = QueryBuilder::build($atts);

        return Renderer::render($query, $atts['layout'], $atts['content_layout'], $atts);
    }
}
