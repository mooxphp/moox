# Billing platform

## Alternativen

- https://qits.de/e-rechnung/
- https://www.b4value.net/
- https://www.businessautomatica.com/

siehe auch

- https://it.heco.in/wp/wiki/projekt-erechnungen/

## Packages

Moox Media

Moox Mail Inbox - scoped

- eingehende Mails
- Anhänge
- Metadaten des Eingangs

Tabelle: inbox_messages

Felder - id - scope - channel (email, upload, api) - external_id nullable - message_id nullable - from_email nullable - from_name nullable - to_email nullable - to_name nullable - subject nullable - received_at nullable - raw_headers json nullable - raw_body_text longText nullable - raw_body_html longText nullable - has_attachments boolean - processing_status (new, read, done, skipped, failure) - created_at - updated_at

Tabelle: inbox_attachments

Felder
• id
• scope
• inbox_message_id nullable
• storage_disk
• storage_path
• filename
• mime_type
• extension nullable
• filesize nullable
• checksum nullable
• is_pdf booleanx
• attachment_role nullable (invoice_pdf, xml, other)
• created_at
• updated_at

Moox PDF Parser

- Parser-Läufe
- Rohtext
- erkannte Felder
- Confidence / OCR-Ergebnisse - https://github.com/spatie/pdf-to-text - https://github.com/PrinsFrank/pdfparser - https://github.com/smalot/pdfparser - https://github.com/tecnickcom/tc-lib-pdf-parser

Moox eBilling Gateway

- Mapping-Regeln
- Intake-Entscheidungen
- Prüfresultate vor Bill
- Sachbearbeiter-Eskalation

Moox Bill

- kanonische Rechnung
- Rechnungsstatus
- Positionen
- Steuerlogik
- Beleg-Snapshots

Moox Numbers

- number ranges
- prefixes / suffixes
- yearly reset
- company scope
- document type scope
- reservation / locking
- preview vs final assignment

Moox Tax

- tax rates
- tax rules
- valid_from
- valid_until
- country / region
- business case flags wie reverse charge, exempt, intra-community
- snapshot-fähige Auflösung für Bill

Moox Audit - scoped

- Spatie Laravel Activitylog

Moox ZUGFeRD

- erzeugte E-Rechnungsartefakte
- https://github.com/horstoeko/zugferd-laravel
- https://github.com/horstoeko/zugferd
- https://github.com/horstoeko/zugferdvisualizer

Moox KoSIT

- Validierungsresultate
- https://github.com/itplr-kosit/validator

Moox Mailer - basiert auf Flow?

- Versandjobs
- Versandhistorie
- Zustellstatus

Moox Outbox - scoped

- ausgehende Mails
- Anhänge
- Metadaten des Versands

Moox Portal

- externe Sicht auf Belege

Moox Feedback

- Rückmeldungen / Korrekturwünsche

Moox Flow

- Zustandsmaschine
- Orchestrierung
- Transitionen

Moox Customer - bundle

- Moox Company
- Moox Contact
- Moox Address

Moox Settings

- Settings eben, tbd

Moox Scopes

- scope (string) mit z. B. ebilling:media:heco:private in allen entities
- scopes table in core
- Packages registrieren ihrer Scopes und liefern Plugins zu gescopten Entities,
  z. B. verwendet eBilling Media gescoped, Career verwendet es in mehreren Scopes: Global Public, Scoped Private, Scoped Public
- Moox Scope liefert die UI dazu, aber Scopes sind eigentlich ein Core feature.
  Der Core liefert standardmäßig core:global:default:public, core:global.default.private, core:global:default.user.
- Moox Permission (Shield) liefert permission:global:default:group, role
- Moox eBilling ebilling:global:default:restricted
- Moox Career career:media:default:public (Stellenanzeigen, CI Elemente), career:media:default:restricted (Uploads, only shown in context)

Future - https://peppol.org/

## Projektplan

Phase 1 — Proof of Concept / technisches MVP

Ziel:
Aus einem vorgegebenen PDF eine Bill und daraus eine e-Rechnung erzeugen.

Umfang:

- Moox PDF Parser liest Text, Felder, Confidence
- Moox eBilling Gateway mapped auf ein kanonisches Rechnungsmodell
- Moox Bill speichert die Rechnung
- Moox ZUGFeRD erzeugt XRechnung oder ZUGFeRD

Packages:

- Moox PDF Parser
- Moox Billing Gateway
- Moox Bill
- Moox Billing ZUGFeRD

Test mit vorhandenen Validatoren.

Phase 2 — Review-MVP

Ziel:
Unvollständige oder unsichere Rechnungen bearbeitbar machen.

Umfang:

- Parser-Resultat sichtbar machen
- fehlende Pflichtfelder markieren
- Sachbearbeiter / Flow (später auch mit AI) kann korrigieren
- Bill danach erneut erzeugen oder aktualisieren
- Statusmodell für:
- uploaded
- parsed
- needs_review
- ready_for_export
- exported

Phase 3 — Inbox-MVP

Ziel:
Eingehende Mails mit PDF automatisch in denselben Prozess geben.

    - Mailbox ingestion wahrscheinlich nicht möglich
    - wahrscheinlich SMTP-Inbound notwendig

Phase 4 — Vorprüfung

    - eigene fachliche Vorprüfung bauen
    - später Moox KoSIT als Bridge integrieren

Phase 5 — Versand-MVP

Ziel:
Erzeugte Rechnung direkt per Mail verschicken.

Umfang:

- Moox Mailer versendet
- Moox Outbox speichert Versandobjekt
- Versandstatus wird protokolliert
- Rechnung zunächst als PDF bzw. ZUGFeRD-PDF an die Mail
- einfache Retry-Logik

Hier gilt:

- Moox Flow entscheidet, wann versendet wird
- Moox Mailer verschickt
- Moox Outbox protokolliert

Phase 6 — Master-Data-Anreicherung

Ziel:
Beim Rechnungsaufbau Companies, Contacts, Addresses kontrolliert befüllen.

    - Parser/Gateway schlägt Company/Contact/Address vor
    - Bill speichert Snapshot
    - Stammdaten werden nur nach Review angelegt oder gemerged

Phase 7 — Portal und Feedback

Ziel:
Externe Bereitstellung und Rückmeldung.

Umfang:

- Beleg im Portal sichtbar
- Download
- Statusanzeige
- Feedback / Beanstandung
- Rückmeldung erzeugt Flow-Event
- Sachbearbeiter kann reagieren
