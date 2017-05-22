# HtmlEditorSrcset

Simple `srcset` integration with SilverStripe’s `HTMLEditorField`.

## What it does

Adds a `srcset` attribute for the provided pixel densities to every image inserted through TinyMCE. Resulting in an image tag like:

```html
<img width="100" height="50" src="assets/530dffc7f9/image__ResizedImageWzE5OCwxMzJd.jpg"
    srcset="assets/530dffc7f9/image__ResizedImageWzE5OCwxMzJd.jpg 1x, assets/530dffc7f9/image__ResizedImageWzM5NiwyNjRd.jpg 2x"
/>
```

## What it doesn’t do

This module doesn’t add a `sizes` attribute, nor does it specify widths in `srcset` - only pixel densities.

## Specifying your own pixel densities

The module will add sources for 1x and 2x pixel densities by default. You can set your own pixel densities by setting the following in your `config.yml`:

```yml
Bigfork\HTMLEditorSrcset\ImageShortcodeHandler:
  pixel_densities: [1, 2, 3]
```
