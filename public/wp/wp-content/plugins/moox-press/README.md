# Moox Press WordPress Plugin

This is work in progress. Do not install on production!

The Moox WordPress Plugin is meant to be installed as companion for Moox Press.

It handles the Auth-Flow.

## Some unsorted docs:

### Features

-   Synchronized users between Laravel and WordPress by using the wp_users table as Authenticatable Model in Laravel
-   Filament User Resource for the WpUser Model allowing to manage users in Laravel
-   SSO for Laravel and WordPress with an improved and configurable Auth-Flow
-   Shared dotenv variables

### Config or env values

...

### Security

Hashing and Encoding:
The user ID is base64 encoded and then hashed using HMAC with SHA-256, using the APP_KEY as the secret key. This creates a secure signature that can be verified by the receiving end.
Token Structure:
The token is structured as base64_encoded_user_id.signature, where the signature is the HMAC hash of the base64 encoded user ID. This structure allows for easy parsing and verification.
Security Consideration:
It's important to use hash_equals for comparing the expected signature with the received signature to prevent timing attacks.
This approach securely transmits the user ID without sending it in plain text and ensures that only the intended recipient, who has the secret key, can verify the authenticity of the token.
