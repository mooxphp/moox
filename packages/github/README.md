# Moox GitHub Package

## Overview

The **Moox GitHub** package is a Laravel package that provides seamless GitHub OAuth integration for Filament-based applications. It acts as a wrapper around the GitHub API and enables users to connect their GitHub accounts to your application, storing GitHub authentication tokens for subsequent API interactions.

## Purpose and Features

This package is designed to:

- **OAuth Integration**: Provide secure GitHub OAuth2 authentication flow
- **User Account Linking**: Connect existing user accounts with their GitHub profiles
- **Token Management**: Store and manage GitHub access tokens for API calls
- **Filament Integration**: Seamlessly integrate with Filament admin panels
- **Database Extensions**: Extend user models with GitHub-specific fields

### Key Features

1. **GitHub OAuth Flow**: Complete OAuth2 implementation with connect/disconnect functionality
2. **Filament UI Integration**: Ready-to-use buttons in Filament panels for GitHub connection
3. **User Token Storage**: Secure storage of GitHub tokens for API access
4. **Database Migration**: Automatic database schema updates for GitHub fields
5. **Error Handling**: Comprehensive error handling with user-friendly messages

## Installation Steps

### 1. Add Package Dependency

Add the package to your `composer.json`:

```json
{
    "require": {
        "moox/github": "*"
    }
}
```

### 2. Database Migration

Publish the migration and 
Run the migration to add GitHub fields to your users table:

```bash
php artisan migrate
```

This adds the following fields to the `users` table:
- `github_id` (string, nullable) - GitHub user ID
- `github_token` (string, nullable) - GitHub access token

### 3. GitHub OAuth App Setup

1. Go to GitHub Settings → Developer settings → OAuth Apps
2. Create a new OAuth App with:
   - **Application name**: Your app name
   - **Homepage URL**: Your application URL
   - **Authorization callback URL**: `{YOUR_APP_URL}/auth/github/callback`

### 4. Environment Configuration

Add the following environment variables to your `.env` file:

```env
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
```

### 5. Service Provider Registration

The package auto-registers its service provider. Ensure it's loaded by checking your `config/app.php` or letting Laravel's auto-discovery handle it.

## Package Interfaces and API

### Web Routes

The package provides three main routes:

#### 1. GitHub Connect Route
- **URL**: `/auth/github/connect`
- **Method**: GET
- **Middleware**: `auth`
- **Purpose**: Initiates GitHub OAuth flow
- **Route Name**: `github.connect`

#### 2. GitHub Callback Route
- **URL**: `/auth/github/callback`
- **Method**: GET
- **Purpose**: Handles GitHub OAuth callback
- **Route Name**: `github.callback`

#### 3. GitHub Disconnect Route
- **URL**: `/auth/github/disconnect`
- **Method**: GET
- **Middleware**: `auth`
- **Purpose**: Removes GitHub connection from user account
- **Route Name**: `github.disconnect`

### Database Schema

The package extends the `users` table with:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('github_id')->nullable();
    $table->string('github_token')->nullable();
});
```

### User Model Integration

Update your User model to include the GitHub fields in the fillable array:

```php
class User extends Authenticatable
{
    protected $fillable = [
        // ... existing fields
        'github_id',
        'github_token',
    ];
}
```

## Usage Examples

### 1. Filament Panel Integration

The package integrates with Filament panels through the `MooxPanelProvider`. Users see:

- **Connect Button**: When GitHub is not connected
- **Disconnect Button**: When GitHub is already connected

```php
// In your Filament panel provider
->userMenuItems([
    MenuItem::make()
        ->label('GitHub verbinden')
        ->icon('heroicon-o-link')
        ->url('/auth/github/connect')
        ->visible(function () {
            $user = Auth::user();
            return $user && !$user->github_id;
        }),
    MenuItem::make()
        ->label('GitHub trennen')
        ->icon('heroicon-o-x-mark')
        ->url('/auth/github/disconnect')
        ->visible(function () {
            $user = Auth::user();
            return $user && $user->github_id;
        }),
])
```

### 2. Programmatic Usage

Check if a user has GitHub connected:

```php
// Check if user has GitHub connected
if ($user->github_id) {
    // User has GitHub connected
    $githubToken = $user->github_token;
    // Use token for GitHub API calls
}
```

### 3. GitHub API Integration

Once a user has connected their GitHub account, you can use their token for API calls:

```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->get('https://api.github.com/user', [
    'headers' => [
        'Authorization' => 'token ' . $user->github_token,
        'Accept' => 'application/vnd.github.v3+json',
    ],
]);

$githubUser = json_decode($response->getBody(), true);
```

## Configuration

### Services Configuration

The package uses Laravel Socialite for OAuth. Configuration is handled in `config/services.php`:

```php
'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => env('APP_URL').'/auth/github/callback',
],
```

### Dependencies

The package requires:
- PHP ^8.3
- Laravel Socialite ^5.21
- Filament (through Moox Core)

## Security Considerations

1. **Token Storage**: GitHub tokens are stored in the database and should be encrypted in production
2. **Environment Variables**: Keep GitHub client secrets secure and never commit them to version control
3. **HTTPS**: Always use HTTPS in production for OAuth flows
4. **Token Scope**: Consider implementing token scope management for limiting API access

## Error Handling

The package includes comprehensive error handling:

- **OAuth Errors**: Logged and user-friendly messages displayed
- **Route Errors**: Graceful fallbacks to main application
- **Authentication Errors**: Proper redirects to login pages

## Future Development

Based on the `IDEA.md`, the package may expand to include:
- Full GitHub API wrapper (inspired by GrahamCampbell/Laravel-GitHub)
- GitHub resource management through Filament
- Repository and organization management interfaces

## Dependencies

- **moox/core**: Core Moox functionality
- **laravel/socialite**: OAuth2 provider for GitHub
- **symplify/monorepo-builder**: Monorepo management

This package provides a solid foundation for GitHub integration in Laravel applications with Filament, offering both OAuth authentication and the groundwork for future GitHub API integrations.
