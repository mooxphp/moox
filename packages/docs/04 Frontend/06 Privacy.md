# Moox Privacy

A **GDPR-compliant, frontend-compatible consent management solution** for Laravel + Filament.



## Compliance

| **Country / Region** | **Law / Abbreviation** | **Consent**     | **Opt-in or out** | **Notes**                            |
| -------------------- | ---------------------- | --------------- | ----------------- | ------------------------------------ |
| 🇪🇺 **EU**            | GDPR                   | ✅ Yes           | ✅ Opt-in          | Very strict                          |
| 🇬🇧 UK                | PECR                   | ✅ Yes           | ✅ Opt-in          | Aligns with GDPR                     |
| 🇺🇸 California        | CCPA / CPRA            | ⚠️ Partial       | ❌ Opt-out         | Banner for sharing/selling           |
| 🇨🇳 China             | PIPL (个人信息保护法)  | ✅ Yes           | ✅ Opt-in          | One of the strictest laws            |
| 🇯🇵 Japan             | APPI (個人情報保護法)  | ⚠️ Partial       | ✅ Opt-in          | Less strict, requires transparency   |
| 🇰🇷 South Korea       | PIPA (개인정보 보호법) | ✅ Yes           | ✅ Opt-in          | One of Asia’s most strict laws       |
| 🇸🇬 Singapore         | PDPA                   | ⚠️ Partial       | ⚠️ Depends         | Consent required for collection      |
| 🇮🇳 India             | DPDP Act 2023          | ⚠️ Partial       | ⚠️ TBD             | Still evolving, not yet enforced     |
| 🇦🇺 Australia         | Privacy Act 1988       | ⚠️ Minimal rules | ❌ Opt-out         | Reform in progress (2025)            |
| 🇨🇦 Canada            | PIPEDA                 | ⚠️ Partial       | ⚠️ Contextual      | Transparency is key                  |
| 🇧🇷 Brazil            | LGPD                   | ✅ Yes           | ✅ Opt-in          | Very similar to GDPR                 |
| 🇹🇭 Thailand          | PDPA                   | ✅ Yes           | ✅ Opt-in          | Very GDPR-like                       |
| 🇮🇩 Indonesia         | PDP Law                | ✅ Yes           | ✅ Opt-in          | Still early in enforcement           |
| 🇲🇾 Malaysia          | PDPA                   | ⚠️ Limited scope | ⚠️ Partial         | Cookie laws not enforced yet         |
| 🇹🇼 Taiwan            | PDPA                   | ⚠️ Light         | ⚠️ Contextual      | Cookie consent not strictly required |
| 🇵🇭 Philippines       | DPA (Data Privacy Act) | ⚠️ Moderate      | ⚠️ Contextual      | Encourages consent                   |
| 🇻🇳 Vietnam           | Decree 13              | ✅ Yes           | ✅ Opt-in          | Very new, still being understood     |



## Localization

| **Language** | **Country/Region**    | **Localized Name for GDPR / Consent Law**                  |
| ------------ | --------------------- | ---------------------------------------------------------- |
| 🇬🇧 English   | EU / UK               | **GDPR** (General Data Protection Regulation)              |
| 🇩🇪 German    | Germany / Austria     | **DSGVO** (Datenschutz-Grundverordnung)                    |
| 🇫🇷 French    | France / Belgium      | **RGPD** (Règlement Général sur la Protection des Données) |
| 🇪🇸 Spanish   | Spain / Latin America | **RGPD** (Reglamento General de Protección de Datos)       |
| 🇮🇹 Italian   | Italy                 | **RGPD**                                                   |
| 🇵🇱 Polish    | Poland                | **RODO** (Rozporządzenie o Ochronie Danych Osobowych)      |
| 🇳🇱 Dutch     | Netherlands / Belgium | **AVG** (Algemene Verordening Gegevensbescherming)         |
| 🇸🇪 Swedish   | Sweden                | **Dataskyddsförordningen**                                 |
| 🇫🇮 Finnish   | Finland               | **Tietosuoja-asetus**                                      |
| 🇨🇿 Czech     | Czech Republic        | **Nařízení GDPR**                                          |
| 🇭🇺 Hungarian | Hungary               | **Általános Adatvédelmi Rendelet** (GDPR)                  |
| 🇷🇴 Romanian  | Romania               | **Regulamentul general privind protecția datelor** (GDPR)  |



## Inspiration 

- https://github.com/whitecube/laravel-cookie-consent 
  - looks great, blade for simple Tailwind-Styling
  - but no flexible positioning / modes
  -  no dark mode
  - no rtl
  - no auto-blocking
- https://github.com/devrabiul/laravel-cookie-consent
  - full featured
  - Must be installed to preview, not seen yet
  - Unkown how to TW-compat, it is blade too
  - https://laravel-news.com/laravel-cookie-consent
  - https://dev.to/devrabiul/multilingual-cookie-consent-for-laravel-gdpr-ccpa-compliant-v102-m5p
- https://github.com/statikbe/laravel-cookie-consent
  - another thingy, looks OK
- https://github.com/scify/laravel-cookie-guard
  - another thingy, looks OK
- https://github.com/spatie/laravel-cookie-consent
  - Spatie, but toooo basic



## Goal

A **GDPR-compliant, frontend-compatible consent management solution** for Laravel + Filament.

- Own package moox/privacy inspired by mostly the first two packages from whitecube and devrabiul
- Filament resource for Trackers (Cookies, Local Storage, Session Storage, IndexedDB, Fingerprints, Analytic Scripts)
- Filament resource for Consents (Anonymized using Fingerprint by default, for statistical purposes)
- Filament resource for Categories (where Trackers can be organized)
- Moox FE-compatible (TailwindCSS, AlpineJS, DaisyUI, Alpina-Ajax, Motion) solution with 
  - Translations
  - RTL support
  - Responsiveness
  - Modal, Bar, Inline options
  - Dark mode
  - Fingerprint float like in TYPO3
- All simply configurable
- Cross-border handling (not needed for GDPR but for PIPL), configurable (or by Geo-IP using Pro)



## Pro

Moox Privacy Pro could use an API on moox.org to auto-discover Trackers used on the website (but there must be a scanner that looks up the database with contents like a content observer, then an installer observer or alternatively scheduled scans). There is data for that:

- https://github.com/duckduckgo/tracker-radar
- https://github.com/disconnectme/disconnect-tracking-protection
- https://easylist.to/
- https://www.ghostery.com/whotracksme/ and https://github.com/whotracksme/whotracks.me

And we could have those features

- GeoIP, also needs fresh data via an API, I guess
- Audit Logs
- Content generation for Privacy pages
- Legal services (Premium) by coop ...

Like

- https://termly.io/
- https://www.cookiebot.com/ and https://usercentrics.com/
- https://www.privacybee.io/
- https://www.ccm19.de/
- see https://www.e-recht24.de/tracking-cookies/12495-cookie-consent-tools.html



## Consent Frameworks

| **Platform**     | **Consent Framework** | **Similar to Consent Mode?** | **Required?**         | **Notes**                        |
| ---------------- | --------------------- | ---------------------------- | --------------------- | -------------------------------- |
| **Google**       | Consent Mode v2       | ✅                            | ✅ in EU               | For Ads, Analytics, GA4          |
| **Meta (FB)**    | Limited Consent       | ⚠️ Similar                    | ⚠️ Maybe               | Needs proof for legal use        |
| **IAB Europe**   | TCF v2.2              | ✅                            | ✅ for many Ad vendors | Standard in ad tech (by CMPs)    |
| **TikTok Ads**   | Consent Flag          | ⚠️ Informal                   | ⚠️ Yes                 | Often via GTM or direct SDK init |
| **YouTube**      | Consent required      | 🚫 Not automatic              | ✅                     | YouTube embeds blocked           |
| **LinkedIn Ads** | Consent param         | ⚠️ Informal                   | ⚠️ Maybe               | Required for retargeting         |
