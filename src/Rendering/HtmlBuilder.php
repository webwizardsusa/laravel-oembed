<?php

namespace Webwizardsusa\OEmbed\Rendering;

/**
 * Writes the `['tag', $attributes, ...$children]` arrays {@see EmbedMarkup} builds as HTML. A child is a nested array
 * or a string of text. Text and attribute values are escaped, attributes with unusual names or `on…` handlers are left
 * out, and null or false values are skipped, so the output is the same as Rich Editor Plus's serializer writes.
 */
class HtmlBuilder
{
    protected const array VOID_TAGS = ['br', 'hr', 'img', 'col', 'input'];

    /**
     * @param  array<mixed>  $element
     */
    public static function build(array $element): string
    {
        $tag = array_shift($element);

        if (! is_string($tag) || preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $tag) !== 1) {
            return '';
        }

        $attributes = is_array($element[0] ?? null) && ($element[0] === [] || ! array_is_list($element[0])) ? array_shift($element) : [];
        $html = '<'.$tag.static::attributes($attributes).'>';

        if (in_array(strtolower($tag), static::VOID_TAGS, true)) {
            return $html;
        }

        foreach ($element as $child) {
            $html .= match (true) {
                is_string($child) => htmlspecialchars($child, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                is_array($child) => static::build($child),
                default => '',
            };
        }

        return $html.'</'.$tag.'>';
    }

    /**
     * @param  array<array-key, mixed>  $attributes
     */
    protected static function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            $name = (string) $name;

            if (! is_scalar($value) || $value === false) {
                continue;
            }

            if (preg_match('/^[a-zA-Z][a-zA-Z0-9:_.-]*\z/', $name) !== 1 || str_starts_with(strtolower($name), 'on')) {
                continue;
            }

            $html .= ' '.$name.'="'.htmlspecialchars((string) $value, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8').'"';
        }

        return $html;
    }
}
