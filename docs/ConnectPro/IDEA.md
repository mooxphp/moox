# Idea

Moox Connect is a Filament package that allows managing REST and GraphQL API endpoints, synchronizing data from APIs to a database, and providing Filament-powered UI for administration.

---

### Config

-   Common limits, like for chaining, batches
-   Error status rules, after how many fails shoud an API go into error state?
-   Registration of Transformers?
-   Invalidation ruleset, maybe not needed
-   Error notifications for Jobs, Endpoints, APIs, stale data

### **Core Components**

#### **1. Base Classes**

-   **Abstract API Service** (`BaseApiService`)
    -   Handles authentication, request execution, and response handling.
    -   Uses HTTP client
    -   Implements common methods like `get()`, `post()`, `put()`, `delete()`, and `patch()`.
-   **Model Integration Layer**
    -   Abstract `BaseApiModel` to act as a bridge between API responses and Eloquent models.
    -   Defines mappings between API response fields and database attributes.

#### **2. Database-Driven API Management**

-   **API Configuration Model** (`ApiConnection`)
    -   Stores API base URL, authentication details, headers, rate limits.
    -   Supports both REST and GraphQL APIs through an `api_type` field.
    -   Stores `lang_param` (name of the language parameter for API calls, e.g., `lang`, `locale`, `language`).
    -   Stores `default_locale` (default language to be used in API requests).
-   **Endpoint Model** (`ApiEndpoint`)
    -   Stores endpoint paths, HTTP methods (for REST) or queries/mutations (for GraphQL), and expected responses.
    -   Allows dynamic configuration via Filament.
    -   Uses Filament Repeater to define field mappings for syncing API responses to database fields.
    -   Allows per-endpoint **language overrides** (e.g., different languages per request).
-   **Sync Job Model** (`ApiSyncJob`)
    -   Tracks API sync tasks and their statuses.
    -   Stores last sync timestamp.
    -   Can process multiple endpoints in a given order.
    -   Supports **Chained Sync Jobs**, allowing one API value to be used in another sync job.
    -   Stores scheduled API call configurations.

#### **3. Queueable Jobs for API Sync**

-   **Job Classes** (`SyncApiDataJob`)
    -   Fetches API data, transforms it, and stores it in the database.
    -   Supports pagination and rate-limiting mechanisms.
    -   Executes multiple endpoints in a predefined order.
    -   Ensures language parameter is passed in each request dynamically.
    -   Implements **Chained Sync Jobs**, passing API values from one request to another.
    -   Runs periodic API calls to fetch data.

#### **4. Filament Resources for API Management**

-   **Dashboard**
    -   Widgets for Connected APIs, Failed APIs, Unused
    -   Widgets for Failed Jobs, Running, Succeeded / 24 hrs / week
    -   Table with API Connections and Status
    -   Last errors table
-   **API Connection Resource**
    -   UI to manage API credentials, base URLs, headers.
    -   Allows selection between REST and GraphQL.
    -   Enables configuration of `lang_param` and `default_locale`.
-   **API Endpoint Resource**
    -   Define and edit endpoints dynamically.
    -   Associate with `ApiConnection`.
    -   Includes a Filament Repeater field to map API response fields to database columns.
    -   Allows defining per-endpoint language overrides.
    -   Allows defining dynamic endpoint urls with parameters
-   **API Logs Resource**
    -   Stores request logs, errors, and execution times.
-   **Sync Job Resource**
    -   Monitor queued sync jobs and their statuses.
    -   Allows defining multiple endpoints per sync job with execution order.
    -   Supports **Chained Sync Jobs** to pass data between requests dynamically.
    -   Allows scheduling of specific Jobs at defined intervals.

### Chained Sync Jobs

Some APIs require fetching an initial list of **IDs**, then making follow-up requests to retrieve detailed data. `Chained Sync Jobs` handle this by:

-   Fetching a list of IDs → `GET /articles` → Response: `[1, 2, 3]`
-   Passing these IDs to a follow-up request → `GET /articles/{id}/details`
-   Storing the detailed data in the database.

#### **Implementation**

-   `ApiSyncJob` stores **intermediate API results** (e.g., IDs) temporarily.
-   A dependent sync job retrieves **detailed data** using the stored values.
-   The **execution chain is automatic**, reducing redundant API calls.

### Transformers

Built in Transformers and registration of custom transformers.

Needs to be documented.

---

### **Database Models (Short List with Fields)**

1. **ApiConnection** – Stores API configurations including:

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
-   `created_at` (timestamp)
-   `updated_at` (timestamp)

2. **ApiEndpoint** – Defines API endpoints, methods (REST) or queries/mutations (GraphQL), and expected responses.

-   `id` (Primary Key)
-   `name` (string) - Human-readable name for the API endpoint
-   `api_connection_id` (Foreign Key) - Links to `ApiConnection`
-   `path` (string, nullable) - API endpoint path (for REST, e.g., `/users`)
-   `method` (enum, nullable) - HTTP method (GET, POST, PUT, DELETE, PATCH) for REST APIs
-   `query` (text, nullable) - GraphQL query or mutation
-   `variables` (json, nullable) - JSON object of GraphQL variables
-   `response_map` (json, nullable) - Defines how to extract and store fields from the GraphQL response
-   `expected_response` (json) - Schema of the expected response
-   `lang_override` (string, nullable) - Optional override for language in this specific endpoint
-   `created_at` (timestamp)
-   `updated_at` (timestamp)

3. **ApiSyncJob** – Define and track API synchronization jobs and their execution statuses.

-   `id` (Primary Key)
-   `name` (string) - Human-readable name for the API sync job
-   `api_endpoint_ids` (json) - Array of API Endpoints to process in order
-   `status` (enum) - Status of sync (pending, running, completed, failed)
-   `last_sync_at` (timestamp) - Last successful sync timestamp
-   `field_mappings` (json, nullable) - Stores Filament Repeater mappings for syncing API data to DB fields- but I would do that in the job
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
-   `retry_delay` (integer, default 300 sec)
-   `notify_on_failure` (boolean, default: `true`)
-   `created_at` (timestamp)
-   `updated_at` (timestamp)

4. Logs

-   `id` (Primary Key)
-   `name` (string) - Human-readable name for the API sync job
-   `api_sync_job_id` (foreign_key)
-   `request_data`
-   `response_data`
-   `status_code`
-   `error_message`
-   `created_at` (timestamp)

````json
// chained parameters
[
  {
    "source_field": "data[].id",
    "target_param": "{id}"
  }
]```


---

### **Development Roadmap**

1. [x] **Build Moox Connect package**
3. [ ] **Build Filament Resources**
2. [ ] **Build Base API Service & Eloquent Integration**
4. [ ] **Develop API Sync Jobs**
6. [ ] **Testing & Documentation**

---

### **Notes**

- Add MultiAuth for heco GraphQL and REST authentication
- Add **automatic token refresh handling**
- Ensure proper handling for switching between REST and GraphQL in the request execution logic.
- Implement Filament Repeater for user-friendly field mapping when defining API Endpoints.
- Implement **Chained Sync Jobs** for multi-step API workflows.
- Support multiple endpoints in `ApiSyncJob` with defined execution order.
- Implement `lang_param` and `default_locale` support in API requests.
- Consider adding webhook support for real-time updates from APIs.

```php
// Multiauth

'auth' => [
    'primary' => [
        'type' => 'graphql',
        'mutations' => [
            'login' => 'mutation login($args: LoginInput!) { ... }',
            'refresh' => 'mutation refreshToken { ... }',
            'validate' => 'mutation validateToken { ... }'
        ]
    ],
    'secondary' => [
        'type' => 'rest',
        'token_type' => 'Bearer',
        'inherit_from' => 'primary'
    ]
]
````
