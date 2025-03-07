# Database Models

We document each model including all fields, relationships and what they are used for.

## ApiConnection

Stores API configurations including:

-   `id` (Primary Key)
-   `name` (string) - Human-readable name for the API connection
-   `base_url` (string) - Base URL of the API
-   `api_type` (enum) - Defines API type (`REST`, `GraphQL`)
-   `auth_type` (enum) - Authentication type (e.g., Bearer, Basic, OAuth)
-   `auth_credentials` (json) - Stores API keys, tokens, or credentials
-   `headers` (json) - Default headers to be sent with requests
-   `rate_limit` (integer) - Maximum requests per minute/hour
-   `lang_param` (string) - Parameter name used for language selection
-   `default_locale` (string) - Default language to be used in API calls
-   `status` (enum) - New, Unused, Active, Error and Disabled
-   `notify_on_failure` (boolean, default: `true`)
-   `notify_on_error` (boolean, default: `true`)
-   `notify_email` (string, nullable) - Email to notify on failure
-   `last_used` (timestamp) - Last time the API was used
-   `last_error` (timestamp) - Last time the API had an error
-   `error_window` (integer, default: 3600) - Seconds after which we should reset the error count
-   `error_count` (integer, default: 0) - Count of errors per error_window
-   `error_limit` (integer, default: 10) - Count of errors before we disable an API
-   `created_at` (timestamp)
-   `updated_at` (timestamp)

## ApiEndpoint

API endpoints, methods (REST), queries/mutations (GraphQL), expected responses:

-   `id` (Primary Key)
-   `name` (string) - Human-readable name for the API endpoint
-   `direct_access` (boolean, default: false) - Allow direct API calls
-   `api_connection_id` (Foreign Key) - Links to `ApiConnection`
-   `path` (string, nullable) - API endpoint path (for REST, e.g., `/users`)
-   `method` (enum, nullable) - HTTP method (GET, POST, PUT, DELETE, PATCH) for REST APIs
-   `query` (text, nullable) - GraphQL query or mutation
-   `variables` (json, nullable) - JSON object of GraphQL variables
-   `response_map` (json, nullable) - Defines how to extract and store fields from the GraphQL response
-   `expected_response` (json) - Schema of the expected response
-   `field_mappings` (json, nullable) - Stores Filament Repeater mappings for syncing API data to DB fields
-   `lang_override` (string, nullable) - Optional override for language in this specific endpoint
-   `rate_limit` (integer, nullable) - Custom rate limit for this endpoint
-   `transformers` (json, nullable) - Transformers for this endpoint
-   `rate_window` (integer, nullable) - Custom time window for rate limiting
-   `status` (enum) - New, Unused, Active, Error and Disabled
-   `last_used` (timestamp) - Last time the API was used
-   `last_error` (timestamp) - Last time the API had an error
-   `error_count` (integer, default: 0) - Count of errors
-   `timeout` (integer, default: 30) - Request timeout in seconds
-   `created_at` (timestamp)
-   `updated_at` (timestamp)

## ApiSyncJob

Define and track API synchronization jobs and their execution statuses:

-   `id` (Primary Key)
-   `name` (string) - Human-readable name for the API sync job
-   `direct_access` (boolean, default: false) - Allow direct job execution
-   `api_endpoint_ids` (json) - Array of API Endpoints to process in order
-   `status` (enum) - Status of sync (pending, running, completed, failed)
-   `last_sync_at` (timestamp) - Last successful sync timestamp
-   `chained_job_id`(foreign_key, nulable) - Links to another `ApiSyncJob` for chaining
-   `chained_parameters`(json) - stores the source fields to target params
-   `batch`(bool) - create a batch
-   `batch_size`(int) - create a batch
-   `batch_mode`(enum: `sequential`, `parallel`)
-   `batch_delay` (integer, nullable) - seconds
-   `freshness_ttl` (int) - e. g. 86400
-   `last_fresh_at` (timestamp) - updated every time the job successfully completes
-   `auto_refresh`(bool) - should the job be called from the frontend when stale
-   `auto_refresh_mode`(bool) - queue / sync
-   `max_retries` (integer) - after how many errors should we stay in error state
-   `retry_strategy` (enum: `fixed`, `exponential`, `linear`)
-   `retry_delay` (integer, default 60 sec)
-   `notify_on_failure` (boolean, default: `true`)
-   `timeout` (integer, default: 300) - Total job timeout
-   `created_at` (timestamp)
-   `updated_at` (timestamp)

### Chained Parameters

```json
[
    {
        "source_field": "data[].id",
        "target_param": "{id}"
    }
]
```

## ApiLog

Logs for API sync jobs and direct API calls:

-   `id` (Primary Key)
-   `name` (string) - Human-readable name for the API sync job
-   `api_sync_job_id` (foreign_key)
-   `request_data`
-   `response_data`
-   `status_code`
-   `error_message`
-   `created_at` (timestamp)
