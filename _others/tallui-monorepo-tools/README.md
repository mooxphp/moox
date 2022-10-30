# Monorepo Tools

Our Monorepo is highly automated. There are a lot of Github Actions that take care of:

- Testing, Formatting and Code Quality
- Pushing all of our packages and apps to single repos (see Monorepo Split Workflows)
- Crafting new releases of all packages from a single changelog

## Changelog

Please note every change in [CHANGELOG.md](../../CHANGELOG.md). All new changes should go under develop @ current. When a new release is ready, the release-manager can insert the heading with the new release tag and date. Our Monorepo will automagically craft a new release for the Monorepo and all updated packages.

```markdown
# Changelog

This is the changelog for all TallUI packages. Please note all changes here separated by package as follows:

## develop @ current

### tallui-web-components

- This is an unreleased change

## v0.0.2 @ 2022-09-01

### tallui-web-components

Some text:

- Working at some wip-things

### tallui-core

- Feature XXX
- Anything else?

Some Text between.

### tallui-app-components

Only text here.

## v0.0.1 @ 2022-08-01

### tallui-app-components

- New component XXX
- Bugfix in component XXX

### tallui-core

- Feature XXX

```

If you forget to mention a change, that change will be missing in the changelog. Not a big deal. But if you forget to mention a package, this package will not be versioned at all. Means if there are changes on this package that.
