<?php
namespace Optilarity\FlatsomeExtra\Shortcodes;

use Optilarity\FlatsomeExtra\Helper;

abstract class AbstractShortcode
{
    abstract public function getTag(): string;

    abstract public function getName(): string;

    public function getCategory(): string
    {
        return 'Content';
    }

    abstract public function getOptions(): array;

    public function isLayoutOnly(): bool
    {
        return false;
    }

    public function register()
    {
        add_shortcode($this->getTag(), [$this, 'render']);
        add_action('ux_builder_setup', [$this, 'registerUXBuilderElement']);
    }

    public function registerUXBuilderElement()
    {
        if (function_exists('add_ux_builder_shortcode')) {
            $category = $this->getCategory();

            // If this is a restricted element and we're not in the right context,
            // we still register it to avoid builder warnings (500/Warnings),
            // but we can move it to a 'hidden' or 'internal' category.
            if ($this->isLayoutOnly() && !Helper::isEditingOptilarityLayout()) {
                $category = ''; // Empty category often hides it from the Add panel
            }

            add_ux_builder_shortcode($this->getTag(), [
                'name' => $this->getName(),
                'category' => $category,
                'options' => (array) $this->getOptions(),
            ]);
        }
    }

    abstract public function render($atts, $content = null): string;
}
