# Moox Shop

Moox Shop is a Bundle (meta package) that requires the following packages and provides some configuration maybe.

## Moox Product

Orderable or non-orderable (if variants) product, from draft ...

**products**

| **Field**          | **Type**        | **Description**                                              |
| ------------------ | --------------- | ------------------------------------------------------------ |
| id                 | UUID            | Primary key                                                  |
| is_orderable       | Boolean         | Whether this product can be ordered directly                 |
| default_price      | Decimal         | Optional fallback for pricing                                |
| image              | media relation  | Product image                                                |
| sku                | string          | Unique? required?                                            |
| status             | enum            | from draft                                                   |
| gallery            | media relations |                                                              |
| available          | enum            | instock, ordered, preorder, low, no                          |
| _at and _by fields |                 | from draft                                                   |
| weight             | Decimal         |                                                              |
| dimensions         | JSON            | length, width, height                                        |
| shipping_type      | enum            | letter, mail, e-mail, parcel, package, freight, pickup, service, download |

### **product_translations**

| **Field**          | **Type**      | **Description**  |
| ------------------ | ------------- | ---------------- |
| name               | String        | Product name     |
| slug               | String        | URL slug         |
| description        | Text/Markdown | Long description |
| data               | JSON          |                  |
| _at and _by fields |               | from draft       |

Configuration:

- relation to variants
- relation to taxonomies
- relation to reviews
- Use pricings
- Use stocks

## Moox Variant

Orderable variants of Products

### **variants**

| **Field**  | **Type** | **Description**                     |
| ---------- | -------- | ----------------------------------- |
| id         | UUID     |                                     |
| product_id | FK       | Belongs to a MooxProduct            |
| sku        | String   | Unique code for this variant        |
| price      | Decimal  | Price of this variant               |
| stock      | Integer  | Quantity available                  |
| is_default | Boolean  | (Optional) default selection for UI |
| images     | JSON     | Variant-specific images (optional)  |

### **variant_translations**

| **Field**     | **Type** | **Description**                       |
| ------------- | -------- | ------------------------------------- |
| option_values | JSON     | E.g., { "color": "Red", "size": "M" } |

## Moox Cart

Event driven cart system

Needs a clear API, used by Moox Product and others.

https://chatgpt.com/share/688bde9e-e8ac-800c-ab8d-6eeb7a5f852f



**Carts**

| **Feld**       | **Typ**     | **Beschreibung**                  |
| -------------- | ----------- | --------------------------------- |
| id             | UUID        |                                   |
| user_id        | nullable    | Relation, unique                  |
| session_id     | nullable    | Current Session ID, unique        |
| token          | nullable    | UUID/Cookie-ID for guests, unique |
| status         | Enum/String | active, abandoned                 |
| reserved_until | Timestamp   | if reservations enabled           |
| created_at     | Timestamp   |                                   |
| updated_at     | Timestamp   |                                   |

**Cart entries**

| **Feld**     | **Typ** | **Beschreibung**                       |
| ------------ | ------- | -------------------------------------- |
| id           | UUID    |                                        |
| cart_id      | FK      | Relation                               |
| entry_id     | FK      | Relation product, variant, package ... |
| entry_type   | String  | product, variant or package            |
| quantity     | Integer |                                        |
| price_at_add | Decimal |                                        |
| options_json | JSON    | additional data                        |

## Moox Wishlist

Event driven wishlist system

**Wishlists**

| **Feld**   | **Typ**   | **Beschreibung**   |
| ---------- | --------- | ------------------ |
| id         | UUID      |                    |
| name       | string    | "Default" or named |
| user_id    | nullable  | Relation           |
| created_at | Timestamp |                    |
| updated_at | Timestamp |                    |

**Wishlist entries**

| **Feld**     | **Typ** | **Beschreibung**                       |
| ------------ | ------- | -------------------------------------- |
| id           | UUID    |                                        |
| cart_id      | FK      | Relation                               |
| entry_id     | FK      | Relation product, variant, package ... |
| entry_type   | String  | product, variant or package            |
| quantity     | Integer |                                        |
| price_at_add | Decimal |                                        |
| options_json | JSON    | additional data                        |

## 

## Moox Customer

Shop customer 

| **Field**           | **Type**         | **Description**                                |
| ------------------- | ---------------- | ---------------------------------------------- |
| id                  | UUID             | Primary key                                    |
| type                | Enum             | private, business, guest                       |
| company_id          | UUID / null      | For B2B context – optional relation to company |
| first_name          | String           | Required if type = private                     |
| last_name           | String           |                                                |
| email               | String           | Unique – used for login and notifications      |
| password            | String / null    | Nullable for guest accounts or magic link auth |
| phone               | String / null    | Optional                                       |
| language            | String(5)        | e.g. en, de, fr                                |
| currency            | String(3)        | e.g. EUR, USD                                  |
| vat_id              | String / null    | EU reverse charge / tax ID                     |
| tax_exempt          | Boolean          | If VAT should be skipped                       |
| customer_number     | String / null    | Optional reference number                      |
| status              | Enum             | active, blocked, archived                      |
| email_verified_at   | Timestamp        | Standard for Laravel auth                      |
| last_login_at       | Timestamp / null | For audit / segmentation                       |
| registered_at       | Timestamp        | May be equal to created_at, but more explicit  |
| created_at          | Timestamp        |                                                |
| updated_at          | Timestamp        |                                                |
| default_shipping_id | UUID             | FK to addresses.id (nullable)                  |
| default_billing_id  | UUID             | FK to addresses.id (nullable)                  |

| **Relation** | **Beschreibung**        |
| ------------ | ----------------------- |
| company()    | Wenn B2B aktiv ist      |
| orders()     | Alle Bestellungen       |
| addresses()  | Beliebig viele Adressen |
| wishlists()  | Wishlist (1:n)          |
| reviews()    | Kundenbewertungen       |
| cart()       | Aktueller warenkorb     |

## Moox Company

Exends Moox Customer, for B2B shops

| **Field**          | **Type**         | **Description**                                  |
| ------------------ | ---------------- | ------------------------------------------------ |
| id                 | UUID             | Primary key                                      |
| name               | String           | Legal name of the company                        |
| slug               | String / null    | Optional URL slug or internal reference          |
| vat_id             | String / null    | EU VAT number (for reverse charge etc.)          |
| tax_exempt         | Boolean          | Should this company be billed without VAT?       |
| company_number     | String / null    | Handelsregisternummer / international equivalent |
| duns_number        | String / null    | Optional for US/International B2B                |
| type               | Enum             | gmbh, ug, ag, sole, llc, nonprofit, etc.         |
| industry           | String / null    | Optional branch / sector                         |
| website            | String / null    | Optional                                         |
| email              | String / null    | Optional (e.g. accounting@…)                     |
| phone              | String / null    | Optional                                         |
| language           | String(5)        | Default language (e.g. de, en, fr)               |
| currency           | String(3)        | Default currency (e.g. EUR, USD)                 |
| country_code       | ISO-2            | Registered in DE, AT, US etc.                    |
| status             | Enum             | active, blocked, pending, archived               |
| scoring_status     | Enum / String    | ok, manual, critical, blacklist, pending         |
| scoring_source     | String / null    | creditreform, experian, manual                   |
| scoring_checked_at | Timestamp / null | Letzter Check                                    |
| customer_number    | String / null    | Internes ERP-Feld (optional)                     |
| created_at         | Timestamp        |                                                  |
| updated_at         | Timestamp        |                                                  |

| **Relation**     | **Beschreibung**                                      |
| ---------------- | ----------------------------------------------------- |
| customers()      | Alle Ansprechpartner (User) dieses Unternehmens (1:n) |
| addresses()      | Unternehmensadressen, optional auch für Versand       |
| orders()         | Alle Bestellungen der Firma (z. B. via Angestellte)   |
| default_billing  | Optionale direkte Standard-Rechnungsadresse           |
| default_shipping | Optionale direkte Versandadresse                      |

Moox company müsste auch Customer erweitern, um company role (String oder enum?)



## Moox Address

Shipping and billing addresses. Can be attached to customer or company, used by a cart or order.

| **Feld**     | **Typ** | **Beschreibung**                 |
| ------------ | ------- | -------------------------------- |
| name         | String  | Copied, needs to be a snapshot   |
| customer_id  | FK      | Relation                         |
| company      | String  | Copied, needs to be a snapshot   |
| company_id   | FK      | Relation                         |
| street       | String  |                                  |
| house_number | String  | Optional getrennt                |
| city         | String  |                                  |
| postal_code  | String  |                                  |
| region/state | String  | Optional (z. B. für USA, CH, AT) |
| country_code | ISO-2   | DE, AT, US etc.                  |
| phone        | String  | Optional                         |
| email        | String  | Optional bei Gastbestellung      |
| type         | Enum    | billing, shipping, both          |

## Moox Reviews

Reviews and Rating for Moox Shop

## Moox Order

Orders from carts

### **orders**

| **Field**       | **Type**       | **Description**                                   |
| --------------- | -------------- | ------------------------------------------------- |
| id              | UUID           | Primary key                                       |
| number          | String         | Sequential order number (e.g. 2025-0000123)       |
| customer_id     | UUID           | Relation to MooxCustomer                          |
| cart_id         | UUID / null    | Optional – source cart                            |
| status          | Enum           | pending, paid, shipped, cancelled, returned, etc. |
| payment_status  | Enum           | unpaid, paid, failed, refunded, partial           |
| shipping_status | Enum           | open, processing, shipped, delivered, returned    |
| channel         | String / null  | e.g. web, pos, api                                |
| language        | String(5)      | e.g. de, en                                       |
| currency        | String(3)      | e.g. EUR, USD                                     |
| total_price     | Decimal        | Final total (including tax)                       |
| total_tax       | Decimal        | Included tax amount                               |
| discount_total  | Decimal        | Discount (coupons etc.)                           |
| shipping_total  | Decimal        | Shipping cost                                     |
| shipping_method | String / null  | Snapshot from MooxShipping                        |
| payment_method  | String / null  | Snapshot from MooxPayment                         |
| ordered_at      | Timestamp      | Zeitpunkt der Bestellung (nicht created_at)       |
| paid_at         | Timestamp/null | Wenn bezahlt                                      |
| shipped_at      | Timestamp/null | Wenn versendet                                    |
| cancelled_at    | Timestamp/null | Wenn storniert                                    |
| created_at      | Timestamp      | Laravel timestamp                                 |
| updated_at      | Timestamp      | Laravel timestamp                                 |

### **order_items**

| **Field**    | **Type** | **Description**                                 |
| ------------ | -------- | ----------------------------------------------- |
| id           | UUID     |                                                 |
| order_id     | UUID     | FK to orders.id                                 |
| type         | Enum     | product, variant, bundle, custom, voucher, etc. |
| reference_id | UUID     | product_id, variant_id, or null                 |
| name         | String   | Snapshot name                                   |
| sku          | String   | Snapshot SKU                                    |
| quantity     | Integer  |                                                 |
| unit_price   | Decimal  | Netto or Brutto (depending on config)           |
| total_price  | Decimal  | quantity × unit_price                           |
| tax_rate     | Decimal  | % rate snapshot                                 |
| tax_amount   | Decimal  | abs amount                                      |
| options_json | JSON     | Personalisierung, Bundles etc.                  |

### order_addresses (immutable)

| **Field**    | **Type** | **Description**   |
| ------------ | -------- | ----------------- |
| order_id     | UUID     | FK                |
| type         | Enum     | billing, shipping |
| name         | String   | Snapshot          |
| company      | String   | Optional          |
| street       | String   |                   |
| house_number | String   |                   |
| city         | String   |                   |
| postal_code  | String   |                   |
| region       | String   |                   |
| country_code | String   | ISO-2             |
| phone        | String   |                   |
| email        | String   |                   |

### order_status_logs

How to log changes ...

## Moox Invoice

PDF invoices for Moox Order

## Moox Mail

First part of Moox Mail specially for Moox Shop, not needed by us but Moox.

Ist ein eigenes System, die einzelnen Module hier liefern die Trigger und passenden Templates, wie z. B.:

| **Event**              | **Mail**                                |
| ---------------------- | --------------------------------------- |
| order.placed           | Bestellbestätigung an Kunde             |
| order.paid             | Zahlungsbestätigung                     |
| order.shipped          | Versandbestätigung mit Tracking         |
| order.cancelled        | Stornierungsinfo                        |
| order.refunded         | Rückerstattung                          |
| order.returned         | Rücksende-Anleitung                     |
| invoice.created        | Rechnung im Anhang                      |
| customer.registered    | Willkommensmail                         |
| customer.guest_ordered | Bestellinfo mit Link                    |
| scoring.failed         | Mahnung oder manuelle Prüfung notwendig |

## Moox Payment

We do not need that ... but Moox?

PDF, Zugferd, etc.

## Moox Shipping

That is special ... DPD?

Config:

deliver to countries OR embargo

## Moox Staff

People ...

## Moox Shop Category

Category, nested

## Moox Shop Tag

Tag, flat

## Moox Stock

Stock management

## Moox Pricing

Custom pricing, needs customer and/or tiered pricing, price comparison

## Moox SEO

moox/seo, for CMS, Blog and Shop

## Moox Bundle

moox/bundle  -  Bundles, Options and Personalization for Moox Products

## Moox Sell

moox/sell  -   Related, Cross-Sell, Upsell

## Moox Subscription

moox/subscription  -  Recurring Products 

## Moox Digital Products

Downloads, One-Time Links, downloaded_at and count, Licensing, can be used with Satis

## Moox Voucher

moox/voucher  -  Voucher and free add-on products or goodies from $100 and  so on ...

## Moox Availability

Multi channel shops, product availability, min. and max. Orders, limitations, flags, labels, badges, age restrictions, available from ... to 

## Moox Tax

Tax rates, international and US.

Checking tax numbers.

## Moox Scoring

Scoring by Creditreform, Schufa, CRIF Bürgel, Arvato infoscore, Pair Finance or International wie CRIF, CreditSafe:

```php
MooxScoring::check($customer)->via('experian_us');
MooxScoring::check($company)->via('creditsafe');

'scoring.providers' => [
    'default' => 'manual',
    'DE' => 'creditreform',
    'US' => 'experian',
    'UK' => 'companycheck',
]
```

## Moox Currency

Multi-currency shops

## Moox Compare

Product comparison

## Moox Return(s)

How to handle this part of eCommerce?

