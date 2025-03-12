<?php

/**
 * Elements styles block support.
 *
 * @since 5.8.0
 */

/**
 * Gets the elements class names.
 *
 * @since 6.0.0
 *
 * @param  array  $block  Block object.
 * @return string The unique class name.
 */
function wp_get_elements_class_name($block)
{
    return 'wp-elements-'.md5(serialize($block));
}

/**
 * Determines whether an elements class name should be added to the block.
 *
 * @since 6.6.0
 *
 * @param  array  $block  Block object.
 * @param  array  $options  Per element type options e.g. whether to skip serialization.
 * @return bool Whether the block needs an elements class name.
 */
function wp_should_add_elements_class_name($block, $options)
{
    if (! isset($block['attrs']['style']['elements'])) {
        return false;
    }

    $element_color_properties = [
        'button' => [
            'skip' => isset($options['button']['skip']) ? $options['button']['skip'] : false,
            'paths' => [
                ['button', 'color', 'text'],
                ['button', 'color', 'background'],
                ['button', 'color', 'gradient'],
            ],
        ],
        'link' => [
            'skip' => isset($options['link']['skip']) ? $options['link']['skip'] : false,
            'paths' => [
                ['link', 'color', 'text'],
                ['link', ':hover', 'color', 'text'],
            ],
        ],
        'heading' => [
            'skip' => isset($options['heading']['skip']) ? $options['heading']['skip'] : false,
            'paths' => [
                ['heading', 'color', 'text'],
                ['heading', 'color', 'background'],
                ['heading', 'color', 'gradient'],
                ['h1', 'color', 'text'],
                ['h1', 'color', 'background'],
                ['h1', 'color', 'gradient'],
                ['h2', 'color', 'text'],
                ['h2', 'color', 'background'],
                ['h2', 'color', 'gradient'],
                ['h3', 'color', 'text'],
                ['h3', 'color', 'background'],
                ['h3', 'color', 'gradient'],
                ['h4', 'color', 'text'],
                ['h4', 'color', 'background'],
                ['h4', 'color', 'gradient'],
                ['h5', 'color', 'text'],
                ['h5', 'color', 'background'],
                ['h5', 'color', 'gradient'],
                ['h6', 'color', 'text'],
                ['h6', 'color', 'background'],
                ['h6', 'color', 'gradient'],
            ],
        ],
    ];

    $elements_style_attributes = $block['attrs']['style']['elements'];

    foreach ($element_color_properties as $element_config) {
        if ($element_config['skip']) {
            continue;
        }

        foreach ($element_config['paths'] as $path) {
            if (_wp_array_get($elements_style_attributes, $path, null) !== null) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Render the elements stylesheet and adds elements class name to block as required.
 *
 * In the case of nested blocks we want the parent element styles to be rendered before their descendants.
 * This solves the issue of an element (e.g.: link color) being styled in both the parent and a descendant:
 * we want the descendant style to take priority, and this is done by loading it after, in DOM order.
 *
 * @since 6.0.0
 * @since 6.1.0 Implemented the style engine to generate CSS and classnames.
 * @since 6.6.0 Element block support class and styles are generated via the `render_block_data` filter instead of `pre_render_block`.
 *
 * @param  array  $parsed_block  The parsed block.
 * @return array The same parsed block with elements classname added if appropriate.
 */
function wp_render_elements_support_styles($parsed_block)
{
    /*
     * The generation of element styles and classname were moved to the
     * `render_block_data` filter in 6.6.0 to avoid filtered attributes
     * breaking the application of the elements CSS class.
     *
     * @see https://github.com/WordPress/gutenberg/pull/59535
     *
     * The change in filter means, the argument types for this function
     * have changed and require deprecating.
     */
    if (is_string($parsed_block)) {
        _deprecated_argument(
            __FUNCTION__,
            '6.6.0',
            __('Use as a `pre_render_block` filter is deprecated. Use with `render_block_data` instead.')
        );
    }

    $block_type = WP_Block_Type_Registry::get_instance()->get_registered($parsed_block['blockName']);
    $element_block_styles = isset($parsed_block['attrs']['style']['elements']) ? $parsed_block['attrs']['style']['elements'] : null;

    if (! $element_block_styles) {
        return $parsed_block;
    }

    $skip_link_color_serialization = wp_should_skip_block_supports_serialization($block_type, 'color', 'link');
    $skip_heading_color_serialization = wp_should_skip_block_supports_serialization($block_type, 'color', 'heading');
    $skip_button_color_serialization = wp_should_skip_block_supports_serialization($block_type, 'color', 'button');
    $skips_all_element_color_serialization = $skip_link_color_serialization &&
        $skip_heading_color_serialization &&
        $skip_button_color_serialization;

    if ($skips_all_element_color_serialization) {
        return $parsed_block;
    }

    $options = [
        'button' => ['skip' => $skip_button_color_serialization],
        'link' => ['skip' => $skip_link_color_serialization],
        'heading' => ['skip' => $skip_heading_color_serialization],
    ];

    if (! wp_should_add_elements_class_name($parsed_block, $options)) {
        return $parsed_block;
    }

    $class_name = wp_get_elements_class_name($parsed_block);
    $updated_class_name = isset($parsed_block['attrs']['className']) ? $parsed_block['attrs']['className']." $class_name" : $class_name;

    _wp_array_set($parsed_block, ['attrs', 'className'], $updated_class_name);

    // Generate element styles based on selector and store in style engine for enqueuing.
    $element_types = [
        'button' => [
            'selector' => ".$class_name .wp-element-button, .$class_name .wp-block-button__link",
            'skip' => $skip_button_color_serialization,
        ],
        'link' => [
            'selector' => ".$class_name a:where(:not(.wp-element-button))",
            'hover_selector' => ".$class_name a:where(:not(.wp-element-button)):hover",
            'skip' => $skip_link_color_serialization,
        ],
        'heading' => [
            'selector' => ".$class_name h1, .$class_name h2, .$class_name h3, .$class_name h4, .$class_name h5, .$class_name h6",
            'skip' => $skip_heading_color_serialization,
            'elements' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        ],
    ];

    foreach ($element_types as $element_type => $element_config) {
        if ($element_config['skip']) {
            continue;
        }

        $element_style_object = isset($element_block_styles[$element_type]) ? $element_block_styles[$element_type] : null;

        // Process primary element type styles.
        if ($element_style_object) {
            wp_style_engine_get_styles(
                $element_style_object,
                [
                    'selector' => $element_config['selector'],
                    'context' => 'block-supports',
                ]
            );

            if (isset($element_style_object[':hover'])) {
                wp_style_engine_get_styles(
                    $element_style_object[':hover'],
                    [
                        'selector' => $element_config['hover_selector'],
                        'context' => 'block-supports',
                    ]
                );
            }
        }

        // Process related elements e.g. h1-h6 for headings.
        if (isset($element_config['elements'])) {
            foreach ($element_config['elements'] as $element) {
                $element_style_object = isset($element_block_styles[$element])
                    ? $element_block_styles[$element]
                    : null;

                if ($element_style_object) {
                    wp_style_engine_get_styles(
                        $element_style_object,
                        [
                            'selector' => ".$class_name $element",
                            'context' => 'block-supports',
                        ]
                    );
                }
            }
        }
    }

    return $parsed_block;
}

/**
 * Ensure the elements block support class name generated, and added to
 * block attributes, in the `render_block_data` filter gets applied to the
 * block's markup.
 *
 * @see wp_render_elements_support_styles
 * @since 6.6.0
 *
 * @param  string  $block_content  Rendered block content.
 * @param  array  $block  Block object.
 * @return string Filtered block content.
 */
function wp_render_elements_class_name($block_content, $block)
{
    $class_string = $block['attrs']['className'] ?? '';
    preg_match('/\bwp-elements-\S+\b/', $class_string, $matches);

    if (empty($matches)) {
        return $block_content;
    }

    $tags = new WP_HTML_Tag_Processor($block_content);

    if ($tags->next_tag()) {
        $tags->add_class($matches[0]);
    }

    return $tags->get_updated_html();
}

add_filter('render_block', 'wp_render_elements_class_name', 10, 2);
add_filter('render_block_data', 'wp_render_elements_support_styles', 10, 1);
