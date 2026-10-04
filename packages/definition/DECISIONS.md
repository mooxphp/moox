# Decision record

## Supplied by the concept

- One canonical definition is projected. Models, migrations, forms, and tables do not keep independent copies of the field list.
- Definitions are framework-independent. Laravel and Filament are projections. The package must be usable without either.
- The package does not depend on Moox Core.
- `moox.json` is the conceptual source of truth. Its executable schema and moox Builder are later work. PHP definitions must work without them.
- Features own semantics. Target companions only add behavior the generic projection cannot derive.
- A feature contribution is not automatically system-managed.
- Identity declares primary allocation, UUID generation, ULID generation, uniqueness, immutability, and system-managed write ownership. Runtime performs those operations. It is not the only place they exist.
- Universal attributes are `name`, `label`, `description`, `section`, `required`, `nullable`, `default`, `unique`, `index`, `immutable`, `systemManaged`, and `generated`. `role` is a projection hint.
- `required` and `nullable` are different. A required field cannot be nullable. A field that is neither must declare a default, be system-managed, or be generated.
- Defaults are deterministic and serializable. Closures are not defaults.
- An absent default is different from an explicit null default.
- Field `unique` and `index` are single-field. Composite unique constraints and composite indexes belong to the entity.
- `dateTime` is a timezone-aware instant stored as a UTC timestamp.
- Semantic field types are the full vocabulary in the concept, not the short example constructors.
- Relation types use Eloquent names. Keys are explicit. The foreign-key storage type follows the referenced field. A missing display attribute does not invalidate the relation or the migration.
- Relations may target entities in other packages.
- Filament customization returns the native component, can replace one component, preserves untouched components, and stays local to the projection instance.
- Audit field lists, Publishing semantics, and a mandatory repository boundary are not decided.

## Technical choices

- Declarations are mutable builders. Fields and attributes are immutable. Resolution copies the field map into a new `ResolvedEntity` and applies features to a sandbox entity, so the caller's declaration is not mutated.
- `Catalog::resolve()` validates and composes. Calling it again uses a new resolver and the same rules, so repeated resolution does not depend on leftover mutable state.
- Conflicts throw `CompositionConflict`. Invalid shape throws `InvalidDefinition` with one issue per missing fact. Overrides are `Entity::override()` for a contributed field or relation, and `Feature::overrides()` when a later feature replaces an earlier contribution.
- Type-inherent rules from the field vocabulary are validation rules, not silent fixes. A `uuid` field must be unique and immutable. An `id` field must be a non-null unique immutable system-managed primary generator. Identity sets those attributes. A bare `Field::uuid()` does not pass validation.
- Generators named `primary`, `uuid`, and `ulid` are valid only on `id`, `uuid`, and `ulid`. Other generator names may be declared. The Laravel runtime performs `uuid` and `ulid` and leaves `primary` to the database. Any other generator fails at runtime with the field and generator name.
- Relations are `Relation` objects, not a second field of type `relation`. A field of type `relation` is rejected and points at `addRelation()`. The vocabulary still lists the type so it is not dropped or remapped onto a string column.
- `belongsTo.ownerKey`, `hasOne`/`hasMany` `foreignKey`, and many-to-many `relatedKey` must equal the referenced `EntityReference` field. The reference always names package, entity, and field. Nothing is filled in as `id`.
- `hasOne` and `hasMany` do not require the foreign key to already exist as a semantic field on the target. The column lives on the other entity. `belongsTo` does require the referenced field to exist, because its storage type is copied from that field.
- `morphType` and `morphId` are required in addition to `morphName`. Column names are not derived from `morphName`.
- Pivot `timestamps` and relation `nullable` are constructor arguments with no default, so omission is reported.
- Storage is described by framework-free `StorageColumn` values. The Laravel migration is a blueprint applicator, not a PHP source generator. `IdentityMigration` checks that the resolved identity fields are still present. It does not add columns.
- Eloquent models extend `Moox\Definition\Laravel\Model`. Fillable attributes are the non-system-managed fields plus local `belongsTo` foreign keys. System-managed fields are omitted from mass assignment. Updates to immutable or system-managed fields throw. Empty generated `uuid` and `ulid` values are assigned on create. A non-empty value already set on the new instance is kept as the initial assignment. `id` is allocated by the database.
- There is no implicit primary key. An entity without exactly one `id` field cannot be used as an Eloquent model.
- There is no implicit table name and no automatic `created_at` / `updated_at`. Eloquent timestamps are off unless a future feature declares those fields.
- `dateTime` uses `UtcDateTimeCast`, which normalizes to UTC `Y-m-d H:i:s` and returns `DateTimeImmutable`. The Filament picker is given timezone `UTC` for storage. A user-specific presentation timezone is not wired, because this package has no user timezone source.
- Decimal defaults are integers or digit strings. Floats are rejected for decimal, money, and percentage so the scale is exact. Float fields may use a float default.
- `keyValue` does not accept `valueType: enum`, because that specification has no enum class.
- Choice arrays are `value => label`.
- Static and backed-enum choice options can become Filament options. `provider` and `definition` sources fail the Filament projection until a binding exists. They stay valid definitions.
- Filament `Checkbox` and `Toggle` call `default(false)` in their own setup. When the definition has no default, the projection clears that component default so the component does not invent one.
- A foreign-key column is indexed because the database constraint needs an index. That index is not an `index` attribute on the relation.
- `hasOne`, `hasMany`, and `belongsToMany` add no column to the declaring entity's table. Pivot tables are created only through `applyPivot()`.
- Morph and through relations validate, and their Laravel and Filament projections report the relation type as unsupported.
- `image`, `file`, `media`, and `taxonomy` validate, including their targets, and the storage projection reports them as unsupported. Their column layout depends on media and taxonomy storage that this package does not own.
- Password hashing is not applied. The concept names a moox hashing standard and does not define the algorithm. The column is `varchar(255)` and the Filament component is a password input.
- Telephone values are strings. No pattern is invented.
- Companion classes are found by suffix: `Identity` + `Migration`. They are loaded only when that projection runs.

## Unresolved

- Executable `moox.json` and moox Builder.
- Whether a repository is a mandatory access layer or an optional one. No repository class is included.
- Audit fields, actor models, and Publishing semantics.
- Content, Ordering, Localization, Author, Hierarchy, Status, Protection, Archive, Soft Deletes, and Scheduling field schemas. Several are still marked TBD in the concept. Ordering and Content are the examples of editable feature fields. The mechanism is tested with a test feature, not a guessed Content schema.
- Hierarchical taxonomy's requirement that the term definition support hierarchy. Hierarchy itself is not specified, so the check is not enforced.
- The JSON Schema dialect for `json`. A non-empty serializable schema is required. It is not interpreted.
- The currency registry behind `money`.
- Color and telephone enforcement on write, beyond definition defaults for color.
- User-timezone presentation for `dateTime`.
- Options bound from `provider` and `definition` sources.
- Code generation of model, migration, and resource files. Runtime projection is the implemented path. Generation remains possible later where an artifact is required.
- Package targets, mapping ownership, policies, representation, and seeders from the package-level `moox.json` sketch.
- Feature dependencies beyond declaration order and explicit overrides.
- Relation overrides use the same `override()` list as fields. A separate relation-override API is not needed yet.
