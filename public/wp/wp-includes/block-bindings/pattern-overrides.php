<?php
/**
 * Pattern Overrides source for the Block Bindings.
 *
 * @since 6.5.0
 */

/**
 * Gets value for the Pattern Overrides source.
 *
 * @since 6.5.0
 *
 * @param  array  $source_args  Array containing source arguments used to look up the override value.
 *                              Example: array( "key" => "foo" ).
 * @param  WP_Block  $block_instance  The block instance.
 * @param  string  $attribute_name  The name of the target attribute.
 * @return mixed The value computed for the source.
 */
function _block_bindings_pattern_overrides_get_value(array $source_args, $block_instance, string $attribute_name)
{
    if (empty($block_instance->attributes['metadata']['name'])) {
        return null;
    }
    $metadata_name = $block_instance->attributes['metadata']['name'];

    return _wp_array_get($block_instance->context, ['pattern/overrides', $metadata_name, $attribute_name], null);
}

/**
 * Registers Pattern Overrides source in the Block Bindings registry.
 *
 * @since 6.5.0
 */
function _register_block_bindings_pattern_overrides_source()
{
    register_block_bindings_source(
        'core/pattern-overrides',
        [
            'label' => _x('Pattern Overrides', 'block bindings source'),
            'get_value_callback' => '_block_bindings_pattern_overrides_get_value',
            'uses_context' => ['pattern/overrides'],
        ]
    );
}

add_action('init', '_register_block_bindings_pattern_overrides_source');
