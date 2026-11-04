---
status: accepted
date: 2026-11-03
---

# Recipient settings table

## Context

ADR 0021 made recipient preferences a port but left open where they are stored (mooxphp/e-billing#120 Part B). The portal settings section `Moox\EBilling\Portal\InvoiceSettingsSection` still wrote `customers.preferred_ebilling_format` and `customers.send_visual_copy`, and the default binding was the null resolver, so nothing the recipient chose reached generation unless the host wrote its own resolver. The format and the visual-copy flag were two independent inputs, so a contradicting pair could be stored (for example a ZUGFeRD file with "XML only").

## Decision

- **E-billing owns a settings table.** `ebilling_settings` (stub `create_ebilling_settings_table`, registered in `EBillingServiceProvider::hasMigrations`): `id`, `owner_type` + `owner_id` (strings, a morph to any model), `preferred_ebilling_format` (nullable string), `ebilling_recipients` (nullable JSON), timestamps, unique (`owner_type`, `owner_id`). `ebilling_recipients` is reserved for a later portal role model and is not read anywhere yet.
- **Model.** `Models\EbillingSetting` has `owner()` (morphTo), `for(Model)` and `forReference(type, id)`, and casts the format to the enum and recipients to an array. The morph is named `owner`, not `settable`: `settable` collides with `Model::setTable()` because PHP method names are case-insensitive.
- **One stored choice covers format and visual copy.** `Enums\RecipientFormatChoice` has three cases: `zugferd` (format `zugferd`), `xrechnung` (format `xrechnung`, no visual copy) and `xrechnung_with_pdf` (format `xrechnung`, visual copy). `preferences(): RecipientPreferences` maps a choice to the port DTO, `label()` and `options()` feed the select. A contradicting pair cannot be stored.
- **Default binding is the settings resolver.** `Support\SettingsRecipientPreferenceResolver` is bound only when the host has not bound `RecipientPreferenceResolver`. It reads the `ebilling_settings` row owned by the party of the document's active `buyer_contact` attribution (`owner_type` and `owner_id` are the attribution's `party_type` and `party_id`, ADR 0021). No active `buyer_contact`, no row or no choice returns empty preferences, so the config defaults apply. `NullRecipientPreferenceResolver` stays available for hosts that want no preferences.
- **Portal section.** `InvoiceSettingsSection::fill()` and `persist()` take any `Model $owner`. The section shows one required select `preferred_ebilling_format` with the three choices; `persist()` upserts the owner's row and refuses an unknown value (`ValueError` from `RecipientFormatChoice::from`); `fill()` falls back to the choice matching the config defaults. It no longer reads or writes `customers.preferred_ebilling_format` / `send_visual_copy`, no longer reads `config('customer.preferred_ebilling_formats')`, and its constructor no longer takes `FormatRegistry`.
- **Keyed by contact, as a morph.** Settings belong to the contact because a portal login sees every debtor of its contact. The key is a morph so the owner model stays swappable.

## Considered options

- **Key by `customer_id` with a foreign key.** Rejected: a portal login sees all debtors of its contact, so one login would edit several unrelated rows. A morph also keeps the owner model swappable.
- **Two columns, `preferred_ebilling_format` and `send_visual_copy`.** Rejected: contradicting pairs can be stored. One enum choice cannot.
- **Store the settings in the host.** Rejected: e-billing owns the knowledge of what a choice means. The earlier `customers` columns also vanished silently when the host recreated `customers`.

## Consequences

- **Breaking for hosts that rely on the portal section:** its signature and form change, and the lang keys `send_visual_copy`, `send_visual_copy_with_pdf` and `send_visual_copy_xml_only` are removed in favour of `format_choice.{zugferd|xrechnung|xrechnung_with_pdf}`.
- **Behaviour change for hosts that bound nothing:** the default binding is no longer the null resolver. With no rows in `ebilling_settings` the result is the same (config defaults).
- Hosts publish and run the `create_ebilling_settings_table` migration. Values previously stored in `customers.preferred_ebilling_format` / `send_visual_copy` are not migrated by the package.
- Glossary in this package's `CONTEXT.md`: *Format choice*.
