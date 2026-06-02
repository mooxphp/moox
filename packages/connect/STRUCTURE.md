# Package Structure

We document each class, their responsability and add a usage example (if applicable) in alphabetical order.

## Auth

### BaseAuthStrategy

-   Abstract base implementing common auth functionality
-   Manages authentication state via `authenticated` flag
-   Stores credentials in protected array
-   Provides concrete implementations for:
    -   `isAuthenticated()`
    -   `getCredentials()`
    -   `clearCredentials()`
-   Leaves strategy-specific methods abstract:
    -   `authenticate()`
    -   `applyToRequest()`
    -   `refreshCredentials()`

### BasicAuthStrategy

-   Implements Basic HTTP Authentication
-   Requires username and password in constructor
-   Validates credential presence during authentication
-   Applies Base64 encoded `Authorization: Basic` header
-   Throws on missing credentials or unauthorized use

```php
$auth = new BasicAuthStrategy('username', 'password');
$auth->authenticate(); // Validates credentials
$request = $auth->applyToRequest($request); // Adds Basic Auth header
```

### BearerTokenStrategy

-   Implements Bearer token authentication
-   Takes token in constructor
-   Validates token presence during authentication
-   Applies `Authorization: Bearer` header
-   Throws on missing token or unauthorized use

```php
$auth = new BearerTokenStrategy('your-token');
$auth->authenticate(); // Validates token
$request = $auth->applyToRequest($request); // Adds Bearer header
```

### JwtAuthStrategy

-   TODO: Not implemented yet

### GraphQLAuthStrategy

-   Implements GraphQL-based authentication
-   Handles login and refresh mutations
-   Stores access and refresh tokens separately
-   Supports token refresh if mutation provided
-   Checks refresh capability via `hasRefreshCapability()`

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

### MultiAuthStrategy

-   Manages multiple authentication strategies
-   Maps URL patterns to specific strategies
-   Routes requests to appropriate strategy
-   Supports refresh for capable strategies
-   Falls back to first strategy if no pattern matches

```php
$auth = new MultiAuthStrategy([
    '/users/*' => $bearerAuth,
    '/admin/*' => $basicAuth,
    '/graphql' => $graphqlAuth
]);
```

## Clients

### BaseApiClient

-   Abstract base implementing common client functionality
-   Handles complete request lifecycle:
    -   Authentication check/refresh
    -   Request preparation
    -   Rate limiting
    -   Retry handling
    -   Response parsing
-   Provides immutable header modification

### RestApiClient

-   Implements HTTP request execution
-   Handles all HTTP methods (GET, POST, PUT, DELETE, PATCH)
-   Manages request/response headers
-   Processes request body
-   Parses response data

```php
$client = new RestApiClient(
    auth: $auth,
    baseUrl: 'https://api.example.com'
);

$response = $client->get('/users', ['page' => 1]);
$response = $client->post('/users', ['name' => 'John Doe']);
```

### GraphQLClient

-   Implements GraphQL-specific request handling
-   Provides dedicated query/mutation methods
-   Supports operation naming
-   Handles variables
-   Processes GraphQL errors

```php
$client = new GraphQLClient(
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

## Contracts

### ApiClientInterface

Base contract for all API clients. Defines core functionality:

-   `sendRequest()`: Executes API requests with full middleware stack
-   `authenticate()`: Handles initial authentication
-   `refreshToken()`: Optional token refresh for OAuth/JWT

### ApiRequestInterface

Base contract for HTTP requests. Defines:

-   `getMethod()`: Returns HTTP method
-   `getEndpoint()`: Returns endpoint configuration
-   `getHeaders()`: Returns request headers array
-   `getQueryParams()`: Returns URL query parameters
-   `getBody()`: Returns request body content

### ApiResponseInterface

Base contract for HTTP responses. Defines:

-   `getStatusCode()`: Returns HTTP status code
-   `getHeaders()`: Returns response headers array
-   `json()`: Parses and returns JSON response body
-   `isSuccessful()`: Checks if status code indicates success
-   `getRateLimit()`: Returns rate limit information if available

### AuthenticationStrategyInterface

Base contract for all auth strategies. Defines:

-   `authenticate()`: Validates and stores credentials
-   `isAuthenticated()`: Returns current auth state
-   `getCredentials()`: Returns stored credentials array
-   `applyToRequest()`: Modifies request with auth headers
-   `refreshCredentials()`: Handles token refresh if supported

### TransformerInterface

Base contract for all data transformers. Defines:

-   `transform()`: Converts data to target format
-   `reverseTransform()`: Converts back from target format
-   `supports()`: Checks if transformer can handle data type
-   `getPriority()`: Returns transformer priority

## DataObjects

### ApiExchange

-   Value object representing a single API interaction
-   Immutable data structure
-   Tracks complete exchange context:
    -   Unique identifier
    -   API and endpoint information
    -   Direction (INBOUND/OUTBOUND)
    -   Context (CRON/USER/WEBHOOK/SYSTEM)
    -   Trigger (EXPIRED/REQUESTED/WEBHOOK)
    -   Request/response data
    -   Status code and timestamp

### RateLimitResult

-   Value object for rate limit check results
-   Contains:
    -   allowed: Whether request is allowed
    -   limit: Maximum requests allowed
    -   remaining: Remaining requests in window
    -   reset: When the limit resets
-   Used for rate limit header generation

## Enums

### ExchangeContext

-   Values: CRON, USER, WEBHOOK, SYSTEM

### ExchangeDirection

-   Values: INBOUND, OUTBOUND

### ExchangeTrigger

-   Values: EXPIRED, REQUESTED, WEBHOOK

## Exceptions

### ApiException

Base exception for API-related errors. Provides:

-   HTTP status code
-   Failed endpoint information
-   Full response if available
-   Additional error context

### JobException

-   Specific exception for job-related errors
-   Named constructors for common scenarios:
    -   `jobFailed()`: General job execution failures
    -   `invalidState()`: State validation errors
    -   `configurationError()`: Job setup issues
-   Includes job ID tracking

### TransformerException

-   Specific exception for transformation errors
-   Thrown during data transformation failures
-   Includes context about the failed transformation

## Health

### ApiErrorLogger

-   Provides structured logging for API errors
-   Handles request/response context
-   Sanitizes sensitive headers
-   Supports custom logging channels
-   Uses the Laravel logging system

```php
$logger = new ApiErrorLogger(
    channel: 'api-errors',
    context: ['api' => 'users']
);

$logger->logRequestError($request, $response, $error);
```

### ApiHealthMonitor

-   Tracks API health status using cache
-   Monitors error rates within time windows
-   Records response times and success rates
-   Provides health status reporting
-   Maintains historical metrics

## Http

### ApiRequest

-   Immutable request object
-   Stores complete request information:
    -   HTTP method
    -   Endpoint path
    -   Headers
    -   Query parameters
    -   Request body

### ApiResponse

-   Immutable response object
-   Handles response data and metadata:
    -   Status code validation
    -   Header management
    -   JSON body parsing
    -   Rate limit information

### RequestBuilder

-   Fluent builder for ApiRequest objects
-   Supports all HTTP methods
-   Handles headers and query parameters
-   JSON body helper methods

### ResponseParser

-   Handles response validation and parsing
-   Configurable status code validation
-   JSON response handling
-   Error detection
-   Content type validation

### WebhookHandler

-   TODO: not implemented yet

## Jobs

### BaseSyncJob

-   Provides base functionality for all sync jobs
-   Integrates with the Laravel queue system
-   Handles job retries with multiple strategies
-   Manages API status tracking
-   Includes error handling and notifications

### BatchSyncJob

-   Processes multiple parameter sets for a single endpoint
-   Supports sequential and parallel execution modes
-   Implements configurable batch sizes
-   Handles batch delays
-   Provides error handling strategies

### ChainedSyncJob

-   Handles chained API calls with parameter passing
-   Executes source job to get data
-   Maps source data to target parameters
-   Supports both single and batch target execution
-   Handles complex data path extraction

### SingleEndpointSyncJob

-   Handles single endpoint synchronization
-   Supports all HTTP methods
-   Manages endpoint parameters
-   Uses EndpointResolver for URL generation
-   Includes response processing hook

## Models

### ApiConnection

-   TODO: not implemented yet

### ApiEndpoint

-   TODO: not implemented yet

### ApiJob

-   TODO: not implemented yet

### ApiLog

-   TODO: not implemented yet

## Notifications

### ApiErrorNotification

-   TODO: not implemented yet

## Resources

### ApiResource

-   TODO: not implemented yet

### ApiEndpointResource

-   TODO: not implemented yet

### ApiJobResource

-   TODO: not implemented yet

### ApiLogResource

-   TODO: not implemented yet

### Dashboard

-   TODO: not implemented yet

## Support

### ApiStatusManager

-   Manages API status states (new, active, error, disabled, unused)
-   Tracks error counts and last usage
-   Automatically transitions between states
-   Uses cache for state persistence
-   Maintains audit trail of status changes

### ChainedParameterResolver

-   Resolves parameters from source data using field mappings
-   Supports deep object traversal with dot notation
-   Handles array transformations
-   Provides parameter template substitution
-   Generates parameter combinations for batch operations

### EndpointResolver

-   Manages dynamic endpoint patterns
-   Supports parameter replacement
-   Validates endpoint patterns
-   Handles global and endpoint-specific parameters
-   Supports batch endpoint resolution

### JobScheduler

-   Integrates with Laravel scheduler
-   Supports various scheduling frequencies
-   Manages schedule metadata in cache
-   Provides schedule control (pause/resume)
-   Tracks last/next run times

### JobStatus

-   Immutable value object for job state management
-   Comprehensive status tracking
-   Progress tracking with validation
-   Attempt counting and retry scheduling
-   Error message storage

### JobStatusManager

-   Manages job status lifecycle
-   Atomic status updates
-   Progress tracking
-   Cache-based storage with TTL
-   Status validation

### RateLimiter

-   Supports both distributed and local rate limiting
-   Uses sliding window algorithm
-   Configurable request limits and time windows
-   Provides remaining requests count
-   Calculates reset time

### RateLimiterFactory

-   Creates configured RateLimiter instances
-   Handles endpoint-specific and job-specific limits
-   Loads configuration from connect.php
-   Supports custom limits and windows

### RetryHandler

-   Supports multiple retry strategies (fixed, linear, exponential)
-   Configurable max attempts and delay
-   Proper error propagation
-   Microsecond-precision delays

### TransformerRegistry

-   Central registry for transformer management
-   Priority-based transformer ordering
-   Name-based transformer lookup
-   Automatic transformer selection
-   Thread-safe implementation

## Transformers

### BaseTransformer

-   Abstract base class for all transformers
-   Provides common transformer functionality:
    -   Name and priority management
    -   Option validation and merging
    -   Type assertions
    -   Immutable configuration

### ArrayTransformer

-   Handles array conversions and transformations
-   Supports multiple target types:
    -   Arrays
    -   Objects
    -   Collections
-   Handles scalar to array conversion

### DateTimeTransformer

-   Handles date/time string conversions
-   Carbon integration for parsing
-   Supports multiple date formats
-   Timezone-aware transformations
-   Format customization via options

### JsonTransformer

-   JSON encoding and decoding with error handling
-   Supports custom JSON flags
-   Automatic JSON detection
-   Handles scalar and complex types
-   Configurable array/object output

### NumberTransformer

-   Handles numeric value transformations
-   Supports locale-aware formatting
-   Configurable precision and rounding
-   Custom decimal and thousands separators
-   NumberFormatter integration
