---
status: accepted
---

# Recipient format preference via a host port (four-layer model)

## Context

Recipients do not all accept the same e-invoice deliverable. Until now, generation froze a single installation-wide format (`default_format` / customer column) onto the document, and hybrid profiles were pulled from `config('zugferd.profile')`. That cannot serve two recipients with different format needs in one run, and it conflates several independent decisions into one knob.

Issue [#16](https://github.com/mooxphp/e-billing/issues/16) requires a host-bound resolver consulted **before** generation, returning a single preference value — not filename, guideline-id, or XMP knobs (those are derived by the underlying library from the profile).

## Decision

Model the deliverable as four layers; only the first two are selected per recipient today:

| Layer | Meaning | Who decides |
| --- | --- | --- |
| **Semantic** | Business content (EN 16931 terms) | Invoice / parser |
| **Syntax** | FormatRegistry id (`xrechnung`, `zugferd`, `factur-x`) + optional hybrid profile | Recipient preference port → orchestrator |
| **Container** | Pure XML vs PDF/A-3 hybrid | FormatDefinition.artifactKind |
| **Transport** | How the artifact is delivered (mail, Peppol, …) | Out of scope here |

**Defaults.** One config object `e-billing.default` `{ format, profile }` plus `allowed_profiles` for preference overrides.

**Port.** `RecipientFormatPreferenceResolverInterface::resolve(EbillingDocument): ?FormatPreference`. `null` means no preference. Value object: `{ format, profile? }`.

**Default binding.** `CustomerFormatPreferenceResolver` reads `Customer.preferred_ebilling_format` (same customer resolution as before: `customer_id` with trashed, else `CustomerMatcher` on invoice `customer_number`). Always `profile: null`. Empty/missing → `null`.

**Orchestrator.** `EBillingFormatResolver` remains the only entry `GenerateArtifactJob` calls. It returns `EffectiveFormat { format, profile }`:

- Freeze: if `xml_storage_path` is set → use frozen `document.format` + frozen `document.profile` (port not re-consulted). Both columns are required; empty/null `document.format` or `document.profile` **throws**. Preference / allowlist changes apply to **future documents only**.
- Else call the port; `null` → `config('e-billing.default.format')` + registry profile for that format.
- Preference format must exist in `FormatRegistry` or **throw** (`UnknownFormatException`) — no silent fallback.
- Profile rules: pure-XML formats (`FormatDefinition.artifactKind === Xml`, today only `xrechnung`) must have `profile === null` or throw; hybrid formats (`artifactKind === Pdf`) with a non-null preference profile must be in `config('e-billing.allowed_profiles')` (default `['EN16931']` only — EXTENDED refused until a later ticket) or throw. Effective profile = `preference.profile ?? definition.profile`.
- **Invariant (revisit for UBL / Peppol XML):** `assertPreferenceProfileAllowed` keys off `ArtifactKind::Xml`, not the `xrechnung` id. That encodes “pure XML never takes a hybrid profile override”. If a future XML syntax (UBL, Peppol BIS, …) needs a selectable profile/conformance id on the preference port, this branch must be revisited — do not assume every `ArtifactKind::Xml` format refuses `FormatPreference.profile`.

**Registry bake-in.** `xrechnung` → `XRECHNUNG`; `zugferd` / `factur-x` → `config('e-billing.default.profile')` (must be in `allowed_profiles`). Pipeline always passes an explicit profile into `moox/zugferd`; `zugferd.profile` config is removed.

## Consequences

- Hosts may bind a custom resolver (Peppol capability, CRM, …) without changing the job.
- Unknown or disallowed preferences fail loudly at generation time.
- Hybrid profile expansion (EXTENDED, etc.) is a config allowlist change plus any generator support — not a second preference surface.
- Peppol / UBL work must re-check the pure-XML profile invariant above before treating transport preference as a hybrid profile override.
- `resolveSendVisualCopy` is unchanged and stays outside the format port.

