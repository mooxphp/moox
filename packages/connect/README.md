# Moox Connect

API connection management, endpoint configuration, fetch jobs, import record storage, and destination model writes. Includes a Filament admin UI.

**Separate from `moox/transform`:** Connect has its own field-mapping pipeline (`TransformImportRecordsJob`) that writes directly to `destination_model` on endpoints. Transform is a standalone package for declarative definitions, validation, and bulk/expand runs. An app can use both: Connect fetches → Transform maps.

## Table of contents

- [Database tables](#database-tables)
- [What this package provides](#what-this-package-provides)
- [What this package does not provide](#what-this-package-does-not-provide)
- [Sync pipeline](#sync-pipeline-summary)
- [Installation](#installation)
- [Configuration](#configuration-configconnectphp)
- [Usage](#usage)
- [App responsibilities](#app-responsibilities)
- [Database models](#database-models)
- [Package structure (class reference)](#package-structure-class-reference)
- [Tests](#tests)
- [License](#license)

## Database tables

| Table | Purpose |
|-------|---------|
| `api_connections` | Base URL, auth, rate limits, health |
| `api_endpoints` | Paths, methods, mappings, parent/child trees |
| `api_logs` | Request/response log per call |
| `api_import_records` | Stored API payloads + status |
| `api_import_payload_chunks` | Chunked large payloads |

## What this package provides

### API execution

- REST and GraphQL requests
- Auth: Basic, Bearer, OAuth2, JWT, Multi-Auth
- Token refresh (where the auth strategy supports it)
- Rate limiting with retry (global, per endpoint, per job)
- Connection health checks

### Sync orchestration

- `RunEndpointJob` — fetch one endpoint, store import record
- `RunConnectionTreeJob` — BFS tree of parent/child endpoints
- `RunDetailForListJob` — list → detail expansion
- `RunEndpointForItemJob` — per-item detail requests
- `TransformImportRecordsJob` — map import records to `destination_model`

### Endpoint configuration (Filament)

- Parent/child endpoint relationships
- List item extraction (`list_item_path`, `list_id_key`)
- External key and scope (`external_key_field`, `sync_scope_fields`, `sync_scope_hash`)
- Field mappings, key fields, transformers
- Sync modes: `append` (upsert only) or `sync` (upsert + prune missing)

### Built-in value transformers

- Array, DateTime, JSON, Number
- Custom transformers via `TransformerRegistry`

### Observability

- `api_logs` per HTTP call
- Import record status tracking
- Debug HTML view for import records (`/connect/import-records/{externalKey}`)
- Email notifications on failures (configurable)

### Scopes

`sync_scope_fields` defines values inside list items used to compute `sync_scope_hash`. Used for list→detail grouping, transformation claiming, and sync pruning.

## Import record statuses

Common values: `new`, `fetched`, `processing`, `update`, `processed`, `failed`.

## What this package does not provide

- **No declarative transform definitions** — use `moox/transform` for `TransformDefinition`, expand/locales/bulk, and `field_map` expressions
- **No product/catalog models** — `destination_model` is configured per endpoint in the app
- **No ERP-specific endpoints or field names** — all mapping is config/DB driven
- **No automatic Connect → Transform bridge** — app must bind `ImportRecordPayloadReader` in transform config if you want Transform to read Connect records
- **No generic binary attachment parsing** — `binary_preview` field keys are configurable; app-specific keys (e.g. `AttachmentFileName`) belong in `config/connect.php`
- **Filament panel name hardcoded** — debug routes use `panel:admin` middleware

## Sync pipeline (summary)

1. **Orchestration** — `RunConnectionTreeJob` runs endpoint tree levels as queue batches
2. **Fetch** — `ApiEndpointRunner` calls API, writes `api_import_records` (+ chunks)
3. **List → detail** — child jobs from parent payload via `EndpointListToDetailOrchestrator`
4. **Transform** — `TransformImportRecordsJob` maps payload → `destination_model`, updates status
5. **Prune** — when `sync_mode = sync`, stale scoped records removed after successful batch
6. **Log** — every request in `api_logs`

## Installation

```bash
composer require moox/connect
php artisan connect:install
```

Or manually:

```bash
php artisan vendor:publish --tag=connect-migrations
php artisan migrate
php artisan vendor:publish --tag=connect-config
```

## Configuration (`config/connect.php`)

```php
return [
    'enable-panel' => env('CONNECT_ENABLE_PANEL', true),

    'binary_preview' => [
        'file_name_keys' => ['file_name', 'filename', 'FileName'],
        'base64_keys' => ['body', 'base64'],
    ],

    'notifications' => [
        'email' => env('MAIL_TO_ADDRESS'),
    ],

    'rate_limits' => [
        // global, per_endpoint, per_job
    ],
];
```

App-specific preview field names (e.g. vendor-specific attachment keys) go in the **app** `config/connect.php`.

## Usage

1. Create `ApiConnection` (URL, auth, activate)
2. Create `ApiEndpoint`(s) with path, mappings, optional parent endpoint
3. Run sync from Filament or dispatch jobs:

```php
use Moox\Connect\Jobs\RunEndpointJob;
use Moox\Connect\Jobs\RunConnectionTreeJob;

RunEndpointJob::dispatch($endpointId);
RunConnectionTreeJob::dispatch($apiConnectionId);
```

## App responsibilities

- Define endpoints, field mappings, and destination models
- Configure `binary_preview` keys for vendor-specific payloads
- Wire Connect import records to Transform (if used):

```php
// config/transform.php
'import_record_payload_reader' => YourImportRecordPayloadReader::class,
'import_record_model' => ApiImportRecord::class,
```

---

## Database models

Each model including fields, relationships, and usage. Current schema is from package migrations; legacy/planned fields from earlier documentation are preserved in [Legacy model documentation](#legacy-model-documentation).

### ApiConnection

Stores API configurations.

**Relationships:** `endpoints()` → `ApiEndpoint`, `logs()` → `ApiLog`

**Current fields (migration):**

| Field | Type | Description |
|-------|------|-------------|
| `id` | PK | Primary key |
| `name` | string | Human-readable name |
| `base_url` | string | Base URL of the API |
| `health_path` | string, nullable | Path for health checks |
| `api_type` | enum | `REST`, `GraphQL` |
| `auth_type` | enum | `Bearer`, `Basic`, `OAuth`, `None`, `JWT` |
| `login_method` | string | Default `none` |
| `auth_credentials` | encrypted text | API keys, tokens, credentials |
| `headers` | json, nullable | Default request headers |
| `rate_limit` | integer, nullable | Max requests per window |
| `lang_param` | string, nullable | Parameter name for language selection |
| `default_locale` | string, nullable | Default language for API calls |
| `status` | enum | `New`, `Unused`, `Active`, `Error`, `Disabled` |
| `notify_on_failure` | enum | Notification on failure |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

### ApiEndpoint

API endpoints: methods (REST), queries/mutations (GraphQL), mappings, sync configuration.

**Relationships:** `apiConnection()` → `ApiConnection`, `parentEndpoint()` → self, `importRecords()` → `ApiImportRecord`

**Current fields (migration):**

| Field | Type | Description |
|-------|------|-------------|
| `id` | PK | Primary key |
| `name` | string | Human-readable name |
| `api_connection_id` | FK | Links to `api_connections` |
| `path` | string | API endpoint path (REST, e.g. `/users`) |
| `method` | string | HTTP method (GET, POST, PUT, DELETE, PATCH) |
| `direct_access` | boolean | Allow direct API calls |
| `variables` | json, nullable | GraphQL variables |
| `response_map` | json, nullable | Extract fields from GraphQL response |
| `expected_response` | json | Schema of expected response |
| `field_mappings` | json, nullable | Filament Repeater mappings for sync to DB |
| `transformers` | json, nullable | Transformers for this endpoint |
| `lang_override` | string, nullable | Override language for this endpoint |
| `rate_limit` | integer, nullable | Custom rate limit |
| `rate_window` | integer, nullable | Custom rate limit window |
| `status` | enum | `new`, `unused`, `active`, `error`, `disabled` |
| `timeout` | integer | Request timeout in seconds |
| `destination_model` | string, nullable | FQCN of target model (e.g. `App\Models\Product`) |
| `key_fields` | json, nullable | External key → internal column mapping |
| `external_key_field` | string, nullable | Payload field stored as `external_key` on import records |
| `list_item_path` | string, nullable | Dot path to list items in response |
| `list_id_key` | string, nullable | Field used as ID within list items |
| `parent_endpoint_id` | FK, nullable | Parent for list→detail trees |
| `route_param_key` | string, nullable | Route parameter name for detail requests |
| `variable_key` | string, nullable | GraphQL variable name for detail requests |
| `sync_mode` | string | `append` (upsert only) or `sync` (upsert + prune) |
| `sync_scope_fields` | json, nullable | Fields used to compute `sync_scope_hash` |
| `purge_after_days` | integer, nullable | Retention for import records |
| `options` | json, nullable | Endpoint-specific options |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Legacy fields (earlier documentation, not in current migration):**

- `query` (text, nullable) — GraphQL query or mutation (may be stored in `options` or path in current usage)
- `last_used`, `last_error`, `error_count` — tracked via `ApiStatusManager` cache in some flows

### ApiImportRecord

Stored API payloads and processing status.

**Relationships:** `apiConnection()` → `ApiConnection`, `apiEndpoint()` → `ApiEndpoint`

| Field | Type | Description |
|-------|------|-------------|
| `id` | PK | Primary key |
| `api_connection_id` | FK | Links to `api_connections` |
| `api_endpoint_id` | FK | Links to `api_endpoints` |
| `external_key` | string, nullable | Unique key from source (indexed) |
| `sync_scope_hash` | string(64), nullable | Scope for sync pruning / grouping |
| `sync_batch_id` | uuid, nullable | Batch identifier for sync runs |
| `payload` | json | Raw API data |
| `payload_hash` | string(64) | Checksum for change detection |
| `status` | enum | `new`, `fetched`, `processing`, `update`, `processed`, `failed` |
| `error_message` | text, nullable | Failure details |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

### ApiImportPayloadChunk

Chunked storage for large import payloads.

**Relationships:** `importRecord()` → `ApiImportRecord`

| Field | Type | Description |
|-------|------|-------------|
| `id` | PK | Primary key |
| `api_import_record_id` | FK | Parent import record (cascade delete) |
| `chunk_index` | unsigned int | Order within record (unique per record) |
| `payload_chunk` | longText | JSON or text chunk |
| `items_count` | unsigned int, nullable | Items in chunk |
| `bytes_size` | unsigned bigint, nullable | Chunk size in bytes |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

### ApiLog

Logs for API sync jobs and direct API calls.

**Relationships:** `apiConnection()` → `ApiConnection`, `endpoint()` → `ApiEndpoint`

**Current fields (migration):**

| Field | Type | Description |
|-------|------|-------------|
| `id` | PK | Primary key |
| `api_connection_id` | FK | Links to `api_connections` |
| `endpoint_id` | integer, nullable | Links to `api_endpoints` |
| `trigger` | enum | `CRON`, `USER`, `WEBHOOK`, `SYSTEM` |
| `request_data` | json | Request payload |
| `response_data` | json, nullable | Response payload |
| `status_code` | string | HTTP status |
| `error_message` | string, nullable | Error text |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Legacy fields (earlier documentation, not in current migration):**

- `name` (string) — human-readable name for the sync job
- `api_sync_job_id` (foreign key) — see [ApiSyncJob (legacy)](#apisyncjob-legacy)

### Legacy model documentation

The following was documented in earlier package specs. These models/tables are **not** part of the current implementation; orchestration uses queue jobs instead (`RunEndpointJob`, `RunConnectionTreeJob`, etc.).

#### ApiSyncJob (legacy)

Define and track API synchronization jobs and their execution statuses:

- `id` (Primary Key)
- `name` (string) — Human-readable name for the API sync job
- `direct_access` (boolean, default: false) — Allow direct job execution
- `api_endpoint_ids` (json) — Array of API Endpoints to process in order
- `status` (enum) — Status of sync (pending, running, completed, failed)
- `last_sync_at` (timestamp) — Last successful sync timestamp
- `chained_job_id` (foreign_key, nullable) — Links to another `ApiSyncJob` for chaining
- `chained_parameters` (json) — Stores the source fields to target params
- `batch` (bool) — create a batch
- `batch_size` (int) — batch size
- `batch_mode` (enum: `sequential`, `parallel`)
- `batch_delay` (integer, nullable) — seconds
- `freshness_ttl` (int) — e.g. 86400
- `last_fresh_at` (timestamp) — updated every time the job successfully completes
- `auto_refresh` (bool) — should the job be called from the frontend when stale
- `auto_refresh_mode` (bool) — queue / sync
- `max_retries` (integer) — after how many errors should we stay in error state
- `retry_strategy` (enum: `fixed`, `exponential`, `linear`)
- `retry_delay` (integer, default 60 sec)
- `notify_on_failure` (boolean, default: `true`)
- `timeout` (integer, default: 300) — Total job timeout
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Chained parameters example:**

```json
[
    {
        "source_field": "data[].id",
        "target_param": "{id}"
    }
]
```

#### ApiConnection (legacy fields)

Additional fields from earlier documentation not present in current migrations:

- `notify_on_error` (boolean, default: `true`)
- `notify_email` (string, nullable) — Email to notify on failure
- `last_used` (timestamp) — Last time the API was used
- `last_error` (timestamp) — Last time the API had an error
- `error_window` (integer, default: 3600) — Seconds after which error count resets
- `error_count` (integer, default: 0) — Count of errors per error_window
- `error_limit` (integer, default: 10) — Errors before API is disabled

#### ApiEndpoint (legacy fields)

- `last_used` (timestamp) — Last time the endpoint was used
- `last_error` (timestamp) — Last time the endpoint had an error
- `error_count` (integer, default: 0) — Count of errors

---

## Package structure (class reference)

Each class, its responsibility, and usage examples where applicable. Documented in alphabetical order.

> **Note:** Some entries below were written during early development and still say "TODO: not implemented yet". Where the class exists and is used today, the TODO is outdated — check `src/` for the current implementation.

### Auth

#### BaseAuthStrategy

- Abstract base implementing common auth functionality
- Manages authentication state via `authenticated` flag
- Stores credentials in protected array
- Provides concrete implementations for:
  - `isAuthenticated()`
  - `getCredentials()`
  - `clearCredentials()`
- Leaves strategy-specific methods abstract:
  - `authenticate()`
  - `applyToRequest()`
  - `refreshCredentials()`

#### BasicAuthStrategy

- Implements Basic HTTP Authentication
- Requires username and password in constructor
- Validates credential presence during authentication
- Applies Base64 encoded `Authorization: Basic` header
- Throws on missing credentials or unauthorized use

```php
$auth = new BasicAuthStrategy('username', 'password');
$auth->authenticate(); // Validates credentials
$request = $auth->applyToRequest($request); // Adds Basic Auth header
```

#### BearerTokenStrategy

- Implements Bearer token authentication
- Takes token in constructor
- Validates token presence during authentication
- Applies `Authorization: Bearer` header
- Throws on missing token or unauthorized use

```php
$auth = new BearerTokenStrategy('your-token');
$auth->authenticate(); // Validates token
$request = $auth->applyToRequest($request); // Adds Bearer header
```

#### JwtAuthStrategy

- Original doc: TODO: Not implemented yet
- **Current:** Implemented in `src/Auth/JwtAuthStrategy.php` (HS256 and token refresh support)

#### GraphQLAuthStrategy

- Implements GraphQL-based authentication
- Handles login and refresh mutations
- Stores access and refresh tokens separately
- Supports token refresh if mutation provided
- Checks refresh capability via `hasRefreshCapability()`

```php
$auth = new GraphQLAuthStrategy(
    loginMutation: 'mutation Login($email: String!, $password: String!) {
        login(email: $email, password: $password) {
            accessToken
            refreshToken
        }
    }',
    variables: ['email' => 'user@example.com', 'password' => 'secret']
);
```

#### MultiAuthStrategy

- Manages multiple authentication strategies
- Maps URL patterns to specific strategies
- Routes requests to appropriate strategy
- Supports refresh for capable strategies
- Falls back to first strategy if no pattern matches

```php
$auth = new MultiAuthStrategy([
    '/users/*' => $bearerAuth,
    '/admin/*' => $basicAuth,
    '/graphql' => $graphqlAuth
]);
```

### Clients

#### BaseApiClient

- Abstract base implementing common client functionality
- Handles complete request lifecycle:
  - Authentication check/refresh
  - Request preparation
  - Rate limiting
  - Retry handling
  - Response parsing
- Provides immutable header modification

#### RestApiClient

- Implements HTTP request execution
- Handles all HTTP methods (GET, POST, PUT, DELETE, PATCH)
- Manages request/response headers
- Processes request body
- Parses response data

```php
$client = new RestApiClient(
    auth: $auth,
    baseUrl: 'https://api.example.com'
);

$response = $client->get('/users', ['page' => 1]);
$response = $client->post('/users', ['name' => 'John Doe']);
```

#### GraphQLApiClient

- Original doc name: `GraphQLClient`
- Implements GraphQL-specific request handling
- Provides dedicated query/mutation methods
- Supports operation naming
- Handles variables
- Processes GraphQL errors

```php
$client = new GraphQLApiClient(
    endpoint: '/graphql',
    auth: $auth,
    baseUrl: 'https://api.example.com'
);

$data = $client->query('
    query GetUser($id: ID!) {
        user(id: $id) {
            id
            name
        }
    }
', ['id' => 123]);
```

### Contracts

#### ApiClientInterface

Base contract for all API clients. Defines core functionality:

- `sendRequest()`: Executes API requests with full middleware stack
- `authenticate()`: Handles initial authentication
- `refreshToken()`: Optional token refresh for OAuth/JWT

#### ApiRequestInterface

Base contract for HTTP requests. Defines:

- `getMethod()`: Returns HTTP method
- `getEndpoint()`: Returns endpoint configuration
- `getHeaders()`: Returns request headers array
- `getQueryParams()`: Returns URL query parameters
- `getBody()`: Returns request body content

#### ApiResponseInterface

Base contract for HTTP responses. Defines:

- `getStatusCode()`: Returns HTTP status code
- `getHeaders()`: Returns response headers array
- `json()`: Parses and returns JSON response body
- `isSuccessful()`: Checks if status code indicates success
- `getRateLimit()`: Returns rate limit information if available

#### AuthenticationStrategyInterface

Base contract for all auth strategies. Defines:

- `authenticate()`: Validates and stores credentials
- `isAuthenticated()`: Returns current auth state
- `getCredentials()`: Returns stored credentials array
- `applyToRequest()`: Modifies request with auth headers
- `refreshCredentials()`: Handles token refresh if supported

#### TransformerInterface

Base contract for all data transformers. Defines:

- `transform()`: Converts data to target format
- `reverseTransform()`: Converts back from target format
- `supports()`: Checks if transformer can handle data type
- `getPriority()`: Returns transformer priority

### DataObjects

#### ApiExchange

- Value object representing a single API interaction
- Immutable data structure
- Tracks complete exchange context:
  - Unique identifier
  - API and endpoint information
  - Direction (INBOUND/OUTBOUND)
  - Context (CRON/USER/WEBHOOK/SYSTEM)
  - Trigger (EXPIRED/REQUESTED/WEBHOOK)
  - Request/response data
  - Status code and timestamp

#### RateLimitResult

- Value object for rate limit check results
- Contains:
  - allowed: Whether request is allowed
  - limit: Maximum requests allowed
  - remaining: Remaining requests in window
  - reset: When the limit resets
- Used for rate limit header generation

### Enums

#### ExchangeContext

- Values: CRON, USER, WEBHOOK, SYSTEM

#### ExchangeDirection

- Values: INBOUND, OUTBOUND

#### ExchangeTrigger

- Values: EXPIRED, REQUESTED, WEBHOOK

### Exceptions

#### ApiException

Base exception for API-related errors. Provides:

- HTTP status code
- Failed endpoint information
- Full response if available
- Additional error context

#### JobException

- Specific exception for job-related errors
- Named constructors for common scenarios:
  - `jobFailed()`: General job execution failures
  - `invalidState()`: State validation errors
  - `configurationError()`: Job setup issues
- Includes job ID tracking

#### TransformerException

- Specific exception for transformation errors
- Thrown during data transformation failures
- Includes context about the failed transformation

### Health

#### ApiErrorLogger

- Provides structured logging for API errors
- Handles request/response context
- Sanitizes sensitive headers
- Supports custom logging channels
- Uses the Laravel logging system

```php
$logger = new ApiErrorLogger(
    channel: 'api-errors',
    context: ['api' => 'users']
);

$logger->logRequestError($request, $response, $error);
```

#### ApiHealthMonitor

- Tracks API health status using cache
- Monitors error rates within time windows
- Records response times and success rates
- Provides health status reporting
- Maintains historical metrics

### Http

#### ApiRequest

- Immutable request object
- Stores complete request information:
  - HTTP method
  - Endpoint path
  - Headers
  - Query parameters
  - Request body

#### ApiResponse

- Immutable response object
- Handles response data and metadata:
  - Status code validation
  - Header management
  - JSON body parsing
  - Rate limit information

#### RequestBuilder

- Fluent builder for ApiRequest objects
- Supports all HTTP methods
- Handles headers and query parameters
- JSON body helper methods

#### ResponseParser

- Handles response validation and parsing
- Configurable status code validation
- JSON response handling
- Error detection
- Content type validation

#### WebhookHandler

- TODO: not implemented yet

### Jobs

#### BaseSyncJob

- Provides base functionality for all sync jobs
- Integrates with the Laravel queue system
- Handles job retries with multiple strategies
- Manages API status tracking
- Includes error handling and notifications

#### BatchSyncJob

- Processes multiple parameter sets for a single endpoint
- Supports sequential and parallel execution modes
- Implements configurable batch sizes
- Handles batch delays
- Provides error handling strategies

#### ChainedSyncJob

- Handles chained API calls with parameter passing
- Executes source job to get data
- Maps source data to target parameters
- Supports both single and batch target execution
- Handles complex data path extraction

#### SingleEndpointSyncJob

- Handles single endpoint synchronization
- Supports all HTTP methods
- Manages endpoint parameters
- Uses EndpointResolver for URL generation
- Includes response processing hook

#### Additional sync orchestration jobs (current implementation)

These jobs are not in the original structure doc but drive the main sync pipeline:

| Job | Responsibility |
|-----|----------------|
| `RunEndpointJob` | Fetch one endpoint, persist import record |
| `RunConnectionTreeJob` | BFS orchestration of parent/child endpoint tree |
| `RunConnectionTreeLevelJob` | Process one level of the connection tree |
| `DispatchConnectionTreeNextLevelJob` | Queue next tree level after batch completes |
| `RunDetailForListJob` | Expand list response into detail fetches |
| `RunEndpointForItemJob` | Single detail request for one list item |
| `FinalizeDetailSyncJob` | Complete detail sync batch |
| `FetchImportRecordsJob` | Fetch and store import records |
| `TransformImportRecordsJob` | Map import records to `destination_model` |

### Models

#### ApiConnection

- Original doc: TODO: not implemented yet
- **Current:** Implemented — see [ApiConnection](#apiconnection)

#### ApiEndpoint

- Original doc: TODO: not implemented yet
- **Current:** Implemented — see [ApiEndpoint](#apiendpoint)

#### ApiJob

- TODO: not implemented yet (superseded by queue jobs; see [Additional sync orchestration jobs](#additional-sync-orchestration-jobs-current-implementation))

#### ApiLog

- Original doc: TODO: not implemented yet
- **Current:** Implemented — see [ApiLog](#apilog)

### Notifications

#### ApiErrorNotification

- Original doc: TODO: not implemented yet
- **Current:** Implemented in `src/Notifications/ApiErrorNotification.php`

### Filament resources

#### ApiConnectionResource

- Original doc name: `ApiResource` — TODO: not implemented yet
- **Current:** `ApiConnectionResource` with CRUD pages and connection tree widget

#### ApiEndpointResource

- Original doc: TODO: not implemented yet
- **Current:** Implemented with import records relation manager

#### ApiJobResource

- TODO: not implemented yet

#### ApiLogResource

- Original doc: TODO: not implemented yet
- **Current:** Implemented with list/view/edit pages

#### Dashboard

- TODO: not implemented yet

### Support

#### ApiEndpointRunner

- Executes endpoint HTTP calls and persists responses (not in original structure doc)

#### ApiImportPayloadExtractor

- Extracts payload data from import records and chunks

#### ApiStatusManager

- Manages API status states (new, active, error, disabled, unused)
- Tracks error counts and last usage
- Automatically transitions between states
- Uses cache for state persistence
- Maintains audit trail of status changes

#### ChainedParameterResolver

- Resolves parameters from source data using field mappings
- Supports deep object traversal with dot notation
- Handles array transformations
- Provides parameter template substitution
- Generates parameter combinations for batch operations

#### ConnectionHealthChecker

- Checks API connection health via configured `health_path`

#### EndpointListToDetailOrchestrator

- Orchestrates list→detail endpoint expansion

#### EndpointResolver

- Manages dynamic endpoint patterns
- Supports parameter replacement
- Validates endpoint patterns
- Handles global and endpoint-specific parameters
- Supports batch endpoint resolution

#### JobScheduler

- Integrates with Laravel scheduler
- Supports various scheduling frequencies
- Manages schedule metadata in cache
- Provides schedule control (pause/resume)
- Tracks last/next run times

#### JobStatus

- Immutable value object for job state management
- Comprehensive status tracking
- Progress tracking with validation
- Attempt counting and retry scheduling
- Error message storage

#### JobStatusManager

- Manages job status lifecycle
- Atomic status updates
- Progress tracking
- Cache-based storage with TTL
- Status validation

#### QueueJobStatsService

- Tracks queue job statistics for connection tree runs

#### RateLimiter

- Supports both distributed and local rate limiting
- Uses sliding window algorithm
- Configurable request limits and time windows
- Provides remaining requests count
- Calculates reset time

#### RateLimiterFactory

- Creates configured RateLimiter instances
- Handles endpoint-specific and job-specific limits
- Loads configuration from connect.php
- Supports custom limits and windows

#### RetryHandler

- Supports multiple retry strategies (fixed, linear, exponential)
- Configurable max attempts and delay
- Proper error propagation
- Microsecond-precision delays

#### TransformerRegistry

- Central registry for transformer management
- Priority-based transformer ordering
- Name-based transformer lookup
- Automatic transformer selection
- Thread-safe implementation

### Transformers

#### BaseTransformer

- Abstract base class for all transformers
- Provides common transformer functionality:
  - Name and priority management
  - Option validation and merging
  - Type assertions
  - Immutable configuration

#### ArrayTransformer

- Handles array conversions and transformations
- Supports multiple target types:
  - Arrays
  - Objects
  - Collections
- Handles scalar to array conversion

#### DateTimeTransformer

- Handles date/time string conversions
- Carbon integration for parsing
- Supports multiple date formats
- Timezone-aware transformations
- Format customization via options

#### JsonTransformer

- JSON encoding and decoding with error handling
- Supports custom JSON flags
- Automatic JSON detection
- Handles scalar and complex types
- Configurable array/object output

#### NumberTransformer

- Handles numeric value transformations
- Supports locale-aware formatting
- Configurable precision and rounding
- Custom decimal and thousands separators
- NumberFormatter integration

---

## Tests

```bash
php artisan test --compact packages/connect/tests
```

## License

MIT — see [LICENSE.md](LICENSE.md).
