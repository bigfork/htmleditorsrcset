<?php

namespace Bigfork\HTMLEditorSrcset;

use SilverStripe\View\Parsers\ShortcodeParser;

// Register the shortcode parser
ShortcodeParser::get('default')
    ->register('image', [ImageShortcodeHandler::class, 'handle_shortcode']);
