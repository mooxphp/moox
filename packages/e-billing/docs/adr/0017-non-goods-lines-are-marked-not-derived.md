---
status: accepted
date: 2026-10-08
---

# Non-goods lines are marked, not derived

## Context

Some credit documents (381, 384) carry no goods positions: only refunded charges (packaging, shipping) or a lump-sum credit with a reason. EN 16931 requires at least one invoice line (BR-16) and rejects a negative net price (BR-27), so each amount becomes its own line (one unit, positive price; the sign of a delta travels in the quantity). Such a line has no article number, material or weight, while the host's line profiles make those fields `must`. Left alone, every such document is blocked until a reviewer releases the same findings by hand.

## Decision

- An invoice line can be marked as a **non-goods line**. The parser sets the mark; the reviewer can change it in the review workspace. The mark is stored on the line.
- The host lists the goods-identity line fields that do not apply to a non-goods line (`field_validation.non_goods_line_fields_not_applicable`, one list for all document types). On a marked line those fields are `not_applicable`; all other line fields keep their profile priority.
- Without the mark, a line is a goods line and is validated as before.

## Considered options

- **Derive non-goods from empty fields (no article number, no material).** Rejected: a goods line whose article number the parser missed would be treated as non-goods, turning off the very check that catches the miss.
- **Lower the goods fields to `should` for 381/384.** Rejected: hides parser misses on real goods lines of every credit document.
- **Severity release by the reviewer on each document.** Rejected: these documents occur frequently; repeating the same release is noise and trains reviewers to release blindly.
- **Per-document-type field lists.** Rejected for now: the goods-identity fields are the same for every type; a list per type can be added when one type needs it.

## Consequences

- The invoice line gains a persisted mark (migration in `moox/invoice`) and the review workspace a control for it; changing it is an audited value correction.
- Field validation consults the mark before applying a line profile.
- Glossary: *Non-goods line* in this package's `CONTEXT.md`.
