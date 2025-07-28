# Monorepo v2.0 - Clean, Fast, and Simple

A completely rewritten monorepo management package for Laravel, designed with clean architecture principles, better separation of concerns, and improved testability.

## 🎯 Key Improvements over v1.0

### Architecture
- **Clean Interface Design**: All services implement clear contracts
- **Separation of Concerns**: Actions, services, and commands are properly separated
- **Dependency Injection**: Full DI container usage for better testability
- **Data Transfer Objects**: Clean data handling with immutable DTOs
- **Action-Based Logic**: Complex operations broken into focused action classes

### Performance
- **Caching Layer**: Built-in caching for GitHub API requests
- **Optimized Requests**: Reduced API calls through intelligent caching
- **Batch Processing**: Efficient handling of multiple packages
- **Memory Efficiency**: Better resource management

### Developer Experience
- **Focused Commands**: Simple, single-responsibility commands
- **Better Error Handling**: Comprehensive error reporting and logging
- **Dry Run Support**: Test releases without making changes
- **Rich CLI Output**: Beautiful, informative command output
- **Comprehensive Tests**: Full test coverage with Pest

## 📦 Installation

```bash
composer require moox/monorepo
```

Publish the configuration:

```bash
php artisan vendor:publish --tag="monorepo-config"
```

## ⚙️ Configuration

Configure your environment variables:

```env
MONOREPO_GITHUB_ORG=mooxphp
MONOREPO_PUBLIC_REPO=moox
MONOREPO_PRIVATE_REPO=pro
MONOREPO_PACKAGES_PATH=packages
MONOREPO_DEVLOG_PATH=packages/monorepo/DEVLOG.md
MONOREPO_CACHE_ENABLED=true
MONOREPO_CACHE_TTL=300

# Repository creation settings (defaults based on mooxphp/jobs)
MONOREPO_DEFAULT_LICENSE=null
MONOREPO_REPO_AUTO_INIT=false
MONOREPO_REPO_GITIGNORE=null
MONOREPO_REPO_HAS_ISSUES=true
MONOREPO_REPO_HAS_PROJECTS=false
MONOREPO_REPO_HAS_WIKI=false
MONOREPO_REPO_HAS_DISCUSSIONS=false
MONOREPO_REPO_ALLOW_FORKING=true
MONOREPO_REPO_WEB_COMMIT_SIGNOFF=false

# Merge preferences
MONOREPO_REPO_ALLOW_SQUASH=true
MONOREPO_REPO_ALLOW_MERGE=false
MONOREPO_REPO_ALLOW_REBASE=false
MONOREPO_REPO_ALLOW_AUTO_MERGE=false
MONOREPO_REPO_DELETE_BRANCH_ON_MERGE=true
```

## 🚀 Usage

### List Packages

Get an overview of all packages:

```bash
php artisan monorepo:list
```

Show only public packages:
```bash
php artisan monorepo:list --public
```

Show only private packages:
```bash
php artisan monorepo:list --private
```

Show packages with changes:
```bash
php artisan monorepo:list --changes
```

Show packages missing from GitHub:
```bash
php artisan monorepo:list --missing
```

### Create Missing Repositories

Automatically create empty GitHub repositories for packages that don't have them:

```bash
php artisan monorepo:create-missing
```

Create only public repositories:
```bash
php artisan monorepo:create-missing --public
```

Create only private repositories:
```bash
php artisan monorepo:create-missing --private
```

Show what would be created without making changes:
```bash
php artisan monorepo:create-missing --dry-run
```

Skip confirmation prompts:
```bash
php artisan monorepo:create-missing --force
```

Interactive mode - ask for each repository individually:
```bash
php artisan monorepo:create-missing --interactive
```

Skip updating devlink configuration:
```bash
php artisan monorepo:create-missing --skip-devlink
```

### Create Release

Create a new release:

```bash
php artisan monorepo:release
```

Specify version directly:
```bash
php artisan monorepo:release --version=1.2.3
```

Dry run (show what would happen):
```bash
php artisan monorepo:release --dry-run
```

Release only public packages:
```bash
php artisan monorepo:release --public-only
```

Release only private packages:
```bash
php artisan monorepo:release --private-only
```

## 🎯 Stability-Based Release Control

The package supports intelligent release control based on package stability settings in `composer.json`:

### Package Stability Configuration

Add stability configuration to your package's `composer.json`:

```json
{
    "extra": {
        "moox-stability": "stable"
    }
}
```

### Stability Levels

- **`"stable"`**: Package will receive GitHub releases when included in monorepo releases
- **`"dev"`** (or missing): Package will be split to its repository but won't get releases
- **Any other value**: Treated as `"dev"`

### Workflow Behavior

When you run `php artisan monorepo:release --version=1.2.3`:

1. **Monorepo Release**: Creates `v1.2.3` release on main monorepo
2. **Package Splitting**: All packages are split to their individual repositories
3. **Individual Releases**: Only packages with `"moox-stability": "stable"` get GitHub releases
4. **Dev Packages**: Split but no release (keeps repos up-to-date without versioned releases)

This allows you to:
- Keep experimental packages in development without confusing users with releases
- Gradually promote packages from dev to stable as they mature
- Maintain consistent repository content while controlling public releases

## 🔄 Progress Indicators

All commands provide clear progress feedback:

### Real-time Progress

```bash
🚀 Starting Monorepo Release Process v2.0
🔐 Authenticating with GitHub...
✅ Authenticated as: Moox-Bot
📡 Getting current version from GitHub...
🔍 Discovering packages from monorepo...
   📂 Scanning public packages...
     📡 Fetching packages from mooxphp/moox...
     🔄 Processing 42 packages...
     📊 Comparing with organization repositories...
📝 Processing changelog entries...
```

### Visual Progress Bars

- **Repository Creation**: Shows progress bar during batch repository creation
- **Interactive Mode**: Step-by-step confirmation with package details
- **Rate Limiting**: Built-in delays with feedback to avoid API limits

## 🏗️ Architecture

### Components

#### Contracts (Interfaces)
- `GitHubClientInterface` - GitHub API interactions
- `VersionManagerInterface` - Version handling and validation
- `PackageDiscoveryInterface` - Package discovery and comparison
- `ChangelogProcessorInterface` - Changelog/DEVLOG processing

#### Services
- `GitHubClient` - GitHub API client with caching
- `VersionManager` - Semantic version management

#### Actions
- `DiscoverPackagesAction` - Package discovery logic
- `ProcessChangelogAction` - Changelog processing logic
- `CreateReleaseAction` - Release creation logic

#### Data Transfer Objects
- `PackageInfo` - Package information container
- `ReleaseInfo` - Release information container
- `PackageChange` - Package change information

#### Commands
- `ReleaseCommand` - Main release orchestration
- `ListPackagesCommand` - Package listing and overview
- `CreateMissingRepositoriesCommand` - Create missing GitHub repositories

### Data Flow

1. **Discovery**: Package discovery scans GitHub monorepo repositories and compares with organization repositories
2. **Changelog Processing**: DEVLOG.md is parsed for package-specific changes
3. **Stability Filtering**: Package stability settings determine release eligibility
4. **Version Management**: Current version is retrieved and new version is validated
5. **Release Creation**: GitHub releases are created and workflows are triggered
6. **Workflow Dispatch**: Package splitting workflows are triggered with stability and package data

## 📝 Changelog Format

The package expects a DEVLOG.md file with the following format:

```markdown
## PackageName1
- Feature: Added new functionality
- Fix: Fixed bug in component
- Breaking: Removed deprecated method

## PackageName2
- Feature: New feature added
```

Packages without explicit changelog entries get a "Compatibility release" message.

## 🧪 Testing

The package includes comprehensive tests:

```bash
./vendor/bin/pest
```

Test structure:
- `tests/Unit/` - Unit tests for services and actions
- `tests/Feature/` - Integration tests for commands

Example test for VersionManager:

```php
it('validates semantic version format correctly', function () {
    expect($this->versionManager->validateVersionFormat('1.0.0'))->toBeTrue();
    expect($this->versionManager->validateVersionFormat('1.0.0-alpha.1'))->toBeTrue();
    expect($this->versionManager->validateVersionFormat('invalid'))->toBeFalse();
});
```

## 📦 Repository Creation

The monorepo package can automatically create empty GitHub repositories for packages that exist in your monorepo but don't have their own individual repositories. These empty repositories are then ready to be populated by your split workflow.

### How it Works

1. **Discovery**: The command scans your GitHub monorepo(s) to find all packages
2. **Comparison**: It compares found packages with existing repositories in your organization
3. **Repository Creation**: For missing repositories, it creates empty GitHub repositories with proper settings
4. **Workflow Ready**: The repositories are ready to receive content from your split workflow

### Devlink Integration

When creating repositories, the package automatically updates your devlink configuration:

- **Automatic Updates**: New packages are added to `config/devlink.php` in alphabetical order
- **Proper Configuration**: Packages are configured with correct paths and visibility settings
- **Skip Option**: Use `--skip-devlink` to skip devlink configuration updates
- **Error Handling**: Devlink failures don't stop repository creation

### Workflow Integration

This creates empty repositories that work perfectly with your existing `split.yml` workflow:

- **Empty repositories** are created with optimal settings for package splitting
- **Configurable settings** - defaults based on mooxphp/jobs repository:
  - Issues: Enabled (configurable via `MONOREPO_REPO_HAS_ISSUES`)
  - Projects: Disabled (configurable via `MONOREPO_REPO_HAS_PROJECTS`)
  - Wiki: Disabled (configurable via `MONOREPO_REPO_HAS_WIKI`)
  - Discussions: Disabled (configurable via `MONOREPO_REPO_HAS_DISCUSSIONS`)
  - Forking: Allowed (configurable via `MONOREPO_REPO_ALLOW_FORKING`)
  - Commit signoff: Not required (configurable via `MONOREPO_REPO_WEB_COMMIT_SIGNOFF`)
- **Merge preferences** configured (squash merge enabled, others disabled by default)
- **Repository settings** optimized for package workflows and fully customizable
- **Ready for content** - your workflow can immediately split package content into them

### Command Options

- **Selective creation**: Create only public or private repositories
- **Dry-run mode**: Preview what would be created without making changes
- **Force mode**: Skip interactive confirmations
- **Configurable settings**: Repository features and merge settings are configurable

### Safety Features

- **Dry-run mode**: Preview repositories that would be created
- **Interactive prompts**: Confirmation required before creating repositories
- **Progress tracking**: Visual progress bar for batch operations
- **Error handling**: Detailed error reporting for failed creations
- **Rate limiting**: Built-in delays to avoid GitHub API limits

## 🔄 Migration from v1.0

The v2.0 package is a complete rewrite with breaking changes:

### Command Changes
- `moox:releasing` → `monorepo:release`
- `moox:releasing --versions` → `monorepo:list`
- `moox:releasing --compare-packages` → `monorepo:list --missing`

### Configuration Changes
- Old: `config('monorepo.github_org')` → New: `config('monorepo.github.organization')`
- Improved configuration structure with nested arrays

### Service Changes
- All services now implement interfaces
- Dependency injection used throughout
- Better error handling and logging

## 🚀 Performance Improvements

### Caching
- GitHub API responses are cached for 5 minutes by default
- Cache can be disabled via configuration
- Intelligent cache invalidation on writes

### Batch Processing
- Multiple packages processed efficiently
- Reduced API calls through pagination
- Memory-efficient collection handling

### Optimized Workflows
- Payload size limits prevent workflow failures
- Message sanitization prevents bash injection
- Intelligent truncation when needed

## 🛠️ Extending the Package

### Custom Actions

Create custom actions by implementing the relevant interfaces:

```php
class CustomPackageAction implements PackageDiscoveryInterface
{
    public function discoverPackages(string $path, string $visibility = 'public'): Collection
    {
        // Custom discovery logic
    }
}
```

### Custom Services

Extend services by binding new implementations:

```php
$this->app->bind(GitHubClientInterface::class, CustomGitHubClient::class);
```

## 📊 Monitoring and Logging

The package provides comprehensive logging:

- GitHub API request/response logging
- Error handling with context
- Performance metrics
- Cache hit/miss statistics

Logs are written to the Laravel log channel and include:
- Request URLs and methods
- Response status codes
- Error messages with context
- Performance timings

## 🔒 Security

- GitHub tokens are securely handled
- Message sanitization prevents injection attacks
- Payload size limits prevent DoS
- Input validation on all user inputs

## 📈 Future Roadmap

- [ ] Web UI for release management
- [ ] Webhook support for automated releases
- [ ] Multi-repository batch operations
- [ ] Release templates and automation
- [ ] Integration with package registries
- [ ] Advanced analytics and reporting

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## 📄 License

MIT License - see LICENSE file for details.

---

**Monorepo v2.0** - Built with ❤️ for better developer experience 