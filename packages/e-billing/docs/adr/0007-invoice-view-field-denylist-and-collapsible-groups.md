---
status: accepted
date: 2026-09-22
---

# Invoice ViewInvoice: UI denylist and collapsible field groups stay out of MoSCoW

## Context

`field_validation.invoice_fields` (MoSCoW) answers one question: how hard the pipeline treats emptiness or divergence (`must` / `should` / `could`). Operators also need ViewInvoice chrome: hide system-stamped fields they should not edit, and collapse field groups so exception-driven review stays scannable.

Stuffing `visible` into each MoSCoW entry would turn a flat priority map into a mini-schema, confuse host diffs, and couple validation severity to presentation. Inferring hide/show from priority (e.g. hide all `could`) is wrong: hosts often hide stamped **`must`** codes while still validating them.

Header groups today are always expanded cards; line items already use native `<details>`. Reviewers who only open an invoice when something is wrong still need a way to find blocking must-findings inside collapsed groups.

## Decision

### 1. Config tree: `invoice_ui` (not MoSCoW)

All ViewInvoice presentation config lives under `e-billing.invoice_ui`:

- **Field denylists** (lists of field keys): `invoice_fields_hidden`, `invoice_line_fields_hidden`
- **Per-group default open**: `field_groups.{document|supplier|buyer|delivery|totals|notes}.default_open` (bool)

Package defaults: denylists `[]`; every `default_open` **`true`** (discoverability for new hosts). Hosts override.

MoSCoW stays a flat `field => must|should|could` map under `field_validation`.

### 2. Denylist semantics

- **Always hide** listed fields on ViewInvoice, even when filled.
- **Validation unchanged** — hidden fields still run through `InvoiceFieldValidator` / MoSCoW.
- Apply in the ViewModel / Blade path that builds visible rows (e.g. filter inside `buildFields` / line `relevantFields`), not by weakening the validator.
- After filtering: if a group has **zero visible fields**, **do not render** the section (no empty `<details>` shell).

### 3. Collapsible groups

- Header groups and notes use the same `<details>` pattern as line items.
- **Force-open** when the group (or line) has at least one **visible** blocking must-finding: status in `missing` / `invalid` / `unmatched` / `needs_review` for a field whose MoSCoW priority is `must`.
- **Marker** on the `<summary>`: text + colour (e.g. issue count / “Must missing”) — not colour-only.
- **Denylisted fields do not count** toward marker or force-open (opening a section for a problem the UI deliberately hides is worse than relying on the status banner / score).
- Line `<details>` use the same force-open + marker rules per line.
- Notes stay a **sibling** after delivery (not nested inside the delivery group), with the same collapse / force-open / marker rules and a `field_groups.notes.default_open` key.

### 4. What this is not

- Not Filament form/table column visibility (ViewInvoice field rows only for v1).
- Not “hide when empty” clutter control.
- Not skipping validation when hidden.
- Not encoding visibility inside MoSCoW string values.

## Considered options

- **Embed `{priority, visible}` in each MoSCoW entry.** Rejected: overloads validation config; noisy host diffs; conflates two questions.
- **Allowlist of visible fields.** Rejected: forces hosts to enumerate ~40 keys; every new field is invisible until listed. Denylist matches “hide a few stamped fields.”
- **Hide only when empty.** Rejected: stamped must-codes still clutter the UI once filled; denylist means “operators should not see this row.”
- **Skip validation when hidden.** Rejected: accidental hide of a `must` would silently unblock dispatch readiness.
- **Forbid hiding `must`/`should`.** Rejected: the primary use case is hiding stamped must-codes (`payment_means`, `vat_category`).
- **One shared denylist for header and lines.** Rejected: same key (e.g. `vat_category`) may need different treatment; mirror MoSCoW’s two maps.
- **Default all groups open; close only supplier.** Rejected as the package default story for hosts whose operators only open ViewInvoice on exceptions — those hosts set all `default_open` to `false` and rely on force-open + markers. Package still ships all `true`.
- **Marker / force-open on any non-ok status (including should).** Rejected for v1: marker means “blocking must work,” not soft warnings.
- **Force-open headers only, not lines.** Rejected: with collapsed headers, line must-findings would stay buried inside closed line `<details>`.

## Consequences

- Implementers add `invoice_ui` to the package config publish and filter/collapse in ViewModel + Blade (`invoice-field-groups`, `invoice-notes`, `invoice-line-table`).
- Hosts that hide stamped codes accept that a missing hidden `must` still blocks the pipeline but will **not** force-open a section; the status banner / readiness score remain the signal — fix is stamp/config, not a field row.
- Further host-specific denylist growth (e.g. full seller Stammdaten) is config-only; no package change if keys already exist on the ViewModel.
- Docs: package README / config comments describe `invoice_ui`; do not document denylist inside the MoSCoW block.
