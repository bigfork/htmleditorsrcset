<?php

namespace Bigfork\HTMLEditorSrcset;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Shortcodes\ImageShortcodeProvider;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

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
     * Most of this is copied straight from ImageShortcodeProvider
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
        $cache = ImageShortcodeProvider::getCache();
        $cacheKey = ImageShortcodeProvider::getCacheKey($args, $content);
        $cachedMarkup = static::getCachedMarkup($cache, $cacheKey, $args);
        if ($cachedMarkup) {
            return $cachedMarkup;
        }

        // Find appropriate record, with fallback for error handlers
        $fileFound = true;
        $record = ImageShortcodeProvider::find_shortcode_record($args, $errorCode);
        if ($errorCode) {
            $fileFound = false;
            $record = static::find_error_record($errorCode);
        }
        if (!$record) {
            return null; // There were no suitable matches at all.
        }

        // Check if a resize is required
        $width = null;
        $height = null;
        $grant = static::getGrant($record);
        $src = $record->getURL($grant);

        $srcsetSources = [];

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

        // Determine whether loading="lazy" is set
        $args = self::updateLoadingValue($args, $width, $height);

        // Build the HTML tag
        $attrs = array_merge(
        // Set overrideable defaults
            ['src' => '', 'alt' => ''],
            // Use all other shortcode arguments
            $args,
            // But enforce some values
            ['id' => '', 'src' => $src, 'srcset' => implode(', ', $srcsetSources)]
        );

        // If file was not found then use the Title value from static::find_error_record() for the alt attr
        if (!$fileFound) {
            $attrs['alt'] = $record->Title;
        }

        if ($record instanceof Image) {
            $record->extend('updateAttributes', $attrs);
        }

        // Clean out any empty attributes (aside from alt) and anything not whitelisted
        $whitelist = array_merge(ImageShortcodeProvider::config()->get('attribute_whitelist'), ['srcset']);
        $attrs = array_filter($attrs ?? [], function ($v, $k) use ($whitelist) {
            return in_array($k, $whitelist) && (strlen(trim($v ?? '')) || $k === 'alt');
        }, ARRAY_FILTER_USE_BOTH);

        $markup = static::createImageTag($attrs);

        // Cache it for future reference
        if ($fileFound) {
            $cache->set($cacheKey, [
                'markup' => $markup,
                'filename' => $record instanceof File ? $record->getFilename() : null,
                'hash' => $record instanceof File ? $record->getHash() : null,
            ]);
        }

        return $markup;
    }

    protected static function createImageTag(array $attributes): string
    {
        $preparedAttributes = '';
        foreach ($attributes as $attributeKey => $attributeValue) {
            if (strlen($attributeValue ?? '') > 0 || $attributeKey === 'alt') {
                $preparedAttributes .= sprintf(
                    ' %s="%s"',
                    $attributeKey,
                    htmlspecialchars($attributeValue ?? '', ENT_QUOTES, 'UTF-8', false)
                );
            }
        }

        return "<img{$preparedAttributes} />";
    }

    private static function updateLoadingValue(array $args, ?int $width, ?int $height): array
    {
        if (!Image::getLazyLoadingEnabled()) {
            return $args;
        }
        if (isset($args['loading']) && $args['loading'] == 'eager') {
            // per image override - unset the loading attribute unset to eager load (default browser behaviour)
            unset($args['loading']);
        } elseif ($width && $height) {
            // width and height must be present to prevent content shifting
            $args['loading'] = 'lazy';
        }
        return $args;
    }

    protected static function getCachedMarkup($cache, $cacheKey, $arguments): string
    {
        $item = $cache->get($cacheKey);
        $assetStore = Injector::inst()->get(AssetStore::class);
        if ($item && $item['markup'] && !empty($item['filename'])) {
            // Initiate a protected asset grant if necessary
            $allowSessionGrant = static::getGrant(null, $arguments);
            if ($allowSessionGrant && $assetStore->exists($item['filename'], $item['hash'])) {
                $assetStore->grant($item['filename'], $item['hash']);
                return $item['markup'];
            }
        }
        return '';
    }

    protected static function getGrant(?File $record, ?array $args = null): bool
    {
        $grant = ImageShortcodeProvider::config()->allow_session_grant;
        ImageShortcodeProvider::singleton()->extend('updateGrant', $grant, $record, $args);
        return $grant;
    }

    protected static function find_error_record($errorCode)
    {
        $result = ImageShortcodeProvider::singleton()->invokeWithExtensions('getErrorRecordFor', $errorCode);
        $result = array_filter($result ?? []);
        if ($result) {
            return reset($result);
        }

        return null;
    }
}
