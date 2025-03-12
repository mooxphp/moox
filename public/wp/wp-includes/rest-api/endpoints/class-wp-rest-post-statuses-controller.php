<?php

/**
 * REST API: WP_REST_Post_Statuses_Controller class
 *
 * @since 4.7.0
 */

/**
 * Core class used to access post statuses via the REST API.
 *
 * @since 4.7.0
 * @see WP_REST_Controller
 */
class WP_REST_Post_Statuses_Controller extends WP_REST_Controller
{
    /**
     * Constructor.
     *
     * @since 4.7.0
     */
    public function __construct()
    {
        $this->namespace = 'wp/v2';
        $this->rest_base = 'statuses';
    }

    /**
     * Registers the routes for post statuses.
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
            '/'.$this->rest_base.'/(?P<status>[\w-]+)',
            [
                'args' => [
                    'status' => [
                        'description' => __('An alphanumeric identifier for the status.'),
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
     * Checks whether a given request has permission to read post statuses.
     *
     * @since 4.7.0
     *
     * @param  WP_REST_Request  $request  Full details about the request.
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
                __('Sorry, you are not allowed to manage post statuses.'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }

    /**
     * Retrieves all post statuses, depending on user context.
     *
     * @since 4.7.0
     *
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request)
    {
        $data = [];
        $statuses = get_post_stati(['internal' => false], 'object');
        $statuses['trash'] = get_post_status_object('trash');

        foreach ($statuses as $obj) {
            $ret = $this->check_read_permission($obj);

            if (! $ret) {
                continue;
            }

            $status = $this->prepare_item_for_response($obj, $request);
            $data[$obj->name] = $this->prepare_response_for_collection($status);
        }

        return rest_ensure_response($data);
    }

    /**
     * Checks if a given request has access to read a post status.
     *
     * @since 4.7.0
     *
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_item_permissions_check($request)
    {
        $status = get_post_status_object($request['status']);

        if (empty($status)) {
            return new WP_Error(
                'rest_status_invalid',
                __('Invalid status.'),
                ['status' => 404]
            );
        }

        $check = $this->check_read_permission($status);

        if (! $check) {
            return new WP_Error(
                'rest_cannot_read_status',
                __('Cannot view status.'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }

    /**
     * Checks whether a given post status should be visible.
     *
     * @since 4.7.0
     *
     * @param  object  $status  Post status.
     * @return bool True if the post status is visible, otherwise false.
     */
    protected function check_read_permission($status)
    {
        if ($status->public === true) {
            return true;
        }

        if ($status->internal === false || $status->name === 'trash') {
            $types = get_post_types(['show_in_rest' => true], 'objects');

            foreach ($types as $type) {
                if (current_user_can($type->cap->edit_posts)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retrieves a specific post status.
     *
     * @since 4.7.0
     *
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item($request)
    {
        $obj = get_post_status_object($request['status']);

        if (empty($obj)) {
            return new WP_Error(
                'rest_status_invalid',
                __('Invalid status.'),
                ['status' => 404]
            );
        }

        $data = $this->prepare_item_for_response($obj, $request);

        return rest_ensure_response($data);
    }

    /**
     * Prepares a post status object for serialization.
     *
     * @since 4.7.0
     * @since 5.9.0 Renamed `$status` to `$item` to match parent class for PHP 8 named parameter support.
     *
     * @param  stdClass  $item  Post status data.
     * @param  WP_REST_Request  $request  Full details about the request.
     * @return WP_REST_Response Post status data.
     */
    public function prepare_item_for_response($item, $request)
    {
        // Restores the more descriptive, specific name for use within this method.
        $status = $item;

        $fields = $this->get_fields_for_response($request);
        $data = [];

        if (in_array('name', $fields, true)) {
            $data['name'] = $status->label;
        }

        if (in_array('private', $fields, true)) {
            $data['private'] = (bool) $status->private;
        }

        if (in_array('protected', $fields, true)) {
            $data['protected'] = (bool) $status->protected;
        }

        if (in_array('public', $fields, true)) {
            $data['public'] = (bool) $status->public;
        }

        if (in_array('queryable', $fields, true)) {
            $data['queryable'] = (bool) $status->publicly_queryable;
        }

        if (in_array('show_in_list', $fields, true)) {
            $data['show_in_list'] = (bool) $status->show_in_admin_all_list;
        }

        if (in_array('slug', $fields, true)) {
            $data['slug'] = $status->name;
        }

        if (in_array('date_floating', $fields, true)) {
            $data['date_floating'] = $status->date_floating;
        }

        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);

        $response = rest_ensure_response($data);

        $rest_url = rest_url(rest_get_route_for_post_type_items('post'));
        if ($status->name === 'publish') {
            $response->add_link('archives', $rest_url);
        } else {
            $response->add_link('archives', add_query_arg('status', $status->name, $rest_url));
        }

        /**
         * Filters a post status returned from the REST API.
         *
         * Allows modification of the status data right before it is returned.
         *
         * @since 4.7.0
         *
         * @param  WP_REST_Response  $response  The response object.
         * @param  object  $status  The original post status object.
         * @param  WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('rest_prepare_status', $response, $status, $request);
    }

    /**
     * Retrieves the post status' schema, conforming to JSON Schema.
     *
     * @since 4.7.0
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
            'title' => 'status',
            'type' => 'object',
            'properties' => [
                'name' => [
                    'description' => __('The title for the status.'),
                    'type' => 'string',
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'private' => [
                    'description' => __('Whether posts with this status should be private.'),
                    'type' => 'boolean',
                    'context' => ['edit'],
                    'readonly' => true,
                ],
                'protected' => [
                    'description' => __('Whether posts with this status should be protected.'),
                    'type' => 'boolean',
                    'context' => ['edit'],
                    'readonly' => true,
                ],
                'public' => [
                    'description' => __('Whether posts of this status should be shown in the front end of the site.'),
                    'type' => 'boolean',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'queryable' => [
                    'description' => __('Whether posts with this status should be publicly-queryable.'),
                    'type' => 'boolean',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'show_in_list' => [
                    'description' => __('Whether to include posts in the edit listing for their post type.'),
                    'type' => 'boolean',
                    'context' => ['edit'],
                    'readonly' => true,
                ],
                'slug' => [
                    'description' => __('An alphanumeric identifier for the status.'),
                    'type' => 'string',
                    'context' => ['embed', 'view', 'edit'],
                    'readonly' => true,
                ],
                'date_floating' => [
                    'description' => __('Whether posts of this status may have floating published dates.'),
                    'type' => 'boolean',
                    'context' => ['view', 'edit'],
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
