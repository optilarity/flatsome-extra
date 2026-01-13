<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

use Jankx\SimpleStats\StatsManager;

class PostViewsShortcode extends AbstractShortcode
{
    public function getTag(): string
    {
        return 'post_views';
    }

    public function getName(): string
    {
        return __('Post Views', 'akselos');
    }

    public function getCategory(): string
    {
        return __('Akselos', 'akselos');
    }

    public function getOptions(): array
    {
        return [
            'label' => [
                'type' => 'textfield',
                'heading' => 'Label',
                'default' => 'READS',
            ],
            'color' => [
                'type' => 'color',
                'heading' => 'Color',
                'default' => '#0066cc',
            ],
            'font_size' => [
                'type' => 'slider',
                'heading' => 'Font Size',
                'default' => 24,
                'max' => 100,
                'min' => 10,
                'unit' => 'px',
            ],
        ];
    }

    public function render($atts, $content = null): string
    {
        $atts = shortcode_atts([
            'label' => 'READS',
            'color' => '#0066cc',
            'font_size' => '24',
        ], $atts);

        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $views = 0;
        if (class_exists(StatsManager::class)) {
            $views = StatsManager::getInstance()->getPostViews($post_id);
        }

        $formatted_views = number_format_i18n($views);

        $style = sprintf(
            'color: %s; font-size: %spx;',
            esc_attr($atts['color']),
            esc_attr($atts['font_size'])
        );

        ob_start();
        ?>
        <div class="post-views-counter" style="text-align: center; line-height: 1.2;">
            <div class="views-count" style="<?php echo $style; ?> font-weight: 700;">
                <?php echo esc_html($formatted_views); ?>
            </div>
            <div class="views-label" style="font-size: 0.5em; font-weight: 700; color: #333; text-transform: uppercase;">
                <?php echo esc_html($atts['label']); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
