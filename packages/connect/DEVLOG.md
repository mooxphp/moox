# Devlog

This is our tasklist in the order we need to do them. Please keep this file automatically updated with the current state of the tasks. If new tasks need to be added, please make a suggestion before adding them.

-   [ ] Fix Base API Client, after having models ready

-   [ ] Complete missing implementations in auth and client classes:

    -   [ ] GraphQLAuthStrategy
        -   Missing applyToRequest() implementation
        -   Missing token refresh logic
        -   Missing actual GraphQL authentication flow
    -   [ ] GraphQLApiClient
        -   Missing complete query execution
        -   Missing mutation handling
        -   Missing GraphQL-specific error handling
    -   [ ] RestApiClient
        -   Missing request execution
        -   Missing response parsing
        -   Missing header handling
    -   [ ] BaseSyncJob
        -   Missing error handling implementation
        -   Missing notification logic
        -   Missing sync execution

-   [ ] Complete missing support systems:

    -   [ ] RateLimiter
        -   Missing implementation of rate limiting strategies
        -   Missing distributed rate limiting support
    -   [ ] RetryHandler
        -   Missing retry strategies implementation
        -   Missing backoff logic
    -   [ ] StatusManager
        -   Missing job status tracking
        -   Missing API status monitoring
    -   [ ] WebhookHandler
        -   Missing complete implementation

-   [ ] Complete Request/Response system:

    -   [ ] ApiRequest
        -   Missing concrete implementation
        -   Missing request building logic
    -   [ ] ApiResponse
        -   Missing response parsing
        -   Missing error handling
    -   [ ] ResponseParser
        -   Missing implementation
    -   [ ] RequestBuilder
        -   Missing implementation

-   [ ] Missing validation for transformer priority ranges?
-   [ ] Missing type hints in transformer options (could be an interface/enum)?
-   [ ] Missing status handling, when do we error an API?

-   [ ] Review the complete package, PHPStan and refactor where needed

## Testing

We would be able to create tests that mock the API to test things without actually calling the API or even without having the DB layer ready. But we need to have the DB layer ready to test the whole thing.

-   [ ] There is currently a jwt auth test, nothing else
-   [ ] Unit Tests (Auth, API Client, Support Systems, Jobs, Transformers)
-   [ ] Integration Tests (Auth flows, Request/Response cycle, Error handling, Rate limiting)
-   [ ] Feature Tests (Mock API, Real-world scenarios, Error recovery, Rate limiting)

## Database & UI

-   [ ] Create database migrations
-   [ ] Implement Eloquent models
-   [ ] Build Filament resources including repeater for ApiEndpoint
-   [ ] Wire the missing models and add missing fields

## Get it running

-   [ ] Run on DB values for the first time
-   [ ] In this phase, we will add both APIs and hopefully sync the first data.
-   [ ] API controller for accessing the API directly including demo components and logging
-   [ ] Fine tuning of using the freshness to invoke direct API access

## Enhancements

-   [ ] Direct access for Endpoints and Jobs
-   [ ] Discuss response time degradation, how to capture it
-   [ ] Discuss handling different types of errors (http status codes, etc.)
-   [ ] Distinguish between permanent, temporary and transient errors
-   [ ] Log level configuration (all including direct, periodic / info,error)
-   [ ] Error policy configuration (how many failures before we disable an API?)
-   [ ] When and how to reset error count? How to handle?
-   [ ] Buffer configuration: 100 requests, 10 seconds
-   [ ] Implement buffer for API logging and updating last_used
-   [ ] MultiAuth with Token inheritance
-   [ ] Add automatic token refresh handling
-   [ ] Add lang_param and default_locale support in API requests
-   [ ] Auto-refresh detection (401 Unauthorized should trigger refresh).
-   [ ] Token inheritance logic (sub-requests inherit main API token).
-   [ ] Add WebhookListener to receive API updates.
-   [ ] Register webhooks via UI.
-   [ ] Adding test buttons for APIs, endpoints, and jobs
-   [ ] API Import and Discovery, OpenAPI etc.
-   [ ] Add event system for real-time updates
-   [ ] Extend logs to track API latency, error counts, and last success time
-   [ ] Create dashboard widgets
-   [ ] TransformerChain implementation
-   [ ] CLI commands for managing APIs, endpoints, and jobs
-   [ ] CLI commands for getting status and health
-   [ ] An exchange format (JSON) for the complete API configuration from the DB
-   [ ] A way to import and export the configuration
