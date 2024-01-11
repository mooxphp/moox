<?php
/**
 * REST API: WP_REST_Post_Types_Controller class
 *
 * @since 4.7.0
 */

/**
 * Core class to access post types via the REST API.
 *
 * @since 4.7.0
 * @see WP_REST_Controller
 */
class WP_REST_Post_Types_Controller extends WP_REST_Controller
{
    /**
     * Constructor.
     *
     * @since 4.7.0
     */
    public function __construct()
    {
        $this->namespace = 'wp/v2';
        $this->rest_base = 'types';
    }

    /**
     * Registers the routes for post types.
     *
     * @since 4.7.0
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
            '/'.$this->rest_base.'/(?P<type>[\w-]+)',
            [
                'args' => [
                    'type' => [
                        'description' => __('An alphanumeric identifier for the post type.'),
                        'type' => 'string',
                    ],
                ],
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'context' => $this->get_context_param(['default' => 'view']),
                    ],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );
    }

    /**
     * Checks whether a given request has permission to read types.
     *
     * @since 4.7.0
     *
     * @param  WP_REST_Request  $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check($request)
    {
        if ($request['context'] === 'edit') {
            $types = get_post_types(['show_in_rest' => true], 'objects');

            foreach ($types as $type) {
                if (current_user_can($type->cap->edit_posts)) {
                    return true;
                }
            }

            return new WP_Error(
                'rest_cannot_view',
                __('Sorry, you are not allowed to edit posts in this post type.'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }

    /**
     * Retrieves all public post types.
     *
     * @since 4.7.0
     *
     * @param  WP_REST_Request  $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request)
    {
        $data = [];
        $types = get_post_types(['show_in_rest' => true], 'objects');

        foreach ($types as $type) {
            if ($request['context'] === 'edit' && ! current_user_can($type->cap->edit_posts)) {
                continue;
            }

            $post_type = $this->prepare_item_for_response($type, $request);
            $data[$type->name] = $this->prepare_response_for_collection($post_type);
        }

        return rest_ensure_response($data);
    }

    /**
     * Retrieves a specific post type.
     *
     * @since 4.7.0
     *
     * @param  WP_REST_Request  $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item($request)
    {
        $obj = get_post_type_object($request['type']);

        if (empty($obj)) {
            return new WP_Error(
                'rest_type_invalid',
                __('Invalid post type.'),
                ['status' => 404]
            );
        }

        if (empty($obj->show_in_rest)) {
            return new WP_Error(
                'rest_cannot_read_type',
                __('Cannot view post type.'),
                ['status' => rest_authorization_required_code()]
            );
        }

        if ($request['context'] === 'edit' && ! current_user_can($obj->cap->edit_posts)) {
            return new WP_Error(
                'rest_forbidden_context',
                __('Sorry, you are not allowed to edit posts in this post type.'),
                ['status' => rest_authorization_required_code()]
            );
        }

        $data = $this->prepare_item_for_response($obj, $request);

        return rest_ensure_response($data);
    }

    /**
     * Prepares a post type object for serialization.
     *
     * @since 4.7.0
     * @since 5.9.0 Renamed `$post_type` to `$item` to match parent class for PHP 8 named parameter support.
     *
     * @param  WP_Post_Type  $item    Post type object.
     * @param  WP_REST_Request  $request Full details about the request.
     * @return WP_REST_Response Response object.
     */
    public function prepare_item_for_response($item, $request)
    {
        // Restores the more descriptive, specific name for use within this method.
        $post_type = $item;

        $taxonomies = wp_list_filter(get_object_taxonomies($post_type->name, 'objects'), ['show_in_rest' => true]);
        $taxonomies = wp_list_pluck($taxonomies, 'name');
        $base = ! empty($post_type->rest_base) ? $post_type->rest_base : $post_type->name;
        $namespace = ! empty($post_type->rest_namespace) ? $post_type->rest_namespace : 'wp/v2';
        $supports = get_all_post_type_supports($post_type->name);

        $fields = $this->get_fields_for_response($request);
        $data = [];

        if (rest_is_field_included('capabilities', $fields)) {
            $data['capabilities'] = $post_type->cap;
        }

        if (rest_is_field_included('description', $fields)) {
            $data['description'] = $post_type->description;
        }

        if (rest_is_field_included('hierarchical', $fields)) {
            $data['hierarchical'] = $post_type->hierarchical;
        }

        if (rest_is_field_included('has_archive', $fields)) {
            $data['has_archive'] = $post_type->has_archive;
        }

        if (rest_is_field_included('visibility', $fields)) {
            $data['visibility'] = [
                'show_in_nav_menus' => (bool) $post_type->show_in_nav_menus,
                'show_ui' => (bool) $post_type->show_ui,
            ];
        }

        if (rest_is_field_included('viewable', $fields)) {
            $data['viewable'] = is_post_type_viewable($post_type);
        }

        if (rest_is_field_included('labels', $fields)) {
            $data['labels'] = $post_type->labels;
        }

        if (rest_is_field_included('name', $fields)) {
            $data['name'] = $post_type->label;
        }

        if (rest_is_field_included('slug', $fields)) {
            $data['slug'] = $post_type->name;
        }

        if (rest_is_field_included('icon', $fields)) {
            $data['icon'] = $post_type->menu_icon;
        }

        if (rest_is_field_included('supports', $fields)) {
            $data['supports'] = $supports;
        }

        if (rest_is_field_included('taxonomies', $fields)) {
            $data['taxonomies'] = array_values($taxonomies);
        }

        if (rest_is_field_included('rest_base', $fields)) {
            $data['rest_base'] = $base;
        }

        if (rest_is_field_included('rest_namespace', $fields)) {
            $data['rest_namespace'] = $namespace;
        }

        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);

        // Wrap the data in a response object.
        $response = rest_ensure_response($data);

        if (rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields)) {
            $response->add_links($this->prepare_links($post_type));
        }

        /**
         * Filters a post type returned from the REST API.
         *
         * Allows modification of the post type data right before it is returned.
         *
         * @since 4.7.0
         *
         * @param  WP_REST_Response  $response  The response object.
         * @param  WP_Post_Type  $post_type The original post type object.
         * @param  WP_REST_Request  $request   Request used to generate the response.
         */
        return apply_filters('rest_prepare_post_type', $response, $post_type, $request);
    }

    /**
     * Prepares links for the request.
     *
     * @since 6.1.0
     *
     * @param  WP_Post_Type  $post_type The post type.
     * @return array Links for the given post type.
     */
    protected function prepare_links($post_type)
    {
        return [
            'collection' => [
                'href' => rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base)),
            ],
            'https://api.w.org/items' => [
                'href' => rest_url(rest_get_route_for_post_type_items($post_type->name)),
            ],
        ];
    }

    /**
     * Retrieves the post type's schema, conforming to JSON Schema.
     *
     * @since 4.7.0
     * @since 4.8.0 The `supports` property was added.
     * @since 5.9.0 The `visibility` and `rest_namespace` properties were added.
     * @since 6.1.0 The `icon` property was added.
     *
     * @return array Item schema data.
     */
    public function get_item_schema()
    {
        if ($this->schema) {
            return $this->add_additional_fields_schema($this->schema);
        }

        $schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'type',
            'type' => 'object',
            'properties' => [
                'capabilities' => [
                    'description' => __('All capabilities used by the post type.'),
                    'type' => 'object',
                    'context' => ['edit'],
                    'readonly' => true,
                ],
                'description' => [
                    'description' => __('A human-readable description of the post type.'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'hierarchical' => [
                    'description' => __('Whether or not the post type should have children.'),
                    'type' => 'boolean',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'viewable' => [
                    'description' => __('Whether or not the post type can be viewed.'),
                    'type' => 'boolean',
                    'context' => ['edit'],
                    'readonly' => true,
                ],
                'labels' => [
                    'description' => __('Human-readable labels for the post type for various contexts.'),
                    'type' => 'object',
                    'context' => ['edit'],
                    'readonly' => true,
                ],
                'name' => [
                    'description' => __('The title for the post type.'),
                    'type' => 'string',
                    'context' => ['view', 'edit', 'embed'],
                    'readonly' => true,
                ],
                'slug' => [
                    'description' => __('An alphanumeric identifier for the post type.'),
                    'type' => 'string',
                    'context' => ['view', 'edit', 'embed'],
                    'readonly' => true,
                ],
                'supports' => [
                    'description' => __('All features, supported by the post type.'),
                    'type' => 'object',
                    'context' => ['edit'],
                    'readonly' => true,
                ],
                'has_archive' => [
                    'description' => __('If the value is a string, the value will be used as the archive slug. If the value is false the post type has no archive.'),
                    'type' => ['string', 'boolean'],
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'taxonomies' => [
                    'description' => __('Taxonomies associated with post type.'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'rest_base' => [
                    'description' => __('REST base route for the post type.'),
                    'type' => 'string',
                    'context' => ['view', 'edit', 'embed'],
                    'readonly' => true,
                ],
                'rest_namespace' => [
                    'description' => __('REST route\'s namespace for the post type.'),
                    'type' => 'string',
                    'context' => ['view', 'edit', 'embed'],
                    'readonly' => true,
                ],
                'visibility' => [
                    'description' => __('The visibility settings for the post type.'),
                    'type' => 'object',
                    'context' => ['edit'],
                    'readonly' => true,
                    'properties' => [
                        'show_ui' => [
                            'description' => __('Whether to generate a default UI for managing this post type.'),
                            'type' => 'boolean',
                        ],
                        'show_in_nav_menus' => [
                            'description' => __('Whether to make the post type available for selection in navigation menus.'),
                            'type' => 'boolean',
                        ],
                    ],
                ],
                'icon' => [
                    'description' => __('The icon for the post type.'),
                    'type' => ['string', 'null'],
                    'context' => ['view', 'edit', 'embed'],
                    'readonly' => true,
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema($this->schema);
    }

    /**
     * Retrieves the query params for collections.
     *
     * @since 4.7.0
     *
     * @return array Collection parameters.
     */
    public function get_collection_params()
    {
        return [
            'context' => $this->get_context_param(['default' => 'view']),
        ];
    }
}
