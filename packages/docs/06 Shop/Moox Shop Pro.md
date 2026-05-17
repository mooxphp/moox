# Shop Pro or Commerce ...

eCommerce offers a lot of potential for commercial packages. Some thoughts are payment, merchant of records, e-invoicing, subscriptions, licensing and last but not least a mix of all of these.

- Know and support E-invoicing standards worldwide.
- Know MoR vs. Self-MoR requirements.
- Know high-barrier markets (entity required), route them to MoR.
- Know and follow Embargo restrictions.
- Know refunding policies (also specialities by countries ) and handle them
- Follow data privacy rules
- Knows global taxes and handles tax registration worldwide

Moox Billing could solve this by being compliant to EU, US, and some other countries, while supporting MoR for the rest of the world. We can increase the self-MoR support when the package becomes more popular.

This solves our own problems to sell Moox ...

------



## 1.  E-Invoicing Landscape

- **Europe**

  - **ZUGFeRD/Factur-X** (DE/FR hybrid PDF/A-3 + CII XML).
  - **XRechnung (Germany)** mandatory for B2G.
  - **UBL/Peppol BIS 3.0** common for cross-border trade.
  - **EU OSS/MOSS** covers VAT reporting EU-wide.

- **North America**

  - **USA**: No national mandate. Sales tax registration at state level (economic nexus).
  - **Canada**: GST/HST; government accepts Peppol.

- **Latin America (strictest region)**

  - **Brazil (NFe), Mexico (CFDI), Chile (DTE), Argentina**: clearance systems → invoices must be pre-approved by tax portals.
  - Often requires a **local entity** or MoR with presence.

- **Asia-Pacific**

  - **India**: GST eInvoice / OIDAR → foreign providers can register, no entity needed.
  - **China**: Fapiao only via Golden Tax System → requires local entity.
  - **Japan**: Qualified Invoice System.
  - **Singapore, Australia, NZ**: Peppol-based networks.
  - **South Korea**: Hometax e-invoice system, local registration needed.

- **Middle East / Africa**

  - **Saudi Arabia (ZATCA), Egypt**: e-invoice clearance models → usually need local entity.
  - **South Africa**: lighter, tax registration only.

  

------



## 2. Merchant of Record (MoR) Insights

- **MoR = legal seller** → handles VAT/Sales Tax registrations, invoicing, compliance, chargebacks.
- **Popular MoRs**: Paddle, LemonSqueezy, FastSpring, 2Checkout (Verifone).
  - Paddle/LemonSqueezy: Easy, 5% + $0.50, but **no support for China/Brazil**.
  - FastSpring, 2Checkout: Higher fees (~6–9%), but **cover hard markets (China, Brazil, Mexico, LATAM)** via local entities/partners.
- **Stripe Tax / PayPal Tax**: *Not* MoRs → only calculate/report taxes; you remain liable.



------



## 3. Market Categories

- **Direct possible (self-MoR with registrations)**:

  - EU (via OSS), US/Canada, India (OIDAR), Australia/NZ, Singapore, UK, Norway, Switzerland.

- **Registration required, but no local entity**:

  - Mexico (B2C digital services), South Korea, Japan.

- **Entity or MoR required**:

  - Brazil, Argentina, Chile (clearance mandatory).
  - China (Fapiao system).
  - Saudi Arabia, Egypt (local clearance).

- **Embargoed (blocked regardless of MoR)**:

  - North Korea, Iran, Syria, Cuba, parts of Ukraine (Crimea, Donetsk, Luhansk), often Afghanistan.
  - Sanctions also apply to specific persons/orgs (OFAC/EU/UN lists).

  

------



## 4. Strategic Takeaways

- **Indie devs / small SaaS**:
  - Can sell *directly* in most of EU/US/Canada/India with OSS/Stripe Tax + Moox Invoice.
  - Must **block or route via MoR** for hard markets (China, Brazil, Argentina, Chile).
- **Hybrid Model = Future**:
  - Self-MoR where possible (saves 5–9% fees).
  - MoR fallback (FastSpring, 2Checkout) where local entities are mandatory.
- **Embargo compliance**: add a **country whitelist/blacklist** to checkout → cannot sell into sanctioned regions under any model.



------

## 

## 5.  Additional Pitfalls in Digital Sales

1. **Consumer Protection & Refund Rights**
   - **EU**: 14-day right of withdrawal for digital goods — *unless* customer explicitly waives it at checkout.
   - Many countries mandate clear refund/cancellation policies.
2. **Data Privacy / Residency**
   - **GDPR (EU)**, **LGPD (Brazil)**, **CCPA (California)**, **PIPL (China)** → govern how you store & process customer data.
   - Some require data to be hosted/replicated locally.
3. **Platform/PSP Restrictions**
   - Stripe, PayPal, Apple/Google enforce rules for digital content (e.g., SaaS vs. in-app purchases).
   - Certain product categories (gambling, adult, crypto, even some plugins) can be banned or high-risk.
4. **Currency & FX Handling**
   - Customers expect local currency billing.
   - In some countries (e.g., Argentina), foreign currency billing is restricted or heavily taxed.
5. **Export Controls & Licensing**
   - Software with encryption, AI, or security features can fall under export restrictions (especially US/EU origin).
   - Some APIs or SDKs may need compliance checks.



------



## 6.  Typical Registration Requirements

- **EU** → **OSS (One Stop Shop)**: single VAT registration covers all EU B2C sales.
- **UK, Norway, Switzerland** → foreign suppliers of digital services must **register for VAT**.
- **Canada, Australia, New Zealand** → GST/HST registration required for foreign digital suppliers.
- **India** → **OIDAR scheme** (Online Information Database Access & Retrieval). Foreign suppliers must register for GST.
- **Mexico** → IVA registration for digital services, even without entity.
- **South Korea, Japan** → similar registration rules for non-resident digital suppliers.
- **Brazil, Argentina, Chile, China, Saudi Arabia, Egypt** → registration isn’t possible without a local company!



------



## 7.  **VAT Number Validation APIs — Europe (2025)**

**EU-Wide**

- **VIES (VAT Information Exchange System)**
  - Covers all **27 EU member states + Northern Ireland**.
  - Public SOAP endpoint: https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl
  - Returns: valid/invalid, some countries also return name/address.
  - Stability: depends on national databases → sometimes down.
  - Best for unified EU coverage.

**National**

- **🇩🇪 Germany (BZSt — Bundeszentralamt für Steuern)**

  - “USt-IdNr. Bestätigungsverfahren”
  - Two levels:
    - **Simple check (Stufe 1)** = valid/invalid.
    - **Qualified check (Stufe 2)** = with company name & address.
  - SOAP service, official documentation in German.
  - More reliable than VIES for German VAT IDs.

- **🇬🇧 United Kingdom (HMRC)** *(not EU, but relevant)*

  - UK VAT number check API (REST + JSON).
  - Public service via [api.service.hmrc.gov.uk](https://developer.service.hmrc.gov.uk/api-documentation).
  - Covers GB VAT numbers; Northern Ireland still in VIES.

- **🇨🇭 Switzerland (UID Register)** *(not EU)*

  - VAT validation via Swiss UID register.
  - Public web search: [uid.admin.ch](https://www.uid.admin.ch).
  - API available (REST/JSON), but limited functionality.

- **🇳🇴 Norway**

  - **VAT-ID:** „MVA-nummer“ (Organisation + „MVA“ am Ende, z. B. 123456789MVA).
  - **Register:** Brønnøysundregistrene (Central Coordinating Register for Legal Entities).
  - **Access:**
    - **REST-API**: [Brreg API](https://data.brreg.no) → Search for Organisation, inkl. VAT-Status.
    - Result: JSON mit OrgNr + VAT-Status.

  

------

# **Missing capabilities**



### **A) Tax determination & reporting**

- **Rates & rules engine** (country → product type → rate, rounding, exemptions, reverse-charge).
- **OSS / ZM exports** (EU), **Sales-tax nexus tracking** (US states), **country-specific CSV/XML exports** (e.g., SAF-T/“JPK” style exports where relevant).
- **Withholding taxes** (e.g., India TDS/TCS), **digital service classifications** (HSN/SAC/NAICS/NCM codes).
- **Currency handling** (FX on invoice date; proper rounding per currency).



### **B) Invoice lifecycle completeness**

- **Pro forma / quotes**, **tax invoices**, **credit notes**, **partial refunds**, **write-offs**.
- **Numbering schemes per jurisdiction** (unique sequences, per-entity/per-year; hash/QR where required—Portugal ATCUD, Spain Verifactu, Italy SDI).
- **Rounding & totals** that pass strict validators (line vs. doc level).



### **C) Formats & validators**

- **PDF/A-3 conformance** check after embedding XML.
- **EN 16931 / XRechnung / UBL** validators in CI.
- **National modules** (plug-ins) for LATAM/ME (CFDI, NFe, DTE, ZATCA)—even if via partner APIs.
- **Localized templates** (i18n, RTL, localized number/date formats).
- **Digital signatures** (XMLDSig for some schemas; optional PDF signing).



### **D) Delivery & archiving**

Questionable as certifications like ISO 14641, GoBD-Testat in Germany, NF525 in France, IDW PS 880 needed.

- **Legally compliant e-archiving** (retention periods, WORM/immutability).
- **Immutable audit trail** (who/what/when; signature of metadata).

Alternatively offer APIs like:

- CMIS (Content Management Interoperability Services), OASIS Standard since 2010, SOAP & REST-Bindings
- WebDAV - most DMS offer
- S3-API - de-facto standard for object storage (with object lock)
- DMS-specific APIs like d.velop documents, DocuWare, ELO ECM REST, OpenKM REST



### **E) Compliance routers**

- **Country policy engine** → self-MoR vs MoR vs block (embargo).
- **Sanctions screening** (OFAC/EU/UK lists) + geo/IP controls.
- **Refund/withdrawal logic by country** (EU 14-day waiver flow at checkout).



### **F) Ops & resilience**

- **VIES health + fallback** (national DE BZSt, UK HMRC, CH UID, REST caches).
- **Idempotent webhooks** (PSPs & MoR), **event-sourcing** for invoice states.
- **Monitoring** (validators, PDF/A-3, queue health).



### **G) Geolocation and Geo Arbitrage**

- IP + billing country + BIN country **triangulation**; flag mismatches.
- **Country policy JSON** (embargo, MoR-required, registration-only, allowed).
- FX routing: choose PSP/MoR by buyer’s currency & country fees.
- Geo-based **invoice template** (legal footer language) & **numbering series per entity/country**.



### **H) Subscriptions & Licensing **

- **Proration, trials, metered usage**; align invoice lines with tax rules per country.
- **License server hooks** (issue/suspend keys on payment state).
- **Dunning & smart retries**; send **correct credit notes** on partial refunds.
- **One-off + recurring tax correctness** (e.g., EU place-of-supply on renewals).



### **I) Disputes & Chargebacks**

- PSP/MoR webhook normalization → dispute records; auto-attach invoice/ToS logs.
- Country-aware evidence packs (consumer law snippets, waiver proof).



### **J) Security & Privacy Ops**

- **DPIA templates**, **RoPA** entries; per-feature data maps.
- **Key management** (KMS/HSM), field-level encryption for PII/tax IDs.
- **Data deletion & retention jobs** by country policy.



------



# **Helpful PHP/Laravel dependencies**



### **Core money, math, dates**

- brick/money + brick/math – high-precision money & rounding.
- nesbot/carbon – robust date/time.



### **Currency & FX**

- florianv/swap (with ECB provider) – historical FX rates for correct “invoice-date” conversions.



### **Addressing & ISO metadata**

- commerceguys/addressing – formats/validations per country.
- league/iso3166 – country metadata.
- ramsey/uuid – compliant IDs for sequencing.



### **Tax/VAT validation & fallbacks**

- dragonbe/vies – EU VIES.
- UK: use HMRC REST (roll a tiny client) for GB VAT.
- CH: call Swiss UID REST (small client).
- Optional REST cache fallback: a vatlayer wrapper (community).



### **PDF & QR**

- barryvdh/laravel-dompdf **or** mpdf/mpdf – HTML→PDF.
- endroid/qr-code – QR/ATCUD/Verifactu payloads on invoices.
- (Validation) integrate **veraPDF** CLI for **PDF/A-3** checks (shell).



### **XML & signatures**

- robrichards/xmlseclibs – XMLDSig (used by some e-invoice schemas).
- horstoeko/zugferd + horstoeko/zugferd-laravel – ZUGFeRD/Factur-X/XRechnung.
- (UBL) generate via your own serializers; validate via external validator CLI or a Java microservice, like https://github.com/num-num/ubl-invoice
- https://github.com/docusealco/docuseal



### **Storage, audit, security**

- league/flysystem (+ S3 adapter) – durable storage.
- spatie/laravel-activitylog – audit trail (extend to WORM).
- paragonie/halite or Laravel’s encryptor – encrypt PII-at-rest.
- spatie/laravel-permission – multi-role access to invoices.



### **Sanctions & embargo hooks**

- Write provider adapters (pick one: ComplyAdvantage, Sanctions.io, OpenSanctions CSV). Keep interface generic so you can swap providers.



### **MoR / Payments**

- lemonsqueezy/laravel, paddlehq/paddle-php-sdk
- 2Checkout/Verifone (current SDK) or thin REST client, like https://github.com/coralsio/payment-twocheckout
- FastSpring: thin client over REST + webhook handlers.
- Stripe/Adyen for self-MoR card payments where allowed.



------



Last but not least: for physical goods:

- **Zoll/Import** (HS-Code, Zölle, Importsteuer, Zollabwicklung).
- **Incoterms** (DAP, DDP, EXW etc.).
- **Logistik & Versandnachweise** (Tracking, Exportpapiere).
- **Warenwirtschaft/Lagerhaltung** (Stock-keeping, Seriennummern).
- **Produktkonformität** (CE-Kennzeichen, RoHS, REACH, lokale Vorschriften).
- **Retouren/Reparaturen** (Kundenrechte bei Mängeln).

but mostly handled by ERP Backend .....