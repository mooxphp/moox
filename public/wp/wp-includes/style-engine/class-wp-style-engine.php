<?php

/**
 * Style Engine: WP_Style_Engine class
 *
 * @since 6.1.0
 */

/**
 * The main class integrating all other WP_Style_Engine_* classes.
 *
 * The Style Engine aims to provide a consistent API for rendering styling for blocks
 * across both client-side and server-side applications.
 *
 * This class is final and should not be extended.
 *
 * This class is for internal Core usage and is not supposed to be used by extenders
 * (plugins and/or themes). This is a low-level API that may need to do breaking changes.
 * Please, use wp_style_engine_get_styles() instead.
 *
 * @since 6.1.0
 * @since 6.3.0 Added support for text-columns.
 * @since 6.4.0 Added support for background.backgroundImage.
 * @since 6.5.0 Added support for background.backgroundPosition,
 *              background.backgroundRepeat and dimensions.aspectRatio.
 * @since 6.7.0 Added support for typography.writingMode.
 */
#[AllowDynamicProperties]
final class WP_Style_Engine
{
    /**
     * Style definitions that contain the instructions to parse/output valid Gutenberg styles from a block's attributes.
     *
     * For every style definition, the following properties are valid:
     *
     *  - classnames    => (array) an array of classnames to be returned for block styles. The key is a classname or pattern.
     *                    A value of `true` means the classname should be applied always. Otherwise, a valid CSS property (string)
     *                    to match the incoming value, e.g., "color" to match var:preset|color|somePresetSlug.
     *  - css_vars      => (array) an array of key value pairs used to generate CSS var values.
     *                     The key should be the CSS property name that matches the second element of the preset string value,
     *                     i.e., "color" in var:preset|color|somePresetSlug. The value is a CSS var pattern (e.g. `--wp--preset--color--$slug`),
     *                     whose `$slug` fragment will be replaced with the preset slug, which is the third element of the preset string value,
     *                     i.e., `somePresetSlug` in var:preset|color|somePresetSlug.
     *  - property_keys => (array) array of keys whose values represent a valid CSS property, e.g., "margin" or "border".
     *  - path          => (array) a path that accesses the corresponding style value in the block style object.
     *  - value_func    => (string) the name of a function to generate a CSS definition array for a particular style object. The output of this function should be `array( "$property" => "$value", ... )`.
     *
     * @since 6.1.0
     *
     * @var array
     */
    const BLOCK_STYLE_DEFINITIONS_METADATA = [
        'background' => [
            'backgroundImage' => [
                'property_keys' => [
                    'default' => 'background-image',
                ],
                'value_func' => [self::class, 'get_url_or_value_css_declaration'],
                'path' => ['background', 'backgroundImage'],
            ],
            'backgroundPosition' => [
                'property_keys' => [
                    'default' => 'background-position',
                ],
                'path' => ['background', 'backgroundPosition'],
            ],
            'backgroundRepeat' => [
                'property_keys' => [
                    'default' => 'background-repeat',
                ],
                'path' => ['background', 'backgroundRepeat'],
            ],
            'backgroundSize' => [
                'property_keys' => [
                    'default' => 'background-size',
                ],
                'path' => ['background', 'backgroundSize'],
            ],
            'backgroundAttachment' => [
                'property_keys' => [
                    'default' => 'background-attachment',
                ],
                'path' => ['background', 'backgroundAttachment'],
            ],
        ],
        'color' => [
            'text' => [
                'property_keys' => [
                    'default' => 'color',
                ],
                'path' => ['color', 'text'],
                'css_vars' => [
                    'color' => '--wp--preset--color--$slug',
                ],
                'classnames' => [
                    'has-text-color' => true,
                    'has-$slug-color' => 'color',
                ],
            ],
            'background' => [
                'property_keys' => [
                    'default' => 'background-color',
                ],
                'path' => ['color', 'background'],
                'css_vars' => [
                    'color' => '--wp--preset--color--$slug',
                ],
                'classnames' => [
                    'has-background' => true,
                    'has-$slug-background-color' => 'color',
                ],
            ],
            'gradient' => [
                'property_keys' => [
                    'default' => 'background',
                ],
                'path' => ['color', 'gradient'],
                'css_vars' => [
                    'gradient' => '--wp--preset--gradient--$slug',
                ],
                'classnames' => [
                    'has-background' => true,
                    'has-$slug-gradient-background' => 'gradient',
                ],
            ],
        ],
        'border' => [
            'color' => [
                'property_keys' => [
                    'default' => 'border-color',
                    'individual' => 'border-%s-color',
                ],
                'path' => ['border', 'color'],
                'classnames' => [
                    'has-border-color' => true,
                    'has-$slug-border-color' => 'color',
                ],
            ],
            'radius' => [
                'property_keys' => [
                    'default' => 'border-radius',
                    'individual' => 'border-%s-radius',
                ],
                'path' => ['border', 'radius'],
            ],
            'style' => [
                'property_keys' => [
                    'default' => 'border-style',
                    'individual' => 'border-%s-style',
                ],
                'path' => ['border', 'style'],
            ],
            'width' => [
                'property_keys' => [
                    'default' => 'border-width',
                    'individual' => 'border-%s-width',
                ],
                'path' => ['border', 'width'],
            ],
            'top' => [
                'value_func' => [self::class, 'get_individual_property_css_declarations'],
                'path' => ['border', 'top'],
                'css_vars' => [
                    'color' => '--wp--preset--color--$slug',
                ],
            ],
            'right' => [
                'value_func' => [self::class, 'get_individual_property_css_declarations'],
                'path' => ['border', 'right'],
                'css_vars' => [
                    'color' => '--wp--preset--color--$slug',
                ],
            ],
            'bottom' => [
                'value_func' => [self::class, 'get_individual_property_css_declarations'],
                'path' => ['border', 'bottom'],
                'css_vars' => [
                    'color' => '--wp--preset--color--$slug',
                ],
            ],
            'left' => [
                'value_func' => [self::class, 'get_individual_property_css_declarations'],
                'path' => ['border', 'left'],
                'css_vars' => [
                    'color' => '--wp--preset--color--$slug',
                ],
            ],
        ],
        'shadow' => [
            'shadow' => [
                'property_keys' => [
                    'default' => 'box-shadow',
                ],
                'path' => ['shadow'],
                'css_vars' => [
                    'shadow' => '--wp--preset--shadow--$slug',
                ],
            ],
        ],
        'dimensions' => [
            'aspectRatio' => [
                'property_keys' => [
                    'default' => 'aspect-ratio',
                ],
                'path' => ['dimensions', 'aspectRatio'],
                'classnames' => [
                    'has-aspect-ratio' => true,
                ],
            ],
            'minHeight' => [
                'property_keys' => [
                    'default' => 'min-height',
                ],
                'path' => ['dimensions', 'minHeight'],
                'css_vars' => [
                    'spacing' => '--wp--preset--spacing--$slug',
                ],
            ],
        ],
        'spacing' => [
            'padding' => [
                'property_keys' => [
                    'default' => 'padding',
                    'individual' => 'padding-%s',
                ],
                'path' => ['spacing', 'padding'],
                'css_vars' => [
                    'spacing' => '--wp--preset--spacing--$slug',
                ],
            ],
            'margin' => [
                'property_keys' => [
                    'default' => 'margin',
                    'individual' => 'margin-%s',
                ],
                'path' => ['spacing', 'margin'],
                'css_vars' => [
                    'spacing' => '--wp--preset--spacing--$slug',
                ],
            ],
        ],
        'typography' => [
            'fontSize' => [
                'property_keys' => [
                    'default' => 'font-size',
                ],
                'path' => ['typography', 'fontSize'],
                'css_vars' => [
                    'font-size' => '--wp--preset--font-size--$slug',
                ],
                'classnames' => [
                    'has-$slug-font-size' => 'font-size',
                ],
            ],
            'fontFamily' => [
                'property_keys' => [
                    'default' => 'font-family',
                ],
                'css_vars' => [
                    'font-family' => '--wp--preset--font-family--$slug',
                ],
                'path' => ['typography', 'fontFamily'],
                'classnames' => [
                    'has-$slug-font-family' => 'font-family',
                ],
            ],
            'fontStyle' => [
                'property_keys' => [
                    'default' => 'font-style',
                ],
                'path' => ['typography', 'fontStyle'],
            ],
            'fontWeight' => [
                'property_keys' => [
                    'default' => 'font-weight',
                ],
                'path' => ['typography', 'fontWeight'],
            ],
            'lineHeight' => [
                'property_keys' => [
                    'default' => 'line-height',
                ],
                'path' => ['typography', 'lineHeight'],
            ],
            'textColumns' => [
                'property_keys' => [
                    'default' => 'column-count',
                ],
                'path' => ['typography', 'textColumns'],
            ],
            'textDecoration' => [
                'property_keys' => [
                    'default' => 'text-decoration',
                ],
                'path' => ['typography', 'textDecoration'],
            ],
            'textTransform' => [
                'property_keys' => [
                    'default' => 'text-transform',
                ],
                'path' => ['typography', 'textTransform'],
            ],
            'letterSpacing' => [
                'property_keys' => [
                    'default' => 'letter-spacing',
                ],
                'path' => ['typography', 'letterSpacing'],
            ],
            'writingMode' => [
                'property_keys' => [
                    'default' => 'writing-mode',
                ],
                'path' => ['typography', 'writingMode'],
            ],
        ],
    ];

    /**
     * Util: Extracts the slug in kebab case from a preset string,
     * e.g. `heavenly-blue` from `var:preset|color|heavenlyBlue`.
     *
     * @since 6.1.0
     *
     * @param  string  $style_value  A single CSS preset value.
     * @param  string  $property_key  The CSS property that is the second element of the preset string.
     *                                Used for matching.
     * @return string The slug, or empty string if not found.
     */
    protected static function get_slug_from_preset_value($style_value, $property_key)
    {
        if (is_string($style_value) && is_string($property_key)
            && str_contains($style_value, "var:preset|{$property_key}|")
        ) {
            $index_to_splice = strrpos($style_value, '|') + 1;

            return _wp_to_kebab_case(substr($style_value, $index_to_splice));
        }

        return '';
    }

    /**
     * Util: Generates a CSS var string, e.g. `var(--wp--preset--color--background)`
     * from a preset string such as `var:preset|space|50`.
     *
     * @since 6.1.0
     *
     * @param  string  $style_value  A single CSS preset value.
     * @param  string[]  $css_vars  An associate array of CSS var patterns
     *                              used to generate the var string.
     * @return string The CSS var, or an empty string if no match for slug found.
     */
    protected static function get_css_var_value($style_value, $css_vars)
    {
        foreach ($css_vars as $property_key => $css_var_pattern) {
            $slug = self::get_slug_from_preset_value($style_value, $property_key);
            if (self::is_valid_style_value($slug)) {
                $var = strtr(
                    $css_var_pattern,
                    ['$slug' => $slug]
                );

                return "var($var)";
            }
        }

        return '';
    }

    /**
     * Util: Checks whether an incoming block style value is valid.
     *
     * @since 6.1.0
     *
     * @param  string  $style_value  A single CSS preset value.
     * @return bool
     */
    protected static function is_valid_style_value($style_value)
    {
        return $style_value === '0' || ! empty($style_value);
    }

    /**
     * Stores a CSS rule using the provided CSS selector and CSS declarations.
     *
     * @since 6.1.0
     * @since 6.6.0 Added the `$rules_group` parameter.
     *
     * @param  string  $store_name  A valid store key.
     * @param  string  $css_selector  When a selector is passed, the function will return
     *                                a full CSS rule `$selector { ...rules }`
     *                                otherwise a concatenated string of properties and values.
     * @param  string[]  $css_declarations  An associative array of CSS definitions,
     *                                      e.g. `array( "$property" => "$value", "$property" => "$value" )`.
     * @param  string  $rules_group  Optional. A parent CSS selector in the case of nested CSS, or a CSS nested @rule,
     *                               such as `@media (min-width: 80rem)` or `@layer module`.
     */
    public static function store_css_rule($store_name, $css_selector, $css_declarations, $rules_group = '')
    {
        if (empty($store_name) || empty($css_selector) || empty($css_declarations)) {
            return;
        }
        self::get_store($store_name)->add_rule($css_selector, $rules_group)->add_declarations($css_declarations);
    }

    /**
     * Returns a store by store key.
     *
     * @since 6.1.0
     *
     * @param  string  $store_name  A store key.
     * @return WP_Style_Engine_CSS_Rules_Store|null
     */
    public static function get_store($store_name)
    {
        return WP_Style_Engine_CSS_Rules_Store::get_store($store_name);
    }

    /**
     * Returns classnames and CSS based on the values in a styles object.
     *
     * Return values are parsed based on the instructions in BLOCK_STYLE_DEFINITIONS_METADATA.
     *
     * @since 6.1.0
     *
     * @param  array  $block_styles  The style object.
     * @param  array  $options  {
     *                          Optional. An array of options. Default empty array.
     *
     * @type bool $convert_vars_to_classnames Whether to skip converting incoming CSS var patterns,
     *            e.g. `var:preset|<PRESET_TYPE>|<PRESET_SLUG>`,
     *            to `var( --wp--preset--* )` values. Default false.
     * @type string $selector                   Optional. When a selector is passed,
     *              the value of `$css` in the return value will comprise
     *              a full CSS rule `$selector { ...$css_declarations }`,
     *              otherwise, the value will be a concatenated string
     *              of CSS declarations.
     *              }
     *
     * @return array {
     *
     * @type string[] $classnames   Array of class names.
     * @type string[] $declarations An associative array of CSS definitions,
     *                e.g. `array( "$property" => "$value", "$property" => "$value" )`.
     *                }
     */
    public static function parse_block_styles($block_styles, $options)
    {
        $parsed_styles = [
            'classnames' => [],
            'declarations' => [],
        ];
        if (empty($block_styles) || ! is_array($block_styles)) {
            return $parsed_styles;
        }

        // Collect CSS and classnames.
        foreach (self::BLOCK_STYLE_DEFINITIONS_METADATA as $definition_group_key => $definition_group_style) {
            if (empty($block_styles[$definition_group_key])) {
                continue;
            }
            foreach ($definition_group_style as $style_definition) {
                $style_value = _wp_array_get($block_styles, $style_definition['path'], null);

                if (! self::is_valid_style_value($style_value)) {
                    continue;
                }

                $parsed_styles['classnames'] = array_merge($parsed_styles['classnames'], self::get_classnames($style_value, $style_definition));
                $parsed_styles['declarations'] = array_merge($parsed_styles['declarations'], self::get_css_declarations($style_value, $style_definition, $options));
            }
        }

        return $parsed_styles;
    }

    /**
     * Returns classnames, and generates classname(s) from a CSS preset property pattern,
     * e.g. `var:preset|<PRESET_TYPE>|<PRESET_SLUG>`.
     *
     * @since 6.1.0
     *
     * @param  string  $style_value  A single raw style value or CSS preset property
     *                               from the `$block_styles` array.
     * @param  array  $style_definition  A single style definition from BLOCK_STYLE_DEFINITIONS_METADATA.
     * @return string[] An array of CSS classnames, or empty array if there are none.
     */
    protected static function get_classnames($style_value, $style_definition)
    {
        if (empty($style_value)) {
            return [];
        }

        $classnames = [];
        if (! empty($style_definition['classnames'])) {
            foreach ($style_definition['classnames'] as $classname => $property_key) {
                if ($property_key === true) {
                    $classnames[] = $classname;

                    continue;
                }

                $slug = self::get_slug_from_preset_value($style_value, $property_key);

                if ($slug) {
                    /*
                     * Right now we expect a classname pattern to be stored in BLOCK_STYLE_DEFINITIONS_METADATA.
                     * One day, if there are no stored schemata, we could allow custom patterns or
                     * generate classnames based on other properties
                     * such as a path or a value or a prefix passed in options.
                     */
                    $classnames[] = strtr($classname, ['$slug' => $slug]);
                }
            }
        }

        return $classnames;
    }

    /**
     * Returns an array of CSS declarations based on valid block style values.
     *
     * @since 6.1.0
     *
     * @param  mixed  $style_value  A single raw style value from $block_styles array.
     * @param  array  $style_definition  A single style definition from BLOCK_STYLE_DEFINITIONS_METADATA.
     * @param  array  $options  {
     *                          Optional. An array of options. Default empty array.
     *
     * @type bool $convert_vars_to_classnames Whether to skip converting incoming CSS var patterns,
     *            e.g. `var:preset|<PRESET_TYPE>|<PRESET_SLUG>`,
     *            to `var( --wp--preset--* )` values. Default false.
     *            }
     *
     * @return string[] An associative array of CSS definitions, e.g. `array( "$property" => "$value", "$property" => "$value" )`.
     */
    protected static function get_css_declarations($style_value, $style_definition, $options = [])
    {
        if (isset($style_definition['value_func']) && is_callable($style_definition['value_func'])) {
            return call_user_func($style_definition['value_func'], $style_value, $style_definition, $options);
        }

        $css_declarations = [];
        $style_property_keys = $style_definition['property_keys'];
        $should_skip_css_vars = isset($options['convert_vars_to_classnames']) && $options['convert_vars_to_classnames'] === true;

        /*
         * Build CSS var values from `var:preset|<PRESET_TYPE>|<PRESET_SLUG>` values, e.g, `var(--wp--css--rule-slug )`.
         * Check if the value is a CSS preset and there's a corresponding css_var pattern in the style definition.
         */
        if (is_string($style_value) && str_contains($style_value, 'var:')) {
            if (! $should_skip_css_vars && ! empty($style_definition['css_vars'])) {
                $css_var = self::get_css_var_value($style_value, $style_definition['css_vars']);
                if (self::is_valid_style_value($css_var)) {
                    $css_declarations[$style_property_keys['default']] = $css_var;
                }
            }

            return $css_declarations;
        }

        /*
         * Default rule builder.
         * If the input contains an array, assume box model-like properties
         * for styles such as margins and padding.
         */
        if (is_array($style_value)) {
            // Bail out early if the `'individual'` property is not defined.
            if (! isset($style_property_keys['individual'])) {
                return $css_declarations;
            }

            foreach ($style_value as $key => $value) {
                if (is_string($value) && str_contains($value, 'var:') && ! $should_skip_css_vars && ! empty($style_definition['css_vars'])) {
                    $value = self::get_css_var_value($value, $style_definition['css_vars']);
                }

                $individual_property = sprintf($style_property_keys['individual'], _wp_to_kebab_case($key));

                if ($individual_property && self::is_valid_style_value($value)) {
                    $css_declarations[$individual_property] = $value;
                }
            }

            return $css_declarations;
        }

        $css_declarations[$style_property_keys['default']] = $style_value;

        return $css_declarations;
    }

    /**
     * Style value parser that returns a CSS definition array comprising style properties
     * that have keys representing individual style properties, otherwise known as longhand CSS properties.
     *
     * Example:
     *
     *     "$style_property-$individual_feature: $value;"
     *
     * Which could represent the following:
     *
     *     "border-{top|right|bottom|left}-{color|width|style}: {value};"
     *
     * or:
     *
     *     "border-image-{outset|source|width|repeat|slice}: {value};"
     *
     * @since 6.1.0
     *
     * @param  array  $style_value  A single raw style value from `$block_styles` array.
     * @param  array  $individual_property_definition  A single style definition from BLOCK_STYLE_DEFINITIONS_METADATA
     *                                                 representing an individual property of a CSS property,
     *                                                 e.g. 'top' in 'border-top'.
     * @param  array  $options  {
     *                          Optional. An array of options. Default empty array.
     *
     * @type bool $convert_vars_to_classnames Whether to skip converting incoming CSS var patterns,
     *            e.g. `var:preset|<PRESET_TYPE>|<PRESET_SLUG>`,
     *            to `var( --wp--preset--* )` values. Default false.
     *            }
     *
     * @return string[] An associative array of CSS definitions, e.g. `array( "$property" => "$value", "$property" => "$value" )`.
     */
    protected static function get_individual_property_css_declarations($style_value, $individual_property_definition, $options = [])
    {
        if (! is_array($style_value) || empty($style_value) || empty($individual_property_definition['path'])) {
            return [];
        }

        /*
         * The first item in $individual_property_definition['path'] array
         * tells us the style property, e.g. "border". We use this to get a corresponding
         * CSS style definition such as "color" or "width" from the same group.
         *
         * The second item in $individual_property_definition['path'] array
         * refers to the individual property marker, e.g. "top".
         */
        $definition_group_key = $individual_property_definition['path'][0];
        $individual_property_key = $individual_property_definition['path'][1];
        $should_skip_css_vars = isset($options['convert_vars_to_classnames']) && $options['convert_vars_to_classnames'] === true;
        $css_declarations = [];

        foreach ($style_value as $css_property => $value) {
            if (empty($value)) {
                continue;
            }

            // Build a path to the individual rules in definitions.
            $style_definition_path = [$definition_group_key, $css_property];
            $style_definition = _wp_array_get(self::BLOCK_STYLE_DEFINITIONS_METADATA, $style_definition_path, null);

            if ($style_definition && isset($style_definition['property_keys']['individual'])) {
                // Set a CSS var if there is a valid preset value.
                if (is_string($value) && str_contains($value, 'var:')
                    && ! $should_skip_css_vars && ! empty($individual_property_definition['css_vars'])
                ) {
                    $value = self::get_css_var_value($value, $individual_property_definition['css_vars']);
                }

                $individual_css_property = sprintf($style_definition['property_keys']['individual'], $individual_property_key);

                $css_declarations[$individual_css_property] = $value;
            }
        }

        return $css_declarations;
    }

    /**
     * Style value parser that constructs a CSS definition array comprising a single CSS property and value.
     * If the provided value is an array containing a `url` property, the function will return a CSS definition array
     * with a single property and value, with `url` escaped and injected into a CSS `url()` function,
     * e.g., array( 'background-image' => "url( '...' )" ).
     *
     * @since 6.4.0
     *
     * @param  array  $style_value  A single raw style value from $block_styles array.
     * @param  array  $style_definition  A single style definition from BLOCK_STYLE_DEFINITIONS_METADATA.
     * @return string[] An associative array of CSS definitions, e.g., array( "$property" => "$value", "$property" => "$value" ).
     */
    protected static function get_url_or_value_css_declaration($style_value, $style_definition)
    {
        if (empty($style_value)) {
            return [];
        }

        $css_declarations = [];

        if (isset($style_definition['property_keys']['default'])) {
            $value = null;

            if (! empty($style_value['url'])) {
                $value = "url('".$style_value['url']."')";
            } elseif (is_string($style_value)) {
                $value = $style_value;
            }

            if ($value !== null) {
                $css_declarations[$style_definition['property_keys']['default']] = $value;
            }
        }

        return $css_declarations;
    }

    /**
     * Returns compiled CSS from CSS declarations.
     *
     * @since 6.1.0
     *
     * @param  string[]  $css_declarations  An associative array of CSS definitions,
     *                                      e.g. `array( "$property" => "$value", "$property" => "$value" )`.
     * @param  string  $css_selector  When a selector is passed, the function will return
     *                                a full CSS rule `$selector { ...rules }`,
     *                                otherwise a concatenated string of properties and values.
     * @return string A compiled CSS string.
     */
    public static function compile_css($css_declarations, $css_selector)
    {
        if (empty($css_declarations) || ! is_array($css_declarations)) {
            return '';
        }

        // Return an entire rule if there is a selector.
        if ($css_selector) {
            $css_rule = new WP_Style_Engine_CSS_Rule($css_selector, $css_declarations);

            return $css_rule->get_css();
        }

        $css_declarations = new WP_Style_Engine_CSS_Declarations($css_declarations);

        return $css_declarations->get_declarations_string();
    }

    /**
     * Returns a compiled stylesheet from stored CSS rules.
     *
     * @since 6.1.0
     *
     * @param  WP_Style_Engine_CSS_Rule[]  $css_rules  An array of WP_Style_Engine_CSS_Rule objects
     *                                                 from a store or otherwise.
     * @param  array  $options  {
     *                          Optional. An array of options. Default empty array.
     *
     * @type string|null $context  An identifier describing the origin of the style object,
     *                   e.g. 'block-supports' or 'global-styles'. Default 'block-supports'.
     *                   When set, the style engine will attempt to store the CSS rules.
     * @type bool $optimize Whether to optimize the CSS output, e.g. combine rules.
     *            Default false.
     * @type bool $prettify Whether to add new lines and indents to output.
     *            Defaults to whether the `SCRIPT_DEBUG` constant is defined.
     *            }
     *
     * @return string A compiled stylesheet from stored CSS rules.
     */
    public static function compile_stylesheet_from_css_rules($css_rules, $options = [])
    {
        $processor = new WP_Style_Engine_Processor;
        $processor->add_rules($css_rules);

        return $processor->get_css($options);
    }
}
