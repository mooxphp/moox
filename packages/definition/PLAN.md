# moox/definition plan

```
packages/definition/src
├── Definitions
│   ├── Package, Entity, Field, Relation, Feature, Capability
│   └── field vocabulary, universal attributes, constraints
├── Validation
├── Resolution
│   └── Catalog, Resolver, ResolvedEntity
├── Projection
│   └── framework-free storage description
├── Features
│   └── Identity
│       ├── Identity
│       ├── IdentityModel
│       ├── IdentityMigration
│       └── IdentityFilament
├── Laravel
│   ├── Migration
│   └── Model
└── Filament
    ├── Resource
    ├── Page
    ├── form components
    └── table columns
```

## Implemented

- Framework-independent package, entity, field, relation, feature, and capability definitions.
- The semantic field vocabulary and the universal attributes.
- Validation of the rules stated in the concept.
- Deterministic resolution, conflict detection, and explicit overrides.
- Identity, including declared generation, uniqueness, immutability, and write ownership.
- Cross-package relations with explicit keys and referenced-field storage.
- Laravel blueprint projection and Eloquent model projection for supported types.
- Filament runtime projection with native component access, replacement, and per-instance state.
- A concrete resource, model, and migration delegate to those projections. They are not generated.

## Acceptance case

An article entity uses Identity plus `article_number`. A notes feature contributes an editable field. A product group in another package is referenced by `ulid`, not by `id`.

## Left open

The unresolved list in [DECISIONS.md](DECISIONS.md) is part of this plan. In particular: Audit, Publishing, the repository boundary, executable `moox.json`, and moox Builder.
