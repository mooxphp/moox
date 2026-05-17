# Todo

- Finish cleanup docs
- Merge package docs here (Core, Jobs, etc.)
- Cleanup all packages (to pro)
- Simplify package docs with template
  - Frontmatter
  - Banner
  - Title (from composer.json)
  - Description (from composer.json)
  - Part of Moox (from template)
  - Installation (from template)
  - Usage (from template or skipped)
  - License (from template)
  - Help Moox (from template)
  - Security (from template)
- Finish docs and care for Todos
- Finish docs package to render on the web
- Start MCP package to leverage docs and package API
- Start Boost implementation
- Code relations and modules into core
- Prepare Cache Package, Slug etc. for Frontend
- Ship Policies with all Entities
- Use Shield
- Ship Components, wire Frontend and Entities, how?
- Media needs to be scoped
  - Same API as author model
- Relations, how to tackle them
  - Many to Many polymorphic can build every other?
  - Then we need one table and two RelationManagers for a relation?
  - Generate the select fields, columns in Filament
  - Provide a universal RelationManager
  - Every entity needs to be prepared by polymorphic?
  - Then which one to support? One, Many ...
- Modules, how to tackle them
  - Generate a tabbed page for any entity that uses it
  - Add fields and filters
- Attributes, used as module, JSON at work
- User Konzept mit Firewall etc. fertig

## Service Provider

```php
public function register()
{
    $this->mergeConfigFrom(__DIR__.'/../config/post/post.php', 'moox.post.post');
  	$this->mergeConfigFrom(__DIR__.'/../config/post/comment.php', 'moox.post.comment');
}

public function boot()
{
    $this->publishes([
        __DIR__.'/../config/post/post.php' => config_path('moox/post/post.php'),
      	__DIR__.'/../config/post/comment.php' => config_path('moox/post/comment.php'),
    ], 'moox-config');
}

// usage
config('moox.post.post')
config('moox.post.comment')
```

## Entity

```php
<?php
return [
  /*
  |--------------------------------------------------------------------------
  | Post Resource - Title
  |--------------------------------------------------------------------------
  |
  | The translatable title of the Post Resource in singular and plural.
  | See: https://www.moox.org/docs/core/title
  | See: https://www.moox.org/docs/core/translatable-config
  |
  */
  'single' => 'trans//draft::draft.draft',
  'plural' => 'trans//draft::draft.drafts',

  /*
  |--------------------------------------------------------------------------
  | Post Resource - Navigation Group
  |--------------------------------------------------------------------------
  |
  | The translatable navigation group of the Post Resource.
  | See: https://www.moox.org/docs/core/navigation-group
  | See: https://www.moox.org/docs/core/translatable-config
  |
  */
  'navigation_group' => 'trans//draft::draft.group',

  /*
  |--------------------------------------------------------------------------
  | Post Resource - Pruning of Soft Deletes
  |--------------------------------------------------------------------------
  |
  | This configuration is used to configure pruning for deleted Posts.
  | See: https://www.moox.org/docs/core/pruning
  |
  */
  'pruning' => [
    'enabled' => true,
    'retention_days' => 7,
  ],

  /*
  |--------------------------------------------------------------------------
  | Post Resource - Tabs
  |--------------------------------------------------------------------------
  |
  | Define the tabs for the Post Resource table.
  | See: https://www.moox.org/docs/core/tabs
  | See: https://www.moox.org/docs/core/translatable-config
  |
  */
  'tabs' => [
    'all' => [
      'label' => 'trans//core::core.all',
      'icon' => 'gmdi-filter-list',
      'query' => [
        [
          'field' => 'deleted_at',
          'operator' => '=',
          'value' => null,
        ],
      ],
    ],
    'deleted' => [
      'label' => 'trans//core::core.deleted',
      'icon' => 'gmdi-delete',
      'query' => [
        [
          'field' => 'deleted_at',
          'operator' => '!=',
          'value' => null,
        ],
      ],
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Post Resource - Relations
  |--------------------------------------------------------------------------
  |
  | Define the relations for the Post Resource table.
  | See: https://www.moox.org/docs/core/relations
  |
  */
  'relations' => [
    'comments' => [
      'label' => 'trans//core::core.category',
      'model' => \Moox\Category\Models\Category::class,
      'table' => 'categorizables',
      'relationship' => 'categorizable',
      'foreignKey' => 'categorizable_id',
      'relatedKey' => 'category_id',
      'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
      'hierarchical' => true,
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Post Resource - Modules
  |--------------------------------------------------------------------------
  |
  | Define the modules for the Post Resource table.
  | See: https://www.moox.org/docs/core/modules
  |
  */
  'modules' => [
    'seo' => \Moox\Category\Module\Seo::class,
  ],

  /*
  |--------------------------------------------------------------------------
  | Post Resource - Features
  |--------------------------------------------------------------------------
  |
  | Define the features for the Post Resource table.
  | See: https://www.moox.org/docs/core/features
  |
  */
  'features' => [
    'auditable' => \Moox\Category\Feature\Auditable::class,
    'commentable' => \Moox\Category\Feature\Commentable::class,
  ],

  /*
  |--------------------------------------------------------------------------
  | Post Resource - Taxonomies
  |--------------------------------------------------------------------------
  |
  | Define the taxonomies for the Post Resource table.
  | See: https://www.moox.org/docs/core/taxonomies
  |
  */
  'taxonomies' => [
    'category' => [
      'label' => 'trans//core::core.category',
      'model' => \Moox\Category\Models\Category::class,
      'table' => 'categorizables',
      'relationship' => 'categorizable',
      'foreignKey' => 'categorizable_id',
      'relatedKey' => 'category_id',
      'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
      'hierarchical' => true,
    ],
    'tag' => [
      'label' => 'trans//core::core.tag',
      'model' => \Moox\Tag\Models\Tag::class,
      'table' => 'taggables',
      'relationship' => 'taggable',
      'foreignKey' => 'taggable_id',
      'relatedKey' => 'tag_id',
      'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
      'hierarchical' => false,
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Post Resource - User Models
  |--------------------------------------------------------------------------
  |
  | The User model classes available for author relationships.
  | You can define multiple user types with their display attributes.
  | See: https://www.moox.org/docs/core/user-models
  |
  */
  'user_models' => [
    \App\Models\User::class => [
      'title_attribute' => 'name',
      'label' => 'App User',
    ],
    \Moox\User\Models\User::class => [
      'title_attribute' => 'name',
      'label' => 'Moox User',
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Post Resource - API
  |--------------------------------------------------------------------------
  |
  | The API settings for the Post Resource.
  | See: https://www.moox.org/docs/core/api
  |
  */
  'api' => [
    'expose' => true,
    'write'  => true,
    'auth'   => [
      'read' => true,
      'write' => 'sanctum',
    ],
    'fields' => [
      'visible' => ['id', 'name', 'email'],
      'hidden'  => ['password'],
    ],
    'docs' => [
      'tag'     => 'User Management',
      'summary' => 'Read-only user listing & profiles',
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Post Resource - Permissions
  |--------------------------------------------------------------------------
  |
  | The Permissions for the Post Resource.
  | See: https://www.moox.org/docs/core/permissions
  |
  */
  'capabilities' => [
    'posts.view',
    'posts.create',
    'posts.update',
    'posts.delete',
    'posts.publish',
  ],

  'default_roles' => [
    'administrator' => ['*'],
    'editor'        => ['posts.*'],
    'author'        => ['posts.create', 'posts.update.own'],
    'viewer'        => ['posts.view'],
  ],
];
```

SEPTEMBER 2025

- [ ] https://laravel-news.com/nuno-maduro-laravel-starter-kit - Monorepo mit allem bauen
- [ ] Have a lokal working installation, by testing installer - Alf
- [ ] Make Screenshots and Readmes - Alf
- [ ] Base Entities vorbereiten (Item, Record, Draft, Category, Tag, News) - Aziz
  - [ ] Relation - Alf
  - [ ] Module - Alf
  - [ ] Feature - Alf
  - [ ] Tests - Kim
  - [ ] APIs - ?

- [ ] Moox Build command can build all packages
  - [ ] Test
  - [ ] Feinschliff (v. a. Readme)
  - [ ] Banner und Screenshot in Build Package, oder API?

- [ ] Monorepo package, Release Command - Kim
- [ ] GitHub all Repos clean, Docs, Screenshots, Banner - Alf
- [ ] Pimp the GitHub Org, Pimp moox-Repo and pro-Repo - Alf
- [ ] Composer.json for the Release API (stable, beta, dev) - Nickson
- [ ] Moox Installer - Mika
- [ ] Release https://github.com/mooxphp/skeleton
- [ ] Release https://github.com/mooxphp/jobs
- [ ] Release https://github.com/mooxphp/core
- [ ] Release https://github.com/mooxphp/progress
- [ ] Release https://github.com/mooxphp/clipboard
- [ ] First Package(s) to FilamentPHP.com
- [ ] Create Moox Analytics MVP for Moox.org - Alf
- [ ] Website V1: Filament V4 compatible, 5 Packages
  - [ ] Responsiveness between the gaps
  - [ ] replace content_copy and check for copy fields
  - [ ] MacBook responsive fixen on https://mooxweb.test/v2
  - [ ] Cards von V1 auf V2 übernehmen

- [ ] https://x.com/mooxphp
  - [ ] Package XYZ released.
  - [ ] Filament 4 compatible
  - [ ] Moox.org Website Relaunch

OCTOBER 2025

- [ ] Release https://github.com/mooxphp/firewall - Alf
- [ ] Release https://github.com/mooxphp/google-icons - gibt es noch nicht ... screenshot auch nicht
- [ ] Release https://github.com/mooxphp/flag-icons-square, to Blade-Icons
- [ ] Release https://github.com/mooxphp/file-icons, to Blade-Icons
- [ ] Release https://github.com/mooxphp/flag-icons-circle, to Blade-Icons
- [ ] Release https://github.com/mooxphp/laravel-icons, to Blade-Icons
- [ ] Release https://github.com/mooxphp/flag-icons-rect, to Blade-Icons
- [ ] Release https://github.com/mooxphp/flag-icons-origin, to Blade-Icons
- [ ] Create auto-counts for the Website
- [ ] Website V2: with Icons in Nav, 12 Packages
- [ ] Release https://github.com/mooxphp/press update, Media, MFA
  - [ ] Press: https://github.com/corcel/corcel/issues/655
- [ ] Release https://github.com/mooxphp/press-trainings
- [ ] Release https://github.com/mooxphp/press-wiki
- [ ] Release https://github.com/mooxphp/trainings
- [ ] Release https://github.com/mooxphp/expiry
- [ ] Website V3: Moox Press is finally ready for production, 17 Packages, 1 Bundle

NOVEMBER 2025

- [ ] Release https://github.com/mooxphp/draft to Filament
- [ ] Release https://github.com/mooxphp/record to Filament
- [ ] Release https://github.com/mooxphp/item to Filament
- [ ] Release https://github.com/mooxphp/category to Filament
- [ ] Release https://github.com/mooxphp/tag to Filament
- [ ] Release https://github.com/mooxphp/build to Filament
- [ ] Release https://github.com/mooxphp/module to Filament
- [ ] Website V4: Base Entities are ready to BUILD!, 24 Packages, 4 News

DECEMBER 2025

- [ ] Release https://github.com/mooxphp/devlink
- [ ] Release https://github.com/mooxphp/devtools
- [ ] Release https://github.com/mooxphp/monorepo - Release Command, nicht auf Filament
- [ ] Release https://github.com/mooxphp/vscode updaten - Cursor, nicht auf Filament
  - [ ] Devsense PHP Tools huge update!

- [ ] Release https://github.com/mooxphp/forge
- [ ] Release https://github.com/mooxphp/restore
- [ ] Release https://github.com/mooxphp/backup-server
- [ ] Release https://github.com/mooxphp/github
- [ ] Release https://github.com/mooxphp/packagist
- [ ] Release https://github.com/mooxphp/packages
- [ ] Release https://github.com/mooxphp/package-registry
- [ ] Release https://github.com/mooxphp/brand
- [ ] Release https://github.com/mooxphp/devops - DevOps Bundle
- [ ] Run News, Page, Components, Media, Theme on Website
- [ ] Website V5: now Development is a breeze, 37 Packages, 5 News, 2 Bundles
- [ ] X-Account with Auto-Tweets each Release

JANUARY 2025

- [ ] Release https://github.com/mooxphp/frontend + Routing
- [ ] Release https://github.com/mooxphp/featherlight + Base?
- [ ] Release https://github.com/mooxphp/components
- [ ] Release https://github.com/mooxphp/page nested Entity
- [ ] Release https://github.com/mooxphp/news
- [ ] Release https://github.com/mooxphp/media
- [ ] Release https://github.com/mooxphp/localization, fix AR, 414 localizations?
- [ ] Release https://github.com/mooxphp/data
- [ ] Release https://github.com/mooxphp/slug
- [ ] Release https://github.com/mooxphp/content - CMS Bundle
- [ ] Website V6: Moox now has a Frontend!, 47 Packages, 6 News, 3 Bundles

FEBRUARY 2026

- [ ] Release https://github.com/mooxphp/user mit https://filamentphp.com/docs/4.x/users/multi-factor-authentication
- [ ] Release https://github.com/mooxphp/user-device
- [ ] Release https://github.com/mooxphp/user-session
- [ ] Release https://github.com/mooxphp/security
- [ ] Release https://github.com/mooxphp/login-link
- [ ] Release https://github.com/mooxphp/impersonate
- [ ] Release https://github.com/mooxphp/permission PRO
- [ ] Release https://github.com/mooxphp/passkey PRO
- [ ] Satis, Package Management including recurring payment for PRO
- [ ] Website V7: User Management done right, 55 Packages, 2 Pro, 7 News
- [ ] Wear Moox
- [ ] Prepare for Laracon

MARCH 2026

- [ ] Release https://github.com/mooxphp/product
- [ ] Release https://github.com/mooxphp/cart
- [ ] Release https://github.com/mooxphp/inquiry cart to form
- [ ] Release https://github.com/mooxphp/customer PRO
- [ ] Release https://github.com/mooxphp/order PRO
- [ ] Release https://github.com/mooxphp/wishlist PRO
- [ ] Release https://github.com/mooxphp/staff PRO
- [ ] Release https://github.com/mooxphp/payment PRO
- [ ] Release https://github.com/mooxphp/subscription PRO
- [ ] Release https://github.com/mooxphp/seo PRO
- [ ] Release https://github.com/mooxphp/commerce - Shop Bundle
- [ ] Website V8: Moox Commerce is here, 66 packages, 9 Pro, 8 News

APRIL 2026

- [ ] Release https://github.com/mooxphp/json
- [ ] Release https://github.com/mooxphp/audit PRO
- [ ] Release https://github.com/mooxphp/rewind PRO
- [ ] Release https://github.com/mooxphp/post PRO
- [ ] Release https://github.com/mooxphp/comment PRO
- [ ] Release https://github.com/mooxphp/rating PRO
- [ ] Release https://github.com/mooxphp/review PRO
- [ ] Release https://github.com/mooxphp/analytics PRO
- [ ] Release https://github.com/mooxphp/forms PRO
- [ ] Release https://github.com/mooxphp/notifications PRO
- [ ] Release https://github.com/mooxphp/markdown PRO
- [ ] Pro Tiers, monthly fee, flatrate, early access, 77 packages, 19 Pro

MAI 2026 +++

- [ ] Headless (API), Block Editor ...
- [ ] Release https://github.com/mooxphp/calendar PRO
- [ ] Release https://github.com/mooxphp/ai PRO
- [ ] Release https://github.com/mooxphp/builder-pro - delete
- [ ] Release https://github.com/mooxphp/connect PRO
- [ ] Release https://github.com/mooxphp/sync PRO
- [ ] Release https://github.com/mooxphp/builder PRO
- [ ] Release https://github.com/mooxphp/mail PRO
- [ ] Release https://github.com/mooxphp/theme-base - delete?

- [ ] Release https://github.com/mooxphp/theme-moox - website?
- [ ] Themes, Themes, Themes and Commercial Bundles, 100 Packages, 50 Pro, 30 Themes ...
