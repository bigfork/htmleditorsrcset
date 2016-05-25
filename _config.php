<?php

namespace Bigfork\HtmlEditorSrcset;

/**
 * Will almost certainly never be used outside of this context... ho hum
 * 
 * @param string $elements The correctly formatted TinyMCE config - e.g. "a[href|class],img[src|alt]"
 * @param string $targetElement The element to enable the attribute for
 * @param string $attribute The attribute to be enabled
 */
function injectAllowedAttribute($elements, $targetElement, $attribute) {
	$elements = explode(',', $elements);

	$parsed = array();
	foreach ($elements as $element) {
		// Regex copied from TinyMCE source
		if (preg_match('/^([#+\-])?([^\[\/]+)(?:\/([^\[]+))?(?:\[([^\]]+)\])?$/', $element, $matches)) {
			$prefix = $matches[1]; // "#" in "#td[id|class]"
			$elementName = $matches[2]; // "a" in "a[href|class]"
			$outputName = isset($matches[3]) ? $matches[3] : ''; // "i" in "em/i[class]"
			$attributes = isset($matches[4]) ? $matches[4] : ''; // "src|alt" in "img[src|alt]"

			// Add attribute to the list of allowed attributes for the target element
			if ($elementName === $targetElement && $attributes) {
				$targetAttributes = explode('|', $attributes);

				if (!in_array($attribute, $targetAttributes)) {
					$targetAttributes[] = $attribute;
				}

				$attributes = implode('|', $targetAttributes);
			}

			// Rebuild rule
			$rule = $prefix . $elementName;
			if ($outputName) $rule .= "/{$outputName}";
			if ($attributes) $rule .= "[$attributes]";

			$parsed[] = $rule;
		}
	}

	return implode(',', $parsed);
}

// Add srcset to the list of allowed attributes for img tags in admin TinyMCE config
$config = \HtmlEditorConfig::get('cms');
$elements = injectAllowedAttribute($config->getOption('extended_valid_elements'), 'img', 'srcset');
$config->setOption('extended_valid_elements', $elements);
