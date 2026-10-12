---
status: accepted
date: 2026-09-25
---

# A credit note with a negative total blocks approval

A credit note (BT-3 = `381`) already says "money back to the buyer", so its quantities and amounts must be positive. A source system that prints credit notes with minus signs produces a 381 with a negative grand total (BT-112). The double negative reads as a debit in the buyer's accounting. KOSIT schema and Schematron validation pass it (observed: a 381 with `GrandTotalAmount -173.14` reported *valid*), so nothing downstream catches it. We therefore check it in `moox/e-billing` itself: a 381 whose BT-112 is negative is a **blocking** finding, and the document cannot be approved or dispatched. The check is generic and knows nothing about any source system. Making the signs positive is the parser's job, because the quirk belongs to the source.

## Considered options

- **Warning only.** Rejected: the XML is valid but books money in the wrong direction, and a warning gets clicked away.
- **Flipping signs automatically in the package.** Rejected: that silently rewrites parsed amounts, and it would hide a parser defect instead of surfacing it.
- **Emitting such documents as 380 with negative amounts.** Rejected: it changes the document kind the integrator declared.
