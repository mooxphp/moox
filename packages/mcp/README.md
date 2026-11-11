# Moox MCP

The Moox MCP package uses [Laravel MCP](https://laravel.com/ai/mcp) to provide a model-agnostic interface for AI agents interacting with the Moox ecosystem.

It exposes Moox capabilities — such as package discovery, scaffolding, wiring, and installation — through MCP Tools, Resources, and Prompts. This allows AI systems to make informed decisions and execute Moox development workflows programmatically.

## Installation

```bash

composer require moox/mcp
php artisan moox:install

```

The installation command will:

- register the Moox MCP servers
- configure the MCP routes
- prepare the Moox package registry
- enable integration with AI agents

## Usage

The Moox MCP Server is designed to be used by:

- Moox AI (Moox internal AI features)
- Laravel Boost (Moox Core feature) for AI-assisted development
- external MCP-compatible AI clients

Through MCP, AI agents can:

- inspect the Moox package registry
- discover available entities, taxonomies, modules, and plugins
- recommend packages for a specific feature
- scaffold new packages using Moox build commands

Further documentation will be added as the Moox MCP architecture evolves.

## Servers

The package provides these MCP servers:

- CoreServer - all Moox Core principles and features
- PackageServer - lookup packages, make wise decisions about installing or building with them
- BuildServer - create Entities, Taxonomies, Modules and more
- JobsServer - see Job status, advanced control over jobs in Laravel
- MediaServer - work with files and media in Moox Media

for shipping scoped tools, resources and prompts inside.
