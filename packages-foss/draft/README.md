![Moox Draft](https://github.com/mooxphp/moox/raw/main/art/banner/draft.jpg)

# Moox Draft

Draft is a publishable Moox Entity that can be used to create and manage Pages, Posts, etc.

## Features

<!--features-->

-   Publish (or schedule)
-   Unpublish (or schedule)
-   Soft Delete
-   Supports Taxonomies
-   Supports Relations
-   Supports Modules
-   Title with Slug fields
-   Active field (Toggle)
-   Description field (Editor)
-   Content field (Markdown)
-   Data field (Key-Value)
-   Image field (Media)
-   Author field (User)
-   Type field (Select)
-   Due field (DateTime)
-   Color field (Color)
-   UUID field (UUID)
-   ULID field (ULID)

<!--/features-->

## Requirements

See [Requirements](https://github.com/mooxphp/moox/blob/main/docs/Requirements.md).

## Installation

```bash
composer require moox/draft
php artisan moox:install
```

Curious what the install command does? See [Installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md).

## Screenshot

![Moox Draft](https://github.com/mooxphp/moox/raw/main/art/screenshots/draft.jpg)

## Get Started

See [Get Started](docs/GetStarted.md).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Roadmap

Please see [ROADMAP](ROADMAP.md) for more information on what is planned for this package.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.


## The Draft Model 
The Draft model comes with several powerful features and capabilities:

### Attributes

#### Base Fields
- `is_active` (boolean) - Activation status
- `data` (json) - Flexible JSON data storage
- `type` - Content type (Post/Page)
- `status` - Publication status (draft/waiting/private/scheduled/published)
- `color` - Custom color coding
- `due_at` (datetime) - Due date
- `uuid` - Universally Unique Identifier
- `ulid` - Universally Unique Lexicographically Sortable Identifier

#### Translated Fields
- `title` - Content title
- `slug` - URL-friendly identifier
- `description` - Brief description
- `content` - Main content
- `author_id` - Content author reference
- `to_publish_at` - Scheduled publish date
- `published_at` - Actual publish date
- `to_unpublish_at` - Scheduled unpublish date
- `unpublished_at` - Actual unpublish date

### Media Handling
- Supports media attachments via Spatie Media Library
- Automatic image conversions for previews
- Media relationship through usables

### Publishing Workflow
- Scheduled publishing support
- Publication status tracking
- Unpublishing capability
- Audit trail for publishing actions

### Methods

#### Publishing Related
- `isScheduledForPublishing()` - Check if content is scheduled
- `isPublished()` - Check publication status
- `isScheduledForUnpublishing()` - Check unpublishing schedule
- `isUnpublished()` - Check if unpublished
- `handleSchedulingDates()` - Manage scheduling dates
- `handlePublishingDates()` - Handle publication dates

#### Query Scopes
- `scopeScheduledForPublishing()`
- `scopePublished()`
- `scopeScheduledForUnpublishing()`
- `scopeUnpublished()`
- `scopeRestored()`

#### Relationships
- `author()` - Author relationship
- `publishedBy()` - Publishing actor
- `updatedBy()` - Update actor
- `createdBy()` - Creation actor
- `unpublishedBy()` - Unpublishing actor
- `deletedBy()` - Deletion actor
- `restoredBy()` - Restoration actor

### Features
- Soft deletes support
- Multi-language support
- Taxonomy integration
- Automatic UUID/ULID generation
- Media library integration
- Publishing workflow management
- Audit trail for all major actions
