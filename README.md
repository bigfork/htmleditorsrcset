# HtmlEditorSrcset

Simple `srcset` integration with SilverStripe’s `HtmlEditorField`.

## What it does

Takes this:

```html
<img width="100" height="50" src="assets/_resampled/ResizedImage10050-image.jpg" />
```

And adds a `srcset` attribute for the provided pixel densities:

```html
<img width="100" height="50" src="assets/_resampled/ResizedImage10050-image.jpg"
	srcset="assets/_resampled/ResizedImage10050-image.jpg 1x, assets/_resampled/ResizedImage200100-image.jpg 2x"
/>
```

## What it doesn’t do

This module doesn’t add a `sizes` attribute, nor does it specify widths in `srcset` - only pixel densities.

## Adding other pixel densities

In your `config.yml`:

```yml
Bigfork\HtmlEditorSrcset\HtmlEditorFieldExtension:
  densities: [1, 2, 3]
```

## Removing pixel densities

Unfortunately there’s no way to remove or replace array config values in YAML, only merge new ones. So this has to be done in your `_config.php`:

```php
Config::inst()->remove('Bigfork\HtmlEditorSrcset\HtmlEditorFieldExtension', 'densities');
Config::inst()->update('Bigfork\HtmlEditorSrcset\HtmlEditorFieldExtension', 'densities', array(1, 1.5));
```
