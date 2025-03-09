# Idea

Moox Markdown

-   https://github.com/GrahamCampbell/Laravel-Markdown - preferably
-   https://github.com/spatie/laravel-markdown

## Docs Renderer

```php

return [
	"Getting Started" => [
		"Introduction" => "/docs/Introduction.md",
		"Installation" => "/docs/Installation.md",
		"Updates" => "/docs/Updates.md",
		"Configuration" => "/docs/Configuration.md"
	],
	"Core Concepts" => [
		"Introduction" => "/docs/Core.md",
		"Entities" => "/docs/Packages/Core/Entity.md",
		"Items" => "/docs/Packages/Items",
		"Taxonomies" => "/docs/Packages/Core/Taxonomy.md",
		"Modules" => "/docs/Packages/Core/Module.md",
		"Workflows" => "/docs/Packages/Core/Workflow.md"
	],
	"Base Packages" => [
		"Introduction" => "/docs/BasePackages.md",
		"Search" => "/docs/Packages/Search"
		"Localization" => "/docs/Packages/Localization",
		"Frontend" => "/docs/Packages/Frontend",
		"Slugs" => "/docs/Packages/Slugs",
		"Navigation" => "/docs/Packages/Navigation",
		"Media" => "/docs/Packages/Media",
		"Packages" => "/docs/Packages/Packages",
		"JSON" => "/docs/Packages/Json",
		"Markdown" => "/docs/Packages/Markdown"
	],
	"Theming" => [
		"Introduction" => "/docs/Theming.md",
		"Themes" => "/docs/Packages/Themes",
		"Theme Light" => "/docs/Packages/ThemeLight",
		"Admin Theme" => "/docs/Packages/AdminTheme"
	],
	"Content" => [
		"Introduction" => "/docs/Content.md",
		"Pages" => "/docs/Packages/Page",
		"Posts" => "/docs/Packages/Posts",
		"News" => "/docs/Packages/News",
		"Taxonomy" => "/docs/Packages/Taxonomy",
		"Category" => "/docs/Packages/Category",
		"Tags" => "/docs/Packages/Tag",
	],
	"Press" => [
		"Introduction" => "/docs/Press.md",
		"Press" => "/docs/Packages/Press",
		"Press Wiki" => "/docs/Packages/PressWiki",
		"Press Trainings" => "/docs/Packages/PressTrainings"
	],
	"Community" => [
		"Introduction" => "/docs/Community.md",
		"Comment" => "/docs/Packages/Comment",
		"Rating" => "/docs/Packages/Rating",
		"Review" => "/docs/Packages/Review",
		"Discussion" => "/docs/Packages/Discussion",
		"Wiki" => "/docs/Packages/Wiki"
	],
	"Shop System" => [
		"Introduction" => "/docs/Shop.md",
		"Product" => "/docs/Packages/Product",
		"Virtual Product" => "/docs/Packages/VirtualProduct",
		"Subscription" => "/docs/Packages/Subscription",
		"Bundle" => "/docs/Packages/Bundle",
		"Add On" => "/docs/Packages/AddOn",
		"Category" => "/docs/Packages/ShopCategory",
		"Tag" => "/docs/Packages/ShopTag",
		"Customers" => "/docs/Packages/Customer",
		"Company" => "/docs/Packages/Company",
		"Cart" => "/docs/Packages/Cart",
		"Payment" => "/docs/Packages/Payment",
		"Wishlist" => "/docs/Packages/Wishlist"
	],
	"Users" => [
		"Introduction" => "/docs/Users.md",
		"User" => "/docs/Packages/User",
		"User Session" => "/docs/Packages/UserSession",
		"User Device" => "/docs/Packages/UserDevice",
		"Security" => "/docs/Packages/Security",
		"Login Link" => "/docs/Packages/LoginLink",
		"Passkey" => "/docs/Packages/Passkey",
		"Permission" => "/docs/Packages/Permission"
	],
	"Sending" => [
		"Introduction" => "/docs/Sending.md",
		"Mails" => "/docs/Packages/Mail",
		// Mail Templates, Monitor, ...
		"Notifications" => "/docs/Packages/Notification"
	],
	"System" => [
		"Introduction" => "/docs/System.md",
		"Audit" => "/docs/Packages/Audit",
		"Jobs" => "/docs/Packages/Jobs"
		"Connect" => "/docs/Packages/Connect",
		"API" => "/docs/Packages/Api"
		"Sync" => "/docs/Packages/Sync",
		"Scheduler" => "/docs/Packages/Scheduler"
	],
	"Tools" => [
		"Introduction" => "/docs/Tools.md",
		"Expiry" => "/docs/Packages/Expiry",
		"Trainings" => "/docs/Packages/Trainings",
		"Calendar" => "/docs/Packages/Calendar",
		"Booking" => "/docs/Packages/Booking",
		"Contact Form" => "/docs/Packages/ContactForm",
		"Contact Cards" => "/docs/Packages/ContactForm",
		"Analytics" => "/docs/Packages/Analytics",
		"Project" => "/docs/Packages/Project"
	],
	"Data" => [
		"Introduction" => "/docs/Data.md",
		"Data" => "/docs/Packages/Data",
		"Data Pro" => "/docs/Pro/Data"
	],
	"Icons" => [
		"Introduction" => "/docs/Icons.md",
		"Flags" => "/docs/Packages/Flags",
		"Files" => "/docs/Packages/Files"
	],
	"DevOps" => [
		"Introduction" => "/docs/Devops.md",
		"Devops" => "/docs/Packages/Devops",
		"GitHub" => "/docs/Packages/Github",
		"Packagist" => "/docs/Packages/Packagist",
		"Package Registry" => "/docs/Packages/PackageRegistry",
		"Forge" => "/docs/Packages/Forge",
		"Backup" => "/docs/Packages/Backup",
		"Restore" => "/docs/Packages/Restore",
		"Health" => "/docs/Packages/Health"
		"Backup Server" => "/docs/Packages/BackupServerUi"
	],
	"AI" => [
		"Introduction" => "/docs/Ai/Introduction.md",
		"Moox AI" => "/docs/packages/Ai",
		"Moox RAG" => "/docs/packages/Rag"
	]
	"Coding" => [
		"Introduction" => "/docs/Coding/Introduction.md",
		"Monorepo" => "/docs/Packages/Monorepo",
		"Builder" => "/docs/Packages/Builder",
		"Skeleton" => "/docs/Packages/Skeleton",
		"Devlink" => "/docs/Packages/Devlink",
		"VS Code" => "/docs/Packages/VSCode"
	],
	"Development" => [
		"Introduction" => "/docs/Development/Introduction.md",
		"Translation" => "/docs/Development/Translation.md",
		"Guidelines" => "/docs/Development/Guidelines.md",
		"Contributors" => "/docs/Development/Contributors.md",
		"Sponsors" => "/docs/Development/Sponsors.md"
	]
];


// Replacements
return [
	"Important" => "ImportantReplacer.php"
];

// Readme Generator
return [
	"Banner" => "BannerGenerator.php",
	"Title" => "TitleGenerator.php",
	"Description" => "DescriptionGenerator.php",
	"Capabilities" => "CapabilitiesGenerator.php",
	"Screenshot" => "ScreenshotGenerator.php",
	"Requirements" => "/docs/GettingStarted/Requirements.md",
	"Installation" => "/docs/GettingStarted/Installation.md",
	"Updating" => "/docs/GettingStarted/Updating.md",
	// can fallback to just print the config
	"Configuration" => "/packages/package/docs/Configuration(.md)",
	"Usage" => "/packages/package/docs/Usage(.md)",
	"Beginner" => "/packages/package/docs/Beginner(.md)",
	"Advanced" => "/packages/package/docs/Advanced(.md)",
	// Everything else would come after advanced, like credits ...
	"Credits" => "/packages/package/docs/Credits(.md)",
	"Classes" => "ClassesGenerator.php",
	"Models" => "ModelsGenerator.php",
	"Moox" => "/docs/GettingStarted/WhatIsMoox.md",
	"Development" => "/docs/Development/Development.md",
	"Changelog" => ...
	"Roadmap" => ...
	"Translation" => "/docs/Development/Translation.md",
	"Contributors" => "/docs/GettingStarted/Contributors.md",
	"Sponsors" => "/docs/GettingStarted/Sponsors.md",
	"Security" => "/docs/GettingStarted/Security.md",
	"License" => "/docs/GettingStarted/License.md"
];

```
