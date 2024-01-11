<?php
/**
 * Server-side rendering of the `core/cover` block.
 */

/**
 * Renders the `core/cover` block on server.
 *
 * @param  array  $attributes The block attributes.
 * @param  string  $content    The block rendered content.
 * @return string Returns the cover block markup, if useFeaturedImage is true.
 */
function render_block_core_cover($attributes, $content)
{
    if ($attributes['backgroundType'] !== 'image' || $attributes['useFeaturedImage'] === false) {
        return $content;
    }

    if (! ($attributes['hasParallax'] || $attributes['isRepeated'])) {
        $attr = [
            'class' => 'wp-block-cover__image-background',
            'data-object-fit' => 'cover',
        ];

        if (isset($attributes['focalPoint'])) {
            $object_position = round($attributes['focalPoint']['x'] * 100).'% '.round($attributes['focalPoint']['y'] * 100).'%';
            $attr['data-object-position'] = $object_position;
            $attr['style'] = 'object-position: '.$object_position;
        }

        $image = get_the_post_thumbnail(null, 'post-thumbnail', $attr);

        /*
         * Inserts the featured image between the (1st) cover 'background' `span` and 'inner_container' `div`,
         * and removes eventual whitespace characters between the two (typically introduced at template level)
         */
        $inner_container_start = '/<div\b[^>]+wp-block-cover__inner-container[\s|"][^>]*>/U';
        if (preg_match($inner_container_start, $content, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $matches[0][1];
            $content = substr($content, 0, $offset).$image.substr($content, $offset);
        }
    } else {
        if (in_the_loop()) {
            update_post_thumbnail_cache();
        }
        $current_featured_image = get_the_post_thumbnail_url();
        if (! $current_featured_image) {
            return $content;
        }

        $processor = new WP_HTML_Tag_Processor($content);
        $processor->next_tag();

        $styles = $processor->get_attribute('style');
        $merged_styles = ! empty($styles) ? $styles.';' : '';
        $merged_styles .= 'background-image:url('.esc_url($current_featured_image).');';

        $processor->set_attribute('style', $merged_styles);
        $content = $processor->get_updated_html();
    }

    return $content;
}

/**
 * Registers the `core/cover` block renderer on server.
 */
function register_block_core_cover()
{
    register_block_type_from_metadata(
        __DIR__.'/cover',
        [
            'render_callback' => 'render_block_core_cover',
        ]
    );
}
add_action('init', 'register_block_core_cover');
