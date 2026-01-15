<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

use Optilarity\FlatsomeExtra\Helper;

class ThoughtLeaderMetaShortcode extends AbstractShortcode
{
    public function getTag(): string
    {
        return 'thought_leader_meta';
    }

    public function getName(): string
    {
        return __('Thought Leader Meta', 'akselos');
    }

    public function getOptions(): array
    {
        return [
            'layout' => [
                'type' => 'select',
                'heading' => 'Layout Style',
                'default' => 'simple',
                'options' => [
                    'simple' => 'Field Only (Simple)',
                    'vertical' => 'Preset 1: Vertical (Sidebar)',
                    'boxed' => 'Preset 2: Boxed Card',
                    'mini' => 'Preset 3: Mini Header',
                ],
            ],
            'type' => [
                'type' => 'select',
                'heading' => 'Field Type (for Simple)',
                'default' => 'role',
                'conditions' => 'layout == "simple"',
                'options' => [
                    'role' => 'Role & Company',
                    'social' => 'Social Links (LinkedIn)',
                ],
            ],
            'color' => [
                'type' => 'colorpicker',
                'heading' => 'Text Color',
                'default' => '',
            ],
            'bg_color' => [
                'type' => 'colorpicker',
                'heading' => 'Background Color',
                'default' => '',
            ]
        ];
    }

    public function render($atts, $content = null): string
    {
        $atts = shortcode_atts([
            'layout' => 'simple',
            'type' => 'role',
            'color' => '',
            'bg_color' => '',
        ], $atts);

        $obj = get_queried_object();
        $term = null;
        if ($obj instanceof \WP_Term) {
            $term = $obj;
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
        }

        if (!$term || $term->taxonomy !== 'thought_leader') {
            return Helper::isUXBuilder() ? '[Thought Leader: ' . ($atts['layout'] !== 'simple' ? $atts['layout'] : $atts['type']) . ']' : '';
        }

        $position = get_term_meta($term->term_id, 'position', true);
        $company = get_term_meta($term->term_id, 'company_name', true);
        $linkedin = get_term_meta($term->term_id, 'linkedin_url', true);
        $thumbnail_id = get_term_meta($term->term_id, 'taxonomy_thumbnail_id', true) ?: get_term_meta($term->term_id, 'featured_thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : 'https://via.placeholder.com/300x300?text=No+Photo';

        ob_start();
        $style_attr = $atts['color'] ? ' style="color:' . esc_attr($atts['color']) . ';"' : '';
        $bg_style = $atts['bg_color'] ? ' style="background-color:' . esc_attr($atts['bg_color']) . ';"' : '';

        // Render based on layout
        if ($atts['layout'] === 'simple') {
            if ($atts['type'] === 'role') {
                if (!$position && !$company)
                    return '';
                echo '<div class="leader-role-meta"' . $style_attr . '>';
                echo $position ? esc_html($position) : '';
                echo ($position && $company) ? ' at ' : '';
                echo $company ? '<strong>' . esc_html($company) . '</strong>' : '';
                echo '</div>';
            } elseif ($atts['type'] === 'social' && $linkedin) {
                echo '<div class="leader-social-meta"><a href="' . esc_url($linkedin) . '" target="_blank" class="social-link linkedin"><i class="icon-linkedin" ' . $style_attr . '></i></a></div>';
            }
        } elseif ($atts['layout'] === 'vertical') {
            ?>
            <div class="tl-preset-vertical" <?php echo $bg_style; ?>>
                <div class="tl-image"><img src="<?php echo esc_url($image_url); ?>" alt=""></div>
                <div class="tl-header">
                    <h3 class="tl-name" <?php echo $style_attr; ?>><?php echo esc_html($term->name); ?></h3>
                    <?php if ($linkedin): ?>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="tl-linkedin"><i class="icon-linkedin" <?php echo $style_attr; ?>></i></a>
                    <?php endif; ?>
                </div>
                <div class="tl-role" <?php echo $style_attr; ?>>
                    <?php echo esc_html($position); ?> at <strong><?php echo esc_html($company); ?></strong>
                </div>
            </div>
            <?php
        } elseif ($atts['layout'] === 'boxed') {
            ?>
            <div class="tl-preset-boxed" <?php echo $bg_style; ?>>
                <div class="tl-avatar"><img src="<?php echo esc_url($image_url); ?>" alt=""></div>
                <div class="tl-content">
                    <h3 class="tl-name" <?php echo $style_attr; ?>><?php echo esc_html($term->name); ?></h3>
                    <div class="tl-role" <?php echo $style_attr; ?>><?php echo esc_html($position); ?> at
                        <strong><?php echo esc_html($company); ?></strong>
                    </div>
                    <?php if ($term->description): ?>
                        <div class="tl-bio" <?php echo $style_attr; ?>><?php echo wp_trim_words($term->description, 30, '...'); ?></div>
                    <?php endif; ?>
                    <div class="tl-footer">
                        <a href="<?php echo esc_url(get_term_link($term)); ?>" class="tl-link" <?php echo $style_attr; ?>>READ FULL
                            BIO</a>
                        <span class="tl-line" <?php echo $atts['color'] ? 'style="background-color:' . esc_attr($atts['color']) . ';"' : ''; ?>></span>
                    </div>
                </div>
            </div>
            <?php
        } elseif ($atts['layout'] === 'mini') {
            ?>
            <div class="tl-preset-mini" <?php echo $bg_style; ?>>
                <div class="tl-avatar"><img src="<?php echo esc_url($image_url); ?>" alt=""></div>
                <div class="tl-content">
                    <h3 class="tl-name" <?php echo $style_attr; ?>><?php echo esc_html($term->name); ?></h3>
                    <div class="tl-role" <?php echo $style_attr; ?>><?php echo esc_html($position); ?> at
                        <strong><?php echo esc_html($company); ?></strong>
                    </div>
                </div>
            </div>
            <?php
        }
        $this->render_style();
        return ob_get_clean();
    }

    protected function render_style()
    {
        static $rendered = false;
        if ($rendered)
            return;
        $rendered = true;
        ?>
        <style>
            /* Preset 1: Vertical */
            .tl-preset-vertical .tl-image img {
                width: 100%;
                height: auto;
                display: block;
                margin-bottom: 15px;
            }

            .tl-preset-vertical .tl-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 5px;
            }

            .tl-preset-vertical .tl-name {
                color: #006eb5;
                font-weight: 700;
                margin: 0;
                font-size: 22px;
            }

            .tl-preset-vertical .tl-linkedin {
                color: #333;
                font-size: 18px;
                line-height: 1;
            }

            .tl-preset-vertical .tl-role {
                font-size: 13px;
                color: #333;
                line-height: 1.4;
            }

            /* Preset 2: Boxed */
            .tl-preset-boxed {
                display: flex;
                gap: 25px;
                background: #f1f1f1;
                padding: 30px;
                align-items: flex-start;
            }

            .tl-preset-boxed .tl-avatar img {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                object-fit: cover;
            }

            .tl-preset-boxed .tl-name {
                font-size: 18px;
                font-weight: 700;
                margin: 0 0 5px;
            }

            .tl-preset-boxed .tl-role {
                font-size: 11px;
                margin-bottom: 15px;
                text-transform: none;
            }

            .tl-preset-boxed .tl-bio {
                font-size: 14px;
                line-height: 1.5;
                margin-bottom: 20px;
                color: #444;
            }

            .tl-preset-boxed .tl-footer {
                display: flex;
                align-items: center;
                gap: 15px;
                width: 100%;
            }

            .tl-preset-boxed .tl-link {
                font-weight: 800;
                font-size: 13px;
                text-decoration: underline;
                white-space: nowrap;
                color: #006eb5;
            }

            .tl-preset-boxed .tl-line {
                height: 1px;
                background: #333;
                flex-grow: 1;
                opacity: 0.8;
            }

            /* Preset 3: Mini */
            .tl-preset-mini {
                display: flex;
                gap: 15px;
                align-items: center;
            }

            .tl-preset-mini .tl-avatar img {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                object-fit: cover;
            }

            .tl-preset-mini .tl-name {
                font-size: 18px;
                font-weight: 700;
                margin: 0;
            }

            .tl-preset-mini .tl-role {
                font-size: 11px;
                color: #666;
            }

            @media (max-width: 600px) {
                .tl-preset-boxed {
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                }

                .tl-preset-boxed .tl-footer {
                    justify-content: center;
                }

                .tl-preset-boxed .tl-line {
                    display: none;
                }
            }
        </style>
        <?php
    }
}
