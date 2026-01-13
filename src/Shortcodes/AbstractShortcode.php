<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

abstract class AbstractShortcode
{
    abstract public function getTag(): string;

    abstract public function getName(): string;

    public function getCategory(): string
    {
        return 'Content';
    }

    abstract public function getOptions(): array;

    public function register()
    {
        add_shortcode($this->getTag(), [$this, 'render']);
        add_action('ux_builder_setup', [$this, 'registerUXBuilderElement']);
    }

    public function registerUXBuilderElement()
    {
        if (function_exists('add_ux_builder_shortcode')) {
            add_ux_builder_shortcode($this->getTag(), [
                'name' => $this->getName(),
                'category' => $this->getCategory(),
                'options' => $this->getOptions(),
            ]);
        }
    }

    abstract public function render($atts, $content = null): string;
}
