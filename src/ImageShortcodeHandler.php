<?php

namespace Bigfork\HTMLEditorSrcset;

use SilverStripe\Assets\Image;
use SilverStripe\Assets\Shortcodes\ImageShortcodeProvider;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;

class ImageShortcodeHandler
{
    use Configurable;

    /**
     * @var array
     * @config
     */
    private static $default_pixel_densities = [1, 2];

    /**
     * @var array
     * @config
     */
    private static $pixel_densities = [];

    /**
     * Most of this is copied straight from Image::handle_shortcode()
     *
     * Replace"[image id=n]" shortcode with an image reference.
     * Permission checks will be enforced by the file routing itself.
     *
     * @param array $args Arguments passed to the parser
     * @param string $content Raw shortcode
     * @param ShortcodeParser $parser Parser
     * @param string $shortcode Name of shortcode used to register this handler
     * @param array $extra Extra arguments
     * @return string Result of the handled shortcode
     */
    public static function handle_shortcode($args, $content, $parser, $shortcode, $extra = array())
    {
        // Find appropriate record, with fallback for error handlers
        $record = ImageShortcodeProvider::find_shortcode_record($args);

        if (!$record) {
            return null; // There were no suitable matches at all.
        }

        $srcsetSources = [];

        // Check if a resize is required
        $src = $record->Link();
        if ($record instanceof Image) {
            $width = isset($args['width']) ? intval($args['width']) : null;
            $height = isset($args['height']) ? intval($args['height']) : null;
            if ($width && $height) {
                if ($width != $record->getWidth() || $height != $record->getHeight()) {
                    $resized = $record->ResizedImage($width, $height);
                    // Make sure that the resized image actually returns an image
                    if ($resized && $resizedUrl = $resized->getURL()) {
                        $src = $resizedUrl;
                    }
                }

                // Output srcset attribute for different pixel densities
                $densities = (array)static::config()->get('pixel_densities');
                if (empty($densities)) {
                    $densities = (array)static::config()->get('default_pixel_densities');
                }

                foreach ($densities as $density) {
                    $density = (int)$density;
                    $resized = $record->ResizedImage((int)ceil($width * $density), (int)ceil($height * $density));
                    // Output in the format "assets/foo.jpg 1x"
                    if ($resized && $resizedUrl = $resized->getURL()) {
                        $srcsetSources[] = $resizedUrl . " {$density}x";
                    }
                }
            }
        }

        // Build the HTML tag
        $attrs = array_merge(
            // Set overrideable defaults
            ['src' => '', 'alt' => $record->Title],
            // Use all other shortcode arguments
            $args,
            // But enforce some values
            ['id' => '', 'src' => $src, 'srcset' => implode(', ', $srcsetSources)]
        );

        // Clean out any empty attributes
        $attrs = array_filter($attrs, function ($v) {
            return (bool)$v;
        });

        // Condense to HTML attribute string
        $attrsStr = join(' ', array_map(function ($name) use ($attrs) {
            return Convert::raw2att($name) . '="' . Convert::raw2att($attrs[$name]) . '"';
        }, array_keys($attrs)));

        return '<img ' . $attrsStr . ' />';
    }
}
