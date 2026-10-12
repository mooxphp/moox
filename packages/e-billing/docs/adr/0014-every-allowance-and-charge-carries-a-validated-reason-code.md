---
status: accepted
date: 2026-09-30
---

# Every allowance and charge carries a validated reason code

EN 16931 accepts an allowance or charge with a reason text alone (BR-33, BR-38, BR-42, BR-44 with BR-CO-21 to BR-CO-24); a code, when present, must come from UNCL 5189 for allowances (BR-CL-19) and UNCL 7161 for charges (BR-CL-20). We are stricter: every allowance and charge in an Artifact carries a reason code from the list for its direction, because a receiver's system books on the code, not on free text. The code a payload charge receives is integrator configuration, checked against the moox/data codelists (`static_charge_reasons`, `static_allowance_reasons`) the way payment means and VAT category are; a configured code that is not in the imported list is a configuration error and fails generation. A stored allowance or charge whose code is missing or from the wrong list is a fixed **blocking** finding, outside the configurable MoSCoW priorities, so the case cannot be approved until a reviewer supplies the code as a value correction.

Package defaults, chosen for fit (no authority mandates a specific code): shipping `SAA`, freight flat rate `FC`, packaging `PC`, minimum-quantity surcharge `DAN`, alloy surcharge `PRV` (UNCL 7161 has no alloy or metal surcharge code; `PRV` is "price variation related to energy and or raw materials cost variation"), material test certificate `CAE`, discount `95`. Codes were compared against the EN 16931 code lists v17.0.

## Considered options

- **Best effort (code when known, text otherwise).** Rejected: an unmapped charge would go out uncoded without anyone noticing.
- **`ZZZ` (mutually defined) as fallback.** Rejected: it hides the missing mapping behind a valid-looking code that needs a bilateral agreement to mean anything.
- **A configurable priority for the finding.** Rejected: the rule exists so that no installation ships uncoded charges; a switch invites turning it off.
- **Filling missing codes at emission, or a data migration for existing drafts.** Rejected: the Invoice is mapped once (ADR 0013) and emission must reflect its rows, so existing drafts get their codes by value correction. The former emission-time `CAE` fallback for certificate charges is removed for the same reason.

## Consequences

- Drafts mapped before this rule become blocked on their next validation until a reviewer adds the codes. Those corrections group under one parser-feedback signature (`allowance_charges.reason_code`, deviation *empty*) that stops growing once new cases carry codes.
- Recognising a stored header charge (shipping, packaging, ...) matches by reason code first and falls back to the reason text, so uncoded drafts stay recognisable.
