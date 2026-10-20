---
status: accepted
date: 2026-10-20
---

# Postal fields decide address corroboration

## Context

Address corroboration (mooxphp/e-billing#25, #98) checks that the address printed on a document exists in the master data of the matched customer or company. It compared the address fingerprint (street, postal code, country) and, in addition, required the master-data `street2` to equal the document's `line2`, the joined additional address lines (BT-51/BT-52).

Master-data addresses rarely carry `street2`, while documents legitimately print additional lines: a department, a business-line descriptor, an owner line. With the exact comparison, a correct address with such a line never corroborated and went to review. The reverse case, a line in master data that the document does not print, was not reported at all.

## Decision

- **Postal fields decide.** An address is corroborated when street (BT-35 / `line1`), postal code and country match exactly (trimmed) and the city (BT-37) matches. City was not compared before. It is compared word by word: lower-cased, split on anything that is not a letter or digit.
  - Same words: match, no information.
  - One city contains the other as a contiguous run of whole words ("Town / Region" against "Town", "Town-District" against "District"): match, but a master-data divergence.
  - Otherwise, including sharing only part of a word ("Townberg" against "Town"): no match.
  - A city empty in master data or on the document is not compared (absent on the master-data side is not a divergence, #25; a document without a city corroborated before and still does).
- **Additional lines are compared separately and normalised** (BT-51/BT-52 against master-data `street2`): case, whitespace, surrounding punctuation and symbols, and line order ignored. A difference in either direction is a divergence, never a block.
- **An address without divergence wins** over the first divergent one, within a role tier and across tiers: an agreeing postal or billing address beats a divergent delivery address.
- **Result shape.** The corroborator returns `AddressMatch` (`exists`, `matched_id`, optional `divergence: AddressDivergence`). `AddressDivergence` holds `city` (the master-data city as stored, trimmed) and/or `additional_lines` (the master-data `street2` lines joined with ", ", empty when master data has none). `corroborateDeliveryParty()` passes `divergence` through.
- **A divergence is information.** For `customer_address` and `delivery_address` (the buyer's own site and a consignee of another company) the field stays `db_validated` and carries `reason: address_differs_from_master_data` plus `master_data_city` and/or `master_data_additional_lines` (only the differing ones; `withAddressDivergence()`). `InvoiceFieldLabels::hint()` shows one information hint, for example "Master data differs: city "Town", additional address lines "Goods Inwards" (does not block)."
- A differing street, postal code or country, or a city that is neither the same nor a whole-word subset, is not corroborated.

This is the principle of ADR 0003 applied to additional lines: a divergence may flag, but never overturn a match.

## Considered options

- **Keep the exact `street2` / `line2` comparison.** Rejected: it puts correct addresses on review whenever master data has no `street2`.
- **Ignore additional lines entirely.** Rejected: a stale or missing line in master data is evidence worth showing, and costs nothing as information.
- **Change `AddressFingerprint` to cover additional lines.** Rejected: the fingerprint is shared and stays unchanged; the comparison lives in `AttributionCorroborator`.
- **Compare the city exactly.** Rejected: a district or region suffix would send a correct address to review.
- **Leave the city out of the comparison.** Rejected: it let an address with a genuinely different city corroborate.
- **Treat any shared text as a city match.** Rejected: partial-word overlap ("Townberg" against "Town") would match different places; whole words only.

## Consequences

- Fewer false review prompts on addresses that carry an additional line.
- A wrong additional line no longer blocks; it is only shown.
- A city spelled with a district or region suffix now corroborates with information instead of going to review.
- A genuinely different city no longer corroborates. Before this change the city was not compared, so such an address used to corroborate.
- The verdict snapshot stores the differing master-data values in the field validation entry (`master_data_city`, `master_data_additional_lines`), so the hint can show them without a second lookup.
- Stored statuses change only on revalidation.
- Glossary: *Address corroboration* and *Master-data divergence* in this package's `CONTEXT.md`.
