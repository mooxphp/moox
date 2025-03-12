<?php

/**
 * REST API: WP_REST_Block_Types_Controller class
 *
 * @since 5.5.0
 */

/**
 * Core class used to access block types via the REST API.
 *
 * @since 5.5.0
 * @see WP_REST_Controller
 */
class WP_REST_Block_Types_Controller extends WP_REST_Controller
{
    const NAME_PATTERN = '^[a-z][a-z0-9-]*/[a-z][a-z0-9-]*$';

    /**
     * Instance of WP_Block_Type_Registry.
     *
     * @since 5.5.0
     *
     * @var WP_Block_Type_Registry
     */
    protected $block_registry;

    /**
     * Instance of WP_Block_Styles_Registry.
     *
     * @since 5.5.0
     *
     * @var WP_Block_Styles_Registry
     */
    protected $style_registry;

    /**
     * Constructor.
     *
     * @since 5.5.0
     */
    public function __construct()
    {
        $this->namespace = 'wp/v2';
        $this->rest_base = 'block-types';
        $this->block_registry = WP_Block_Type_Registry::get_instance();
        $this->style_registry = WP_Block_Styles_Registry::get_instance();
    }

    /**
     * Registers the routes for block types.
     *
     * @since 5.5.0
     * @see register_rest_route()
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/'.$this->rest_base,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/'.$this->rest_base.'/(?P<namespace>[a-zA-Z0-9_-]+)',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/'.$this->rest_base.'/(?P<namespace>[a-zA-Z0-9_-]+)/(?P<name>[a-zA-Z0-9_-]+)',
            [
                'args' => [
                    'name' => [
                        'description' => __('Block name.'),
                        'type' => 'string',
                    ],
                    'namespace' => [
                        'description' => __('Block namespace.'),
                        'type' => 'string',
                    ],
                ],
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => [
                        'context' => $this->get_context_param(['default' => 'view']),
                    ],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );
    }

    /**
     * Checks whether a given request has permission to read post block types.
     *
     * @since 5.5.0
     *
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check($request)
    {
        return $this->check_read_permission();
    }

    /**
     * Retrieves all post block types, depending on user context.
     *
     * @since 5.5.0
     *
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request)
    {
        $data = [];
        $block_types = $this->block_registry->get_all_registered();

        // Retrieve the list of registered collection query parameters.
        $registered = $this->get_collection_params();
        $namespace = '';
        if (isset($registered['namespace']) && ! empty($request['namespace'])) {
            $namespace = $request['namespace'];
        }

        foreach ($block_types as $obj) {
            if ($namespace) {
                [$block_namespace] = explode('/', $obj->name);

                if ($namespace !== $block_namespace) {
                    continue;
                }
            }
            $block_type = $this->prepare_item_for_response($obj, $request);
            $data[] = $this->prepare_response_for_collection($block_type);
        }

        return rest_ensure_response($data);
    }

    /**
     * Checks if a given request has access to read a block type.
     *
     * @since 5.5.0
     *
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_item_permissions_check($request)
    {
        $check = $this->check_read_permission();
        if (is_wp_error($check)) {
            return $check;
        }
        $block_name = sprintf('%s/%s', $request['namespace'], $request['name']);
        $block_type = $this->get_block($block_name);
        if (is_wp_error($block_type)) {
            return $block_type;
        }

        return true;
    }

    /**
     * Checks whether a given block type should be visible.
     *
     * @since 5.5.0
     *
     * @return true|WP_Error True if the block type is visible, WP_Error otherwise.
     */
    protected function check_read_permission()
    {
        if (current_user_can('edit_posts')) {
            return true;
        }
        foreach (get_post_types(['show_in_rest' => true], 'objects') as $post_type) {
            if (current_user_can($post_type->cap->edit_posts)) {
                return true;
            }
        }

        return new WP_Error('rest_block_type_cannot_view', __('Sorry, you are not allowed to manage block types.'), ['status' => rest_authorization_required_code()]);
    }

    /**
     * Get the block, if the name is valid.
     *
     * @since 5.5.0
     *
     * @param  string  $name  Block name.
     * @return WP_Block_Type|WP_Error Block type object if name is valid, WP_Error otherwise.
     */
    protected function get_block($name)
    {
        $block_type = $this->block_registry->get_registered($name);
        if (empty($block_type)) {
            return new WP_Error('rest_block_type_invalid', __('Invalid block type.'), ['status' => 404]);
        }

        return $block_type;
    }

    /**
     * Retrieves a specific block type.
     *
     * @since 5.5.0
     *
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item($request)
    {
        $block_name = sprintf('%s/%s', $request['namespace'], $request['name']);
        $block_type = $this->get_block($block_name);
        if (is_wp_error($block_type)) {
            return $block_type;
        }
        $data = $this->prepare_item_for_response($block_type, $request);

        return rest_ensure_response($data);
    }

    /**
     * Prepares a block type object for serialization.
     *
     * @since 5.5.0
     * @since 5.9.0 Renamed `$block_type` to `$item` to match parent class for PHP 8 named parameter support.
     * @since 6.3.0 Added `selectors` field.
     * @since 6.5.0 Added `view_script_module_ids` field.
     *
     * @param  WP_Block_Type  $item  Block type data.
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return WP_REST_Response Block type data.
     */
    public function prepare_item_for_response($item, $request)
    {
        // Restores the more descriptive, specific name for use within this method.
        $block_type = $item;

        $fields = $this->get_fields_for_response($request);
        $data = [];

        if (rest_is_field_included('attributes', $fields)) {
            $data['attributes'] = $block_type->get_attributes();
        }

        if (rest_is_field_included('is_dynamic', $fields)) {
            $data['is_dynamic'] = $block_type->is_dynamic();
        }

        $schema = $this->get_item_schema();
        // Fields deprecated in WordPress 6.1, but left in the schema for backwards compatibility.
        $deprecated_fields = [
            'editor_script',
            'script',
            'view_script',
            'editor_style',
            'style',
        ];
        $extra_fields = array_merge(
            [
                'api_version',
                'name',
                'title',
                'description',
                'icon',
                'category',
                'keywords',
                'parent',
                'ancestor',
                'allowed_blocks',
                'provides_context',
                'uses_context',
                'selectors',
                'supports',
                'styles',
                'textdomain',
                'example',
                'editor_script_handles',
                'script_handles',
                'view_script_handles',
                'view_script_module_ids',
                'editor_style_handles',
                'style_handles',
                'view_style_handles',
                'variations',
                'block_hooks',
            ],
            $deprecated_fields
        );
        foreach ($extra_fields as $extra_field) {
            if (rest_is_field_included($extra_field, $fields)) {
                if (isset($block_type->$extra_field)) {
                    $field = $block_type->$extra_field;
                    if (in_array($extra_field, $deprecated_fields, true) && is_array($field)) {
                        // Since the schema only allows strings or null (but no arrays), we return the first array item.
                        $field = ! empty($field) ? array_shift($field) : '';
                    }
                } elseif (array_key_exists('default', $schema['properties'][$extra_field])) {
                    $field = $schema['properties'][$extra_field]['default'];
                } else {
                    $field = '';
                }
                $data[$extra_field] = rest_sanitize_value_from_schema($field, $schema['properties'][$extra_field]);
            }
        }

        if (rest_is_field_included('styles', $fields)) {
            $styles = $this->style_registry->get_registered_styles_for_block($block_type->name);
            $styles = array_values($styles);
            $data['styles'] = wp_parse_args($styles, $data['styles']);
            $data['styles'] = array_filter($data['styles']);
        }

        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);

        $response = rest_ensure_response($data);

        if (rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields)) {
            $response->add_links($this->prepare_links($block_type));
        }

        /**
         * Filters a block type returned from the REST API.
         *
         * Allows modification of the block type data right before it is returned.
         *
         * @since 5.5.0
         *
         * @param  WP_REST_Response  $response  The response object.
         * @param  WP_Block_Type  $block_type  The original block type object.
         * @param  WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('rest_prepare_block_type', $response, $block_type, $request);
    }

    /**
     * Prepares links for the request.
     *
     * @since 5.5.0
     *
     * @param  WP_Block_Type  $block_type  Block type data.
     * @return array Links for the given block type.
     */
    protected function prepare_links($block_type)
    {
        [$namespace] = explode('/', $block_type->name);

        $links = [
            'collection' => [
                'href' => rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base)),
            ],
            'self' => [
                'href' => rest_url(sprintf('%s/%s/%s', $this->namespace, $this->rest_base, $block_type->name)),
            ],
            'up' => [
                'href' => rest_url(sprintf('%s/%s/%s', $this->namespace, $this->rest_base, $namespace)),
            ],
        ];

        if ($block_type->is_dynamic()) {
            $links['https://api.w.org/render-block'] = [
                'href' => add_query_arg(
                    'context',
                    'edit',
                    rest_url(sprintf('%s/%s/%s', 'wp/v2', 'block-renderer', $block_type->name))
                ),
            ];
        }

        return $links;
    }

    /**
     * Retrieves the block type' schema, conforming to JSON Schema.
     *
     * @since 5.5.0
     * @since 6.3.0 Added `selectors` field.
     *
     * @return array Item schema data.
     */
    public function get_item_schema()
    {
        if ($this->schema) {
            return $this->add_additional_fields_schema($this->schema);
        }

        // rest_validate_value_from_schema doesn't understand $refs, pull out reused definitions for readability.
        $inner_blocks_definition = [
            'description' => __('The list of inner blocks used in the example.'),
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'description' => __('The name of the inner block.'),
                        'type' => 'string',
                        'pattern' => self::NAME_PATTERN,
                        'required' => true,
                    ],
                    'attributes' => [
                        'description' => __('The attributes of the inner block.'),
                        'type' => 'object',
                    ],
                    'innerBlocks' => [
                        'description' => __("A list of the inner block's own inner blocks. This is a recursive definition following the parent innerBlocks schema."),
                        'type' => 'array',
                    ],
                ],
            ],
        ];

        $example_definition = [
            'description' => __('Block example.'),
            'type' => ['object', 'null'],
            'default' => null,
            'properties' => [
                'attributes' => [
                    'description' => __('The attributes used in the example.'),
                    'type' => 'object',
                ],
                'innerBlocks' => $inner_blocks_definition,
            ],
            'context' => ['embed', 'view', 'edit'],
            'readonly' => true,
        ];

        $keywords_definition = [
            'description' => __('Block keywords.'),
            'type' => 'array',
            'items' => [
                'type' => 'string',
            ],
            'default' => [],
            'context' => ['embed', 'view', 'edit'],
            'readonly' => true,
        ];

        $icon_definition = [
            'description' => __('Icon of block type.'),
            'type' => ['string', 'null'],
            'default' => null,
            'context' => ['embed', 'view', 'edit'],
            'readonly' => true,
        ];

        $category_definition = [
            'description' => __('Block category.'),
            'type' => ['string', 'null'],
            'default' => null,
            'context' => ['embed', 'view', 'edit'],
            'readonly' => true,
        ];

        $this->schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'block-type',
            'type' => 'object',
            'properties' => [
                'api_version' => [
                    'description' => __('Version of block API.'),
                    'type' => 'integer',
                    'default' => 1,
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'title' => [
                    'description' => __('Title of block type.'),
                    'type' => 'string',
                    'default' => '',
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'name' => [
                    'description' => __('Unique name identifying the block type.'),
                    'type' => 'string',
                    'pattern' => self::NAME_PATTERN,
                    'required' => true,
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'description' => [
                    'description' => __('Description of block type.'),
                    'type' => 'string',
                    'default' => '',
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'icon' => $icon_definition,
                'attributes' => [
                    'description' => __('Block attributes.'),
                    'type' => ['object', 'null'],
                    'properties' => [],
                    'default' => null,
                    'additionalProperties' => [
                        'type' => 'object',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'provides_context' => [
                    'description' => __('Context provided by blocks of this type.'),
                    'type' => 'object',
                    'properties' => [],
                    'additionalProperties' => [
                        'type' => 'string',
                    ],
                    'default' => [],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'uses_context' => [
                    'description' => __('Context values inherited by blocks of this type.'),
                    'type' => 'array',
                    'default' => [],
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'selectors' => [
                    'description' => __('Custom CSS selectors.'),
                    'type' => 'object',
                    'default' => [],
                    'properties' => [],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'supports' => [
                    'description' => __('Block supports.'),
                    'type' => 'object',
                    'default' => [],
                    'properties' => [],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'category' => $category_definition,
                'is_dynamic' => [
                    'description' => __('Is the block dynamically rendered.'),
                    'type' => 'boolean',
                    'default' => false,
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'editor_script_handles' => [
                    'description' => __('Editor script handles.'),
                    'type' => ['array'],
                    'default' => [],
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'script_handles' => [
                    'description' => __('Public facing and editor script handles.'),
                    'type' => ['array'],
                    'default' => [],
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'view_script_handles' => [
                    'description' => __('Public facing script handles.'),
                    'type' => ['array'],
                    'default' => [],
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'view_script_module_ids' => [
                    'description' => __('Public facing script module IDs.'),
                    'type' => ['array'],
                    'default' => [],
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'editor_style_handles' => [
                    'description' => __('Editor style handles.'),
                    'type' => ['array'],
                    'default' => [],
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'style_handles' => [
                    'description' => __('Public facing and editor style handles.'),
                    'type' => ['array'],
                    'default' => [],
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'view_style_handles' => [
                    'description' => __('Public facing style handles.'),
                    'type' => ['array'],
                    'default' => [],
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'styles' => [
                    'description' => __('Block style variations.'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'description' => __('Unique name identifying the style.'),
                                'type' => 'string',
                                'required' => true,
                            ],
                            'label' => [
                                'description' => __('The human-readable label for the style.'),
                                'type' => 'string',
                            ],
                            'inline_style' => [
                                'description' => __('Inline CSS code that registers the CSS class required for the style.'),
                                'type' => 'string',
                            ],
                            'style_handle' => [
                                'description' => __('Contains the handle that defines the block style.'),
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'default' => [],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'variations' => [
                    'description' => __('Block variations.'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'description' => __('The unique and machine-readable name.'),
                                'type' => 'string',
                                'required' => true,
                            ],
                            'title' => [
                                'description' => __('A human-readable variation title.'),
                                'type' => 'string',
                                'required' => true,
                            ],
                            'description' => [
                                'description' => __('A detailed variation description.'),
                                'type' => 'string',
                                'required' => false,
                            ],
                            'category' => $category_definition,
                            'icon' => $icon_definition,
                            'isDefault' => [
                                'description' => __('Indicates whether the current variation is the default one.'),
                                'type' => 'boolean',
                                'required' => false,
                                'default' => false,
                            ],
                            'attributes' => [
                                'description' => __('The initial values for attributes.'),
                                'type' => 'object',
                            ],
                            'innerBlocks' => $inner_blocks_definition,
                            'example' => $example_definition,
                            'scope' => [
                                'description' => __('The list of scopes where the variation is applicable. When not provided, it assumes all available scopes.'),
                                'type' => ['array', 'null'],
                                'default' => null,
                                'items' => [
                                    'type' => 'string',
                                    'enum' => ['block', 'inserter', 'transform'],
                                ],
                                'readonly' => true,
                            ],
                            'keywords' => $keywords_definition,
                        ],
                    ],
                    'readonly' => true,
                    'context' => ['embed', 'view', 'edit'],
                    'default' => null,
                ],
                'textdomain' => [
                    'description' => __('Public text domain.'),
                    'type' => ['string', 'null'],
                    'default' => null,
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'parent' => [
                    'description' => __('Parent blocks.'),
                    'type' => ['array', 'null'],
                    'items' => [
                        'type' => 'string',
                        'pattern' => self::NAME_PATTERN,
                    ],
                    'default' => null,
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'ancestor' => [
                    'description' => __('Ancestor blocks.'),
                    'type' => ['array', 'null'],
                    'items' => [
                        'type' => 'string',
                        'pattern' => self::NAME_PATTERN,
                    ],
                    'default' => null,
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'allowed_blocks' => [
                    'description' => __('Allowed child block types.'),
                    'type' => ['array', 'null'],
                    'items' => [
                        'type' => 'string',
                        'pattern' => self::NAME_PATTERN,
                    ],
                    'default' => null,
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'keywords' => $keywords_definition,
                'example' => $example_definition,
                'block_hooks' => [
                    'description' => __('This block is automatically inserted near any occurrence of the block types used as keys of this map, into a relative position given by the corresponding value.'),
                    'type' => 'object',
                    'patternProperties' => [
                        self::NAME_PATTERN => [
                            'type' => 'string',
                            'enum' => ['before', 'after', 'first_child', 'last_child'],
                        ],
                    ],
                    'default' => [],
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
            ],
        ];

        // Properties deprecated in WordPress 6.1, but left in the schema for backwards compatibility.
        $deprecated_properties = [
            'editor_script' => [
                'description' => __('Editor script handle. DEPRECATED: Use `editor_script_handles` instead.'),
                'type' => ['string', 'null'],
                'default' => null,
                'context' => ['embed', 'view', 'edit'],
                'readonly' => true,
            ],
            'script' => [
                'description' => __('Public facing and editor script handle. DEPRECATED: Use `script_handles` instead.'),
                'type' => ['string', 'null'],
                'default' => null,
                'context' => ['embed', 'view', 'edit'],
                'readonly' => true,
            ],
            'view_script' => [
                'description' => __('Public facing script handle. DEPRECATED: Use `view_script_handles` instead.'),
                'type' => ['string', 'null'],
                'default' => null,
                'context' => ['embed', 'view', 'edit'],
                'readonly' => true,
            ],
            'editor_style' => [
                'description' => __('Editor style handle. DEPRECATED: Use `editor_style_handles` instead.'),
                'type' => ['string', 'null'],
                'default' => null,
                'context' => ['embed', 'view', 'edit'],
                'readonly' => true,
            ],
            'style' => [
                'description' => __('Public facing and editor style handle. DEPRECATED: Use `style_handles` instead.'),
                'type' => ['string', 'null'],
                'default' => null,
                'context' => ['embed', 'view', 'edit'],
                'readonly' => true,
            ],
        ];
        $this->schema['properties'] = array_merge($this->schema['properties'], $deprecated_properties);

        return $this->add_additional_fields_schema($this->schema);
    }

    /**
     * Retrieves the query params for collections.
     *
     * @since 5.5.0
     *
     * @return array Collection parameters.
     */
    public function get_collection_params()
    {
        return [
            'context' => $this->get_context_param(['default' => 'view']),
            'namespace' => [
                'description' => __('Block namespace.'),
                'type' => 'string',
            ],
        ];
    }
}
