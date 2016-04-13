<?php

namespace Bigfork\HtmlEditorSrcset;

use Config;
use Extension;

class HtmlEditorFieldExtension extends Extension
{
    /**
     * @var array
     * @config
     */
    private static $densities = array(1, 2);

    /**
     * @param File|null $imageObject
     * @param DOMElement $imageElement
     */
    public function processImage($imageObject, $imageElement)
    {
        if (!$imageObject) {
            return;
        }

        $width = (int)$imageElement->getAttribute('width');
        $height = (int)$imageElement->getAttribute('height');

        $densities = (array)Config::inst()->get(__CLASS__, 'densities');
        $sources = array();
        foreach ($densities as $density) {
            $density = (int)$density;
            $resized = $imageObject->ResizedImage(ceil($width * $density), ceil($height * $density));
            // Output in the format "assets/foo.jpg 1x"
            $sources[] = $resized->getRelativePath() . " {$density}x";
        }

        $srcset = implode(', ', $sources);
        $imageElement->setAttribute('srcset', $srcset);
    }
}
