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
 * @param  array  $block Block object.
 * @return string The unique class name.
 */
function wp_get_elements_class_name($block)
{
    return 'wp-elements-'.md5(serialize($block));
}

/**
 * Updates the block content with elements class names.
 *
 * @since 5.8.0
 * @since 6.4.0 Added support for button and heading element styling.
 *
 * @param  string  $block_content Rendered block content.
 * @param  array  $block         Block object.
 * @return string Filtered block content.
 */
function wp_render_elements_support($block_content, $block)
{
    if (! $block_content || ! isset($block['attrs']['style']['elements'])) {
        return $block_content;
    }

    $block_type = WP_Block_Type_Registry::get_instance()->get_registered($block['blockName']);
    if (! $block_type) {
        return $block_content;
    }

    $element_color_properties = [
        'button' => [
            'skip' => wp_should_skip_block_supports_serialization($block_type, 'color', 'button'),
            'paths' => [
                ['button', 'color', 'text'],
                ['button', 'color', 'background'],
                ['button', 'color', 'gradient'],
            ],
        ],
        'link' => [
            'skip' => wp_should_skip_block_supports_serialization($block_type, 'color', 'link'),
            'paths' => [
                ['link', 'color', 'text'],
                ['link', ':hover', 'color', 'text'],
            ],
        ],
        'heading' => [
            'skip' => wp_should_skip_block_supports_serialization($block_type, 'color', 'heading'),
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

    $skip_all_element_color_serialization = $element_color_properties['button']['skip'] &&
        $element_color_properties['link']['skip'] &&
        $element_color_properties['heading']['skip'];

    if ($skip_all_element_color_serialization) {
        return $block_content;
    }

    $elements_style_attributes = $block['attrs']['style']['elements'];

    foreach ($element_color_properties as $element_config) {
        if ($element_config['skip']) {
            continue;
        }

        foreach ($element_config['paths'] as $path) {
            if (_wp_array_get($elements_style_attributes, $path, null) !== null) {
                /*
                 * It only takes a single custom attribute to require that the custom
                 * class name be added to the block, so once one is found there's no
                 * need to continue looking for others.
                 *
                 * As is done with the layout hook, this code assumes that the block
                 * contains a single wrapper and that it's the first element in the
                 * rendered output. That first element, if it exists, gets the class.
                 */
                $tags = new WP_HTML_Tag_Processor($block_content);
                if ($tags->next_tag()) {
                    $tags->add_class(wp_get_elements_class_name($block));
                }

                return $tags->get_updated_html();
            }
        }
    }

    // If no custom attributes were found then there's nothing to modify.
    return $block_content;
}

/**
 * Renders the elements stylesheet.
 *
 * In the case of nested blocks we want the parent element styles to be rendered before their descendants.
 * This solves the issue of an element (e.g.: link color) being styled in both the parent and a descendant:
 * we want the descendant style to take priority, and this is done by loading it after, in DOM order.
 *
 * @since 6.0.0
 * @since 6.1.0 Implemented the style engine to generate CSS and classnames.
 *
 * @param  string|null  $pre_render The pre-rendered content. Default null.
 * @param  array  $block      The block being rendered.
 * @return null
 */
function wp_render_elements_support_styles($pre_render, $block)
{
    $block_type = WP_Block_Type_Registry::get_instance()->get_registered($block['blockName']);
    $element_block_styles = isset($block['attrs']['style']['elements']) ? $block['attrs']['style']['elements'] : null;

    if (! $element_block_styles) {
        return null;
    }

    $skip_link_color_serialization = wp_should_skip_block_supports_serialization($block_type, 'color', 'link');
    $skip_heading_color_serialization = wp_should_skip_block_supports_serialization($block_type, 'color', 'heading');
    $skip_button_color_serialization = wp_should_skip_block_supports_serialization($block_type, 'color', 'button');
    $skips_all_element_color_serialization = $skip_link_color_serialization &&
        $skip_heading_color_serialization &&
        $skip_button_color_serialization;

    if ($skips_all_element_color_serialization) {
        return null;
    }

    $class_name = wp_get_elements_class_name($block);

    $element_types = [
        'button' => [
            'selector' => ".$class_name .wp-element-button, .$class_name .wp-block-button__link",
            'skip' => $skip_button_color_serialization,
        ],
        'link' => [
            'selector' => ".$class_name a",
            'hover_selector' => ".$class_name a:hover",
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

    return null;
}

add_filter('render_block', 'wp_render_elements_support', 10, 2);
add_filter('pre_render_block', 'wp_render_elements_support_styles', 10, 2);
