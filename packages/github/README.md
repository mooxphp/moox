# Moox GitHub Package

## Overview

The **Moox GitHub** package provides GitHub integration for Laravel applications with Filament. It supports both simple token-based access and full OAuth user authentication, adapting to your development needs.

## Installation

### Step 1: Install the Package

```bash
composer require moox/github
```

That's it for the basic installation! The package is now ready to use.

## Step 2: Choose Your Implementation Path

At this point, your implementation splits into two paths depending on your needs:

### Path A: Developer/CLI Usage (Simple Token)

**Perfect for**: Development, CLI tools, automation, API testing

**What you need**: Just a GitHub Personal Access Token

#### A1. Get a GitHub Token
1. Go to [GitHub Settings → Developer Settings → Personal Access Tokens](https://github.com/settings/tokens)
2. Click "Generate new token (classic)"
3. Select required scopes:
   - `repo` - Repository access
   - `read:org` - Organization membership
   - `workflow` - GitHub Actions access
4. Copy the generated token

#### A2. Add Token to Environment
```env
GITHUB_TOKEN=ghp_your_personal_access_token
```

**You're done!** No database setup, no OAuth configuration needed.

### Path B: Application with User Authentication

**Perfect for**: Web applications where users connect their own GitHub accounts

**What you need**: Database setup + GitHub OAuth App + User interface

#### B1. Database Setup
```bash
php artisan vendor:publish --github-migrations
php artisan migrate
```

This adds GitHub fields to your users table:
- `github_id` (string, nullable)
- `github_token` (string, nullable)

#### B2. Update User Model
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

#### B3. Create GitHub OAuth App
1. Go to [GitHub Settings → Developer Settings → OAuth Apps](https://github.com/settings/developers)
2. Click "New OAuth App"
3. Configure:
   - **Application name**: Your app name
   - **Homepage URL**: Your application URL
   - **Authorization callback URL**: `{YOUR_APP_URL}/auth/github/callback`
4. Save and copy the Client ID and Client Secret

#### B4. Add OAuth Configuration
```env
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
```

#### B5. Add Filament Panel Menu Items
```php
// In your Filament PanelProvider
use Filament\Navigation\MenuItem;
use Illuminate\Support\Facades\Auth;

->userMenuItems([
    MenuItem::make()
        ->label('Connect GitHub')
        ->icon('heroicon-o-link')
        ->url('/auth/github/connect')
        ->visible(function () {
            $user = Auth::user();
            return $user && !$user->github_id;
        }),
    MenuItem::make()
        ->label('Disconnect GitHub')
        ->icon('heroicon-o-x-mark')
        ->url('/auth/github/disconnect')
        ->visible(function () {
            $user = Auth::user();
            return $user && $user->github_id;
        }),
])
```

## Step 3: Test Your Setup

Regardless of which path you chose, you can test your setup:

### Check Token Status
```bash
php artisan github:token --check
```

This will:
- ✅ Validate your token
- 📋 Show available scopes
- 🔍 Test API connectivity
- ⚠️ Report any issues

### Show Token Information
```bash
php artisan github:token --info
```

This displays:
- Token source (environment vs user database)
- Token type (OAuth vs Personal Access Token)
- Associated user information
- Token preview (first 10 + last 4 characters)

### Other Useful Commands
```bash
# Show current status and help
php artisan github:token

# Clear user token (doesn't affect environment tokens)
php artisan github:token --clear
```

## Available Routes (Path B Only)

If you chose Path B (user authentication), these routes are available:

- **`/auth/github/connect`** - Start OAuth flow
- **`/auth/github/callback`** - OAuth callback handler
- **`/auth/github/disconnect`** - Remove user's GitHub connection

## Token Priority

The package uses this priority system:

1. **Environment Token** (`GITHUB_TOKEN`) - Always takes precedence
2. **User Token** (database) - Used when no environment token exists
3. **No Token** - Shows setup instructions

This means you can have both:
- An environment token for system operations
- User tokens for user-specific features

## Usage Examples

### Simple API Access (Works with Both Paths)

```php
use Illuminate\Support\Facades\Http;

// Get token (environment or user token)
$token = env('GITHUB_TOKEN') ?: Auth::user()->github_token;

$response = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
    'Accept' => 'application/vnd.github+json',
])->get('https://api.github.com/user');

$userData = $response->json();
```

### Check User Connection (Path B)

```php
$user = Auth::user();

if ($user->github_id) {
    // User has GitHub connected
    echo "Connected as: " . $user->github_id;
    
    // Use their token for API calls
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$user->github_token}",
        'Accept' => 'application/vnd.github+json',
    ])->get('https://api.github.com/user/repos');
} else {
    // Show connect button
    echo "Please connect your GitHub account";
}
```

## Configuration

### Services Configuration (Path B Only)

```php
// config/services.php
'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => env('APP_URL').'/auth/github/callback',
],
```

## Required GitHub Scopes

For full functionality, ensure your GitHub token has these scopes:

- **`repo`** - Repository access
- **`read:org`** - Organization membership
- **`workflow`** - GitHub Actions workflow access

## Troubleshooting

### Common Issues

**"No GitHub token found"**
```bash
# Check what's configured
php artisan github:token --info

# Solution: Add GITHUB_TOKEN to .env or set up user OAuth
```

**"Token is invalid or expired"**
```bash
# Test current token
php artisan github:token --check

# Solution: Generate new token on GitHub
```

**"Missing required scopes"**
```bash
# Check current scopes
php artisan github:token --check

# Solution: Recreate token with repo, read:org, workflow scopes
```

**OAuth callback errors**
- Verify callback URL in GitHub OAuth app matches exactly
- Check GITHUB_CLIENT_ID and GITHUB_CLIENT_SECRET in .env

## Security Considerations

1. **Never commit tokens** to version control
2. **Use HTTPS** in production for OAuth flows
3. **Rotate tokens** regularly
4. **Limit scopes** to what you actually need
5. **Encrypt database tokens** in production environments

## Quick Reference

### Path A (Developer/CLI)
```bash
# 1. Install
composer require moox/github

# 2. Add token to .env
GITHUB_TOKEN=your_token

# 3. Test
php artisan github:token --check
```

### Path B (User OAuth)
```bash
# 1. Install
composer require moox/github

# 2. Migrate
php artisan migrate

# 3. Setup OAuth app on GitHub

# 4. Add credentials to .env
GITHUB_CLIENT_ID=your_id
GITHUB_CLIENT_SECRET=your_secret

# 5. Add Filament menu items

# 6. Test
php artisan github:token --info
```

## Dependencies

- PHP ^8.3
- Laravel Socialite ^5.21 (for OAuth functionality)
- Filament (through Moox Core)

This package grows with your needs - start simple with a token, add user authentication when you're ready!