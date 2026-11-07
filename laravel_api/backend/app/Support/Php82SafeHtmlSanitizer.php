<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Fallback HTML sanitizer for PHP < 8.4 environments where symfony/html-sanitizer v8's
 * NativeParser (\Dom\Node) collides with polyfilled \Dom\HTMLDocument returning \DOMElement.
 * On PHP 8.4+, Symfony's native HtmlSanitizer is used directly.
 */
final class Php82SafeHtmlSanitizer implements HtmlSanitizerInterface
{
    /** @var array<string, true> */
    private const ALLOWED_TAGS = [
        'a' => true,
        'b' => true,
        'blockquote' => true,
        'br' => true,
        'code' => true,
        'div' => true,
        'em' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
        'hr' => true,
        'i' => true,
        'img' => true,
        'li' => true,
        'ol' => true,
        'p' => true,
        'pre' => true,
        's' => true,
        'small' => true,
        'span' => true,
        'strong' => true,
        'sub' => true,
        'sup' => true,
        'u' => true,
        'ul' => true,
    ];

    /** @var array<string, true> */
    private const DROP_SUBTREE_TAGS = [
        'applet' => true,
        'base' => true,
        'embed' => true,
        'form' => true,
        'frame' => true,
        'frameset' => true,
        'head' => true,
        'iframe' => true,
        'link' => true,
        'math' => true,
        'meta' => true,
        'noscript' => true,
        'object' => true,
        'script' => true,
        'style' => true,
        'svg' => true,
        'template' => true,
        'textarea' => true,
        'title' => true,
    ];

    /** @var array<string, true> */
    private const ALLOWED_ATTRS = [
        'alt' => true,
        'class' => true,
        'data-color' => true,
        'data-from-breakpoint' => true,
        'data-type' => true,
        'height' => true,
        'href' => true,
        'rel' => true,
        'src' => true,
        'style' => true,
        'target' => true,
        'title' => true,
        'width' => true,
    ];

    public function sanitize(string $input): string
    {
        return $this->sanitizeFor('body', $input);
    }

    public function sanitizeFor(string $element, string $input): string
    {
        if ($input === '' || !preg_match('//u', $input)) {
            return '';
        }

        $input = str_replace("\0", '', $input);
        if (trim($input) === '') {
            return '';
        }

        if (strtolower($element) === 'title' || strtolower($element) === 'textarea') {
            return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        // Fast path for plain text without any HTML characters
        if (!str_contains($input, '<') && !str_contains($input, '&')) {
            return htmlspecialchars($input, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="__san_root__">'
            . $input
            . '</div></body></html>';
        $loaded = $dom->loadHTML($wrapped, LIBXML_NONET | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$loaded) {
            return htmlspecialchars(strip_tags($input), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $root = $dom->getElementById('__san_root__');
        if (!$root instanceof DOMElement) {
            return htmlspecialchars(strip_tags($input), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $this->cleanNode($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child) ?: '';
        }

        return $output;
    }

    private function cleanNode(DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);
                continue;
            }

            if (!$node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            if (isset(self::DROP_SUBTREE_TAGS[$tag])) {
                $parent->removeChild($node);
                continue;
            }

            $this->cleanNode($node);

            if (!isset(self::ALLOWED_TAGS[$tag])) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            $attrsToRemove = [];
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attr) {
                    $attrName = strtolower($attr->name);
                    $attrVal = trim($attr->value);

                    if (!isset(self::ALLOWED_ATTRS[$attrName]) || str_starts_with($attrName, 'on')) {
                        $attrsToRemove[] = $attr->name;
                        continue;
                    }

                    if ($attrName === 'href' || $attrName === 'src') {
                        $normalized = preg_replace('/[\x00-\x20]+/', '', strtolower($attrVal)) ?? '';
                        if (
                            str_starts_with($normalized, 'javascript:')
                            || str_starts_with($normalized, 'vbscript:')
                            || str_starts_with($normalized, 'data:')
                        ) {
                            $attrsToRemove[] = $attr->name;
                        }
                    } elseif ($attrName === 'style') {
                        $normalized = strtolower($attrVal);
                        if (
                            str_contains($normalized, 'expression(')
                            || str_contains($normalized, 'javascript:')
                            || str_contains($normalized, 'url(')
                        ) {
                            $attrsToRemove[] = $attr->name;
                        }
                    }
                }
            }

            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }
    }
}

