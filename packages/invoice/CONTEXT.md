# Invoice — Context

Glossary for the generic invoice domain (`packages/invoice`, `moox/invoice`). This package is an
**EN 16931 model** and nothing else: a term belongs here when the standard has a BT/BG number for it,
and stays out when it is an integrator's house concept. No industry vocabulary, no integrator's
document wording, no host model names. Keep lean.

## Glossary

- **Invoice / Invoice line** — the persisted EN 16931 document (BG-25 for the line). Written through
  `InvoiceDraft` → `InvoiceBuilder`; a parser never writes the models directly. A line `extra`
  field that the configured model cannot accept fails with `RejectedDraftFieldException` naming
  the field and model — nothing is dropped silently at that seam.
- **Configured model** — hosts extend `Invoice` / `InvoiceLine` and register their subclass via
  `config('invoice.models.*')`, resolved through `InvoiceModels`. Every relation and every morph type
  must go through that resolution. A relation that hard-codes the base class resolves to **zero rows**
  for any host with a subclass, because the persisted morph type is the subclass. *Avoid:* referencing
  `Invoice::class` directly in a relation definition.
- **Allowance / Charge** — a deduction or an addition, header level (BG-20/BG-21) or line level
  (BG-27/BG-28), persisted polymorphically as `InvoiceAllowanceCharge` via `chargeable`. **Meaning
  comes from `reason_code`**, from the applicable code list; `reason_text` is human-readable
  accompaniment. *Avoid:* deriving a charge's kind by comparing its reason text against a literal —
  it binds the model to one integrator's wording and to one language.
- **Certificate** — proof of conformance accompanying goods. Its **existence** and its **price** are
  two separate facts: a certificate may be issued free of charge, and then no charge exists at all.
  Existence is an item attribute (BG-32), price is an ordinary line charge (BG-28) carrying reason
  code **`CAE`** (certificate of conformance), document level is a supporting document (BG-24).
  *Avoid:* inferring that a certificate exists from the presence of an amount.
- **Item attribute** — BG-32, a name/value pair on a line (BT-160 name, BT-161 value). BT-160 comes
  from a code list, never from the document, so "which lines carry X" is a query rather than a text
  search. BG-32 carries **no amount**; anything monetary is a separate charge.
- **Consignee** — BG-13 / BT-70, **where the goods went**: a party with a name, not a bare address,
  because it may be a different legal entity than the buyer. In the CII binding the standard models it
  as a party with a postal address beneath it, which is why the model holds a `Party` here. BG-13 is
  optional. BR-57 (country code BT-80 when BG-15 is present) is an **emission** rule, not a storage
  rule: the stored consignee may lack `country_code`, and the country may be completed later from
  master data on an unambiguous match. `Address::fromArray()` still requires a country (ready to emit);
  `Address::fromDocumentArray()` keeps the address as read. *Avoid:* the term
  "delivery address" — it is also used for the address an e-invoice is *dispatched to*, which is a
  different concept with a different master-data source.
- **Derived consignee** — the buyer address standing in when the document names no consignee. Derived
  at read time and marked as such; **never written to the column**, so "read from the document" and
  "assumed by us" stay distinguishable.
- **Invoice note** — BT-22, with an optional subject code BT-21 from a code list. Multiple notes per
  invoice, order preserved. A note is prose *about* the invoice; it is not a carrier for facts that
  have their own field. *Avoid:* recovering a structured fact by parsing a note.
- **Unit of measure** — BT-130. **The code is the canonical form**; the label is a document artefact
  resolved against the Rec 20 code list. Mandatory in EN 16931, so an unresolvable or empty unit
  cannot be tolerated at generation time — it is a blocking finding before it. Where several codes
  share a label in one language, the preferred code is configuration, not a constant keyed on a word
  in that language.
- **Buyer identifier** — BT-46. The debtor identity of the document. Distinct from **buyer reference**
  (BT-10); the two are separate terms and must not be conflated.
- **Payment terms** — BT-20, free text as printed on the document. A term, not a structured discount:
  no early-payment arithmetic is modelled here.
- **Shipping method** — how the goods travelled. No BT number in EN 16931; ZUGFeRD `EXTENDED`
  expresses it as `SpecifiedLogisticsTransportMovement/ModeCode`. Generic enough to live here — it
  describes transport, not any one trade.
- **Delivery terms** — how the price is quoted, i.e. who bears the freight: ex works, ex warehouse,
  free above a given order value. Free text, because the values are **parametric** — a threshold
  amount is part of the condition — and therefore not enumerable in a code list. Only a minority of
  real values map to an Incoterm at all. No BT number, and the structured carrier
  (`ApplicableTradeDeliveryTerms`) is forbidden in the EN16931 profile at both document and line level
  (CII-SR-293, CII-SR-106) and offers only a code, no text, in `EXTENDED`. It therefore travels as a
  **note** with the appropriate subject code. *Avoid:* the name "pricing basis" — it reads as a basis
  for the price and has caused real misreadings; and *avoid* confusing it with **payment terms**
  (BT-20), which is a separate field.

## Out of scope

Industry-specific line facts — material designations, weights, trade-specific surcharges — do **not**
belong in this package. They reach the model through the host's own extension of the configured model
and its own migration. If a term needs an industry to explain it, it is not an EN 16931 term.

The test is the *term*, not the presence of a BT number: shipping method and delivery terms have no
BT number and still belong here, because commerce at large needs them. A material designation needs a
steel trade to explain it, and does not.
