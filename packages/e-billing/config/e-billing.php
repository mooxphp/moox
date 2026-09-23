<?php

use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\EbillingDeliveryAttempt;
use Moox\EBilling\Resources\CreditNoteResource;
use Moox\EBilling\Resources\InvoiceResource;
use Moox\EBilling\Support\EbillingActivityAttributeLabels;
use Moox\EBilling\Support\InvoiceActivitySubjectLabel;
use Moox\Invoice\Models\Invoice;
use Moox\KositValidator\Models\KositValidatable;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Resources\KositValidationResource;
use Moox\VeraPdf\Models\VeraPdfValidatable;
use Moox\VeraPdf\Models\VeraPdfValidation;
use Moox\VeraPdf\Resources\VeraPdfValidationResource;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core. Example:
|
| 'trans//core::core.all',
| loads from common.php
| outputs 'All'
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice parser
    |--------------------------------------------------------------------------
    |
    | The PDF → Invoice DTO parser. The package ships none because the layout is
    | host-specific; set this to a class implementing
    | Moox\EBilling\Contracts\InvoiceParserInterface. When null, the parser is left
    | unbound and resolving the EBilling service fails fast with a clear error.
    |
    */

    'parser' => null,

    /*
    |--------------------------------------------------------------------------
    | Default format + hybrid profile
    |--------------------------------------------------------------------------
    |
    | Single default preference when the recipient port returns null.
    | - format: FormatRegistry key (xrechnung|zugferd|factur-x)
    | - profile: library profile baked into zugferd/factur-x definitions
    |   (must be listed in allowed_profiles). XRechnung always uses XRECHNUNG
    |   and ignores profile here.
    | The document freezes format + profile at generation (when xml_storage_path is set).
    |
    */

    'default' => [
        'format' => 'zugferd',
        'profile' => 'EN16931',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed hybrid profiles
    |--------------------------------------------------------------------------
    |
    | EN 16931 conformance levels a recipient preference may request for
    | zugferd / factur-x. XRechnung never takes a preference profile (registry
    | bakes in XRECHNUNG). EXTENDED / MINIMUM / BASIC are refused until a later
    | ticket expands this list. Effective profile = preference.profile
    | ?? FormatDefinition.profile (from default.profile for hybrids).
    |
    */

    'allowed_profiles' => ['EN16931'],

    /*
    |--------------------------------------------------------------------------
    | Identical-content duplicate notifications
    |--------------------------------------------------------------------------
    |
    | Mail ingest has no logged-in user. Database notifications go to
    | notify_emails when set. Empty list = every user who can access the
    | panel. panel_id null uses Filament's default panel.
    |
    */

    'identical_duplicate' => [
        'panel_id' => env('EBILLING_IDENTICAL_DUPLICATE_PANEL'),
        'notify_emails' => [
            // 'ops@example.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Duplicate document-number comparison scope
    |--------------------------------------------------------------------------
    |
    | Controls when two documents with the same invoice_number + document_type
    | count as a collision (review / identical-content discard).
    |
    | global (default) — collide across the whole installation. Use when you
    | have a single issuing party (one seller VAT) or number ranges never
    | overlap between sellers.
    |
    | issuer — also require the same seller VAT id (BT-31). Switch to this when
    | several suppliers can reuse the same number for different issuers; then
    | seller A's "2024-001" does not collide with seller B's "2024-001". Blank /
    | missing VAT ids only collide with other blank / missing VAT ids.
    |
    */

    'duplicate_number' => [
        'scope' => env('EBILLING_DUPLICATE_NUMBER_SCOPE', 'global'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual uploads
    |--------------------------------------------------------------------------
    |
    | Internal admin uploads for documents that never enter the mailbox flow.
    | Enabled per resource via resources.*.manual_upload.enabled.
    |
    */

    'manual_upload' => [
        'source_disk' => env('EBILLING_MANUAL_SOURCE_DISK', 'local'),
        'source_path' => env('EBILLING_MANUAL_SOURCE_PATH', 'ebilling/manual-uploads/source'),
        'max_size_kb' => (int) env('EBILLING_MANUAL_MAX_SIZE_KB', 20480),
    ],

    'letterhead' => [
        'default' => [
            'label' => 'Default letterhead',
            'pdf_path' => env('EBILLING_MANUAL_DEFAULT_LETTERHEAD_PDF', ''),
            'offset_x_mm' => (float) env('EBILLING_LETTERHEAD_OFFSET_X_MM', 0),
            'offset_y_mm' => (float) env('EBILLING_LETTERHEAD_OFFSET_Y_MM', 0),
        ],
        'by_seller' => [
            // '655371' => [
            //     'label' => 'HECO seller-specific letterhead',
            //     'pdf_path' => env('EBILLING_MANUAL_LETTERHEAD_655371_PDF', ''),
            //     'offset_x_mm' => (float) env('EBILLING_LETTERHEAD_655371_OFFSET_X_MM', 0),
            //     'offset_y_mm' => (float) env('EBILLING_LETTERHEAD_655371_OFFSET_Y_MM', 0),
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | qpdf binary (letterhead overlay preprocessing)
    |--------------------------------------------------------------------------
    |
    | Used to rewrite encrypted or FPDI-unreadable PDFs before underlay merge.
    | Leave empty to rely on qpdf on PATH (Homebrew: /opt/homebrew/bin/qpdf).
    |
    */

    'qpdf' => [
        'binary_path' => env('EBILLING_QPDF_PATH', env('QPDF_PATH', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Send visual copy with XRechnung mail
    |--------------------------------------------------------------------------
    |
    | When true (default), the human-readable copy PDF is attached alongside the
    | XRechnung XML. The copy is always produced and downloadable in the portal;
    | this flag only gates the mail attachment. Overridable per customer column
    | `send_visual_copy`, then this config default.
    |
    */

    'send_visual_copy' => true,

    /*
    |--------------------------------------------------------------------------
    | XRechnung copy PDF marking (§14c)
    |--------------------------------------------------------------------------
    |
    | Always produced for XRechnung. send_visual_copy only gates outbound mail.
    | `term` is stamped as a single soft diagonal watermark when the copy PDF is generated.
    | `notice` is kept for config compatibility and is not rendered on the PDF.
    |
    */

    'copy_pdf' => [
        'term' => env('EBILLING_COPY_PDF_TERM', 'Kopie'),
        'notice' => env(
            'EBILLING_COPY_PDF_NOTICE',
            'Die elektronische XRechnung (XML) ist das maßgebliche Original. Dieses PDF ist nur eine bildliche Kopie und keine weitere Rechnung.',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed BT-3 document type codes (UNTDID 1001)
    |--------------------------------------------------------------------------
    |
    | DocumentTypeCodeResolver only accepts these codes. Anything else throws
    | UnresolvedCodelistLabelException (routes to needs-review). Defaults cover
    | commercial invoice (380) and credit note (381).
    |
    */

    'allowed_document_type_codes' => ['380', '381'],


    /*
    |--------------------------------------------------------------------------
    | Default payment means + VAT category (UNTDID)
    |--------------------------------------------------------------------------
    |
    | Stamped onto every mapped invoice when the DTO does not override.
    | ConfiguredEn16931CodeResolver fail-fast validates against moox/data
    | static_payment_means (4461) and static_vat_categories (5305).
    |
    */

    'payment_means_code' => env('EBILLING_PAYMENT_MEANS_CODE', '58'),
    'vat_category_code' => env('EBILLING_VAT_CATEGORY_CODE', 'S'),


    /*
    |--------------------------------------------------------------------------
    | Preferred piece unit code (UN/ECE Rec 20)
    |--------------------------------------------------------------------------
    |
    | When a label resolves to any code listed in piece_unit_codes, the resolver
    | and artifact adapter emit this code instead (default H87 for piece counts).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Document emission locale
    |--------------------------------------------------------------------------
    |
    | Language for EN 16931 free-text emission labels (BG-32 BT-160 names and
    | CAE reason_text fallbacks). Independent of Filament / App UI locale.
    | Applies to newly built artifacts only (forward-only).
    |
    */

    'document_locale' => env('EBILLING_DOCUMENT_LOCALE', 'en'),

    'preferred_piece_unit_code' => env('EBILLING_PREFERRED_PIECE_UNIT_CODE', 'H87'),

    'piece_unit_codes' => ['C62', 'H87'],

    /*
    |--------------------------------------------------------------------------
    | Filament: Invoices (InvoiceResource)
    |--------------------------------------------------------------------------
    */

    'resources' => [
        'invoices' => [
            'enabled' => true,
            'label' => 'trans//e-billing::ebilling.invoice',
            'plural_label' => 'trans//e-billing::ebilling.invoices',
            'navigation_group' => 'trans//e-billing::ebilling.navigation_group',
            'navigation_icon' => 'heroicon-o-document-text',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'document_types' => ['380'],
            /*
            |--------------------------------------------------------------------------
            | Soft Delete Tab Key
            |--------------------------------------------------------------------------
            |
            | This key must match a tab key under 'tabs.invoices'. It tells our custom
            | applySoftDeleteQuery() which tab to treat as the trash view.
            |
            | IMPORTANT: Moox Core's SingleSoftDeleteInResource::getHardDeleteBulkAction()
            | still hardcodes 'deleted' and 'trash' for visibility. If you rename this
            | key to something else, the hard-delete bulk action from the vendor trait
            | may not appear on the correct tab. This is a known limitation.
            |
            | @see https://github.com/mooxphp/core — open an issue if this needs
            |      to be configurable in the trait.
            |
            */
            'soft_delete_tab_key' => 'deleted',
            'resource' => InvoiceResource::class,
            'manual_upload' => [
                'enabled' => false,
                'label' => 'Manual upload',
                'scope' => 'invoices',
                'requires_letterhead_overlay' => false,
            ],
        ],
        'credit_notes' => [
            'enabled' => true,
            'label' => 'trans//e-billing::ebilling.credit_note',
            'plural_label' => 'trans//e-billing::ebilling.credit_notes',
            'navigation_group' => 'trans//e-billing::ebilling.navigation_group',
            'navigation_icon' => 'heroicon-o-receipt-refund',
            'navigation_sort' => 2,
            'navigation_count_badge' => true,
            'document_types' => ['381'],
            'soft_delete_tab_key' => 'deleted',
            'resource' => CreditNoteResource::class,
            'manual_upload' => [
                'enabled' => true,
                'label' => 'Upload credit note',
                'scope' => 'credit-notes',
                'requires_letterhead_overlay' => true,
            ],
        ],
    ],

    'tabs' => [
        'invoices' => [
            'all' => [
                'label' => 'trans//e-billing::fields.tab_all',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'gateway_failed' => [
                'label' => 'trans//e-billing::fields.tab_gateway_failed',
                'icon' => 'gmdi-error',
                'query' => [
                    [
                        'field' => 'gateway_status',
                        'operator' => 'in',
                        'value' => ['generation_failed', 'validation_failed', 'validator_error'],
                    ],
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'processing' => [
                'label' => 'trans//e-billing::fields.tab_processing',
                'icon' => 'gmdi-hourglass-empty',
                'query' => [
                    [
                        'field' => 'gateway_status',
                        'operator' => 'in',
                        'value' => ['generating', 'validating'],
                    ],
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'needs_review' => [
                'label' => 'trans//e-billing::fields.tab_needs_review',
                'icon' => 'gmdi-warning',
                'query' => [
                    [
                        'field' => 'review_status',
                        'operator' => 'in',
                        'value' => ['parser_created', 'db_validated'],
                    ],
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'confirmed' => [
                'label' => 'trans//e-billing::fields.tab_confirmed',
                'icon' => 'gmdi-check-circle',
                'query' => [
                    [
                        'field' => 'review_status',
                        'operator' => 'in',
                        'value' => ['human_confirmed', 'validated'],
                    ],
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'deleted' => [
                'label' => 'trans//e-billing::fields.tab_deleted',
                'icon' => 'gmdi-delete',
                'query' => [
                    [
                        'field' => 'deleted_at',
                        'operator' => '!=',
                        'value' => null,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ZUGFeRD storage (filesystem disk `zugferd`)
    |--------------------------------------------------------------------------
    |
    | Relative paths on this disk follow `{scope}/{Y-m}/{invoiceNumber}_{date}.xml|.pdf`.
    | When `storage_root` is null, it defaults to `storage/app/private/{mail-inbox.zugferd.path}`.
    |
    */

    'zugferd' => [
        'storage_disk' => 'zugferd',
        'storage_root' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default customer country (TRANSITIONAL)
    |--------------------------------------------------------------------------
    |
    | Fallback buyer/delivery country when the parser derives none (domestic
    | addresses without a country line). Removed by the master-data phase
    | (Company/Address lookup). Foreign invoices are still detected via
    | BILLING_COUNTRY_MAP and filtered before XML.
    |
    */

    'default_customer_country' => 'DE',

    /*
    |--------------------------------------------------------------------------
    | Supplier (master data)
    |--------------------------------------------------------------------------
    |
    | Central supplier data for invoices.
    | Copied onto the invoice as a snapshot when the invoice is created.
    |
    */

    // Example supplier data — override in your application's config/e-billing.php
    // or set via environment variables.
    'supplier' => [
        'name' => 'Acme Stainless GmbH',
        'vat_id' => 'DE123456789',
        'tax_number' => '12345/67890',
        'address' => [
            'company' => 'Acme Stainless GmbH',
            'street' => 'Musterstraße 1',
            'zip' => '12345',
            'city' => 'Musterstadt',
            'country' => 'DE',
        ],
        'country_code' => 'DE',
        'phone' => '+49 (0)1234 567890',
        'email' => 'billing@example.com',

        /*
        |--------------------------------------------------------------------------
        | Bank accounts
        |--------------------------------------------------------------------------
        |
        | Multiple accounts allowed (e.g. different banks / currencies).
        |
        */

        'bank_accounts' => [

            [
                'bank_name' => 'Example Bank eG',
                'iban' => 'DE00100000000000001234',
                'bic' => 'EXMPDEDB',
            ],

            [
                'bank_name' => 'Muster Sparkasse',
                'iban' => 'DE00200000000000005678',
                'bic' => 'MSPKDEDB',
            ],

        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribution corroboration
    |--------------------------------------------------------------------------
    |
    | After a customer is attributed via buyer identifier, name / VAT / country /
    | address are checked against master data. Divergences only flag needs_review;
    | they never clear or rewrite the customer attribution.
    |
    */

    'corroboration' => [

        /*
        | Minimum length for a name token to count as significant (after
        | lowercase + diacritic fold + punctuation strip).
        */
        'name_min_token_length' => 4,

        /*
        | Legal-form words ignored during name token corroboration.
        */
        'name_legal_form_stop_words' => [
            'gmbh',
            'ag',
            'kg',
            'ohg',
            'ug',
            'se',
            'eg',
            'ev',
            'ltd',
            'limited',
            'inc',
            'incorporated',
            'corp',
            'corporation',
            'co',
            'plc',
            'llc',
            'llp',
            'sarl',
            'sa',
            'bv',
            'nv',
            'ab',
            'oy',
            'as',
            'spa',
            'srl',
            'sas',
        ],

        /*
        | Address-assignment pivot flags for buyer-address corroboration
        | (customer_address, country).
        */
        'buyer_address_roles' => [
            'billing_address',
            'postal_address',
        ],

        /*
        | Ordered roles for delivery-address corroboration. The first role is
        | tried alone; remaining roles are the fallback tier when no delivery
        | address fingerprint matches.
        */
        'delivery_address_roles' => [
            'delivery_address',
            'postal_address',
            'billing_address',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field validation (MoSCoW)
    |--------------------------------------------------------------------------
    */

    'field_validation' => [

        /*
        |--------------------------------------------------------------------------
        | MoSCoW Priority per Field
        |--------------------------------------------------------------------------
        |
        | Determines how the InvoiceFieldValidator treats each field:
        | - must:   Missing/invalid → blocks transition to validated, forces human review
        | - should: Missing/invalid → warning, ideally reviewed but not blocking
        | - could:  Missing/invalid → info only, auto-accepted
        |
        | Fields not listed here are treated as 'could' by default.
        |
        */

        'invoice_fields' => [
            // Document identification — MUST
            'invoice_number' => 'must',    // BT-1
            'invoice_date' => 'must',    // BT-2
            'document_type' => 'must',    // BT-3
            'due_date' => 'should',  // BT-9
            'currency' => 'must',    // BT-5

            // Buyer — MUST (core identification)
            'customer_number' => 'must',    // BT-46
            'customer_name' => 'must',    // BT-44
            'customer_address' => 'must',    // BG-8
            'country' => 'could',    // BT-55
            'customer_vat_id' => 'should',  // BT-48
            // Inbox To (mail-sourced only; not EN 16931). Empty blocks delivery via inbox_to.
            'buyer_email' => 'must',

            // Buyer reference
            'customer_reference' => 'could', // BT-10
            'order_number' => 'should',  // BT-13
            'order_date' => 'could',   // [GAP] no EN 16931 BT; not BT-13

            // Delivery
            'delivery_address' => 'must',    // BG-15
            'delivery_date' => 'should',  // BT-72 (header ActualDeliverySupplyChainEvent)

            // Seller — MUST (own company data, from system settings later)
            'supplier_name' => 'must',    // BT-27
            'supplier_vat_id' => 'must',    // BT-31
            'supplier_tax_number' => 'should', // BT-32
            'supplier_address' => 'must',    // BG-5
            'supplier_bank_accounts' => 'should', // BG-16 / BG-17 (BT-84 IBAN)
            'supplier_email' => 'should', // BT-34 / BT-43
            'supplier_phone' => 'should', // BT-42
            'payment_means' => 'must', // BT-81
            'vat_category' => 'must', // BT-118

            // Agent & terms
            'agent' => 'could',  // BT-41
            'payment_terms' => 'should',  // BT-20
            'delivery_terms' => 'could',  // BT-22 (invoice note)
            'shipping_method' => 'could',  // BT-22 (invoice note)
            'notes' => 'could',  // BT-22 (parser free-text notes)

            // Amounts — MUST
            'net_total' => 'must',    // BT-109
            'vat_rate' => 'must',    // BT-119
            'vat_amount' => 'must',    // BT-110
            'gross_total' => 'must',    // BT-112

            // Optional amounts
            'discount_percent' => 'could',  // BG-20 / BT-94
            'discount_amount' => 'could',  // BG-20 / BT-92
            'shipping_cost' => 'could',  // BG-21 / BT-99
            'minimum_quantity_surcharge' => 'could',  // BG-21 / BT-99
            'freight_flat_rate' => 'could',  // BG-21 / BT-99
            'packaging_cost' => 'could',  // BG-21 / BT-99
        ],

        'invoice_line_fields' => [
            'position' => 'must',  // BT-126
            'description' => 'must',    // BT-153
            'quantity' => 'must',    // BT-129
            'unit' => 'must',    // BT-130
            'unit_price' => 'must',    // BT-146
            'line_total' => 'must',    // BT-131
            'vat_category' => 'must', // BT-151 (inherits header stamp)

            'article_number' => 'should',  // BT-155
            'material' => 'should',  // BG-32 / BT-160–161 (host-specific)
            'customs_tariff_number' => 'could', // BT-158

            'description_detail' => 'could',  // BT-154
            'material_test_certificate' => 'could',  // BG-32 / BT-160–161
            'material_test_certificate_price' => 'could',  // BG-28 line charge
            'weight_kg_total' => 'could',
            'weight_kg_net' => 'could',
            'surcharge_amount' => 'could',
            'surcharge_description' => 'could',
            'delivery_date' => 'should',  // BG-26 / BT-134 (BillingSpecifiedPeriod in XML)
            'delivery_note_number' => 'could', // BT-16
            'order_number' => 'could',   // BT-132 (item-level override)
            'order_date' => 'could',  // [GAP] no EN 16931 BT; not BT-13
            'delivery_address' => 'could',
        ],

        /*
        |--------------------------------------------------------------------------
        | Contextual Rules (should-priority fields)
        |--------------------------------------------------------------------------
        |
        | When a 'should' field is empty: if it appears in the contextual list below,
        | status is 'missing'; otherwise 'not_applicable'. Fields not listed default
        | to 'not_applicable' when empty.
        |
        */

        'invoice_contextual_should' => [
            'customer_vat_id',
            'payment_terms',
            'supplier_tax_number',
            'supplier_bank_accounts',
            'supplier_email',
            'supplier_phone',
            'delivery_date',
        ],

        'invoice_line_contextual_should' => [
            'article_number',
            'material',
            'delivery_date',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dispatch approval gate
    |--------------------------------------------------------------------------
    |
    | When required, documents must reach approval_status=approved before the
    | dispatch path accepts them. auto_approve_enabled is independent: when
    | false, clean documents stay pending until a human approves.
    |
    */

    'approval' => [
        'required' => (bool) env('EBILLING_APPROVAL_REQUIRED', true),
        'auto_approve_enabled' => (bool) env('EBILLING_APPROVAL_AUTO_APPROVE', true),
    ],


    /*
    |--------------------------------------------------------------------------
    | Invoice ViewInvoice UI (ADR 0007)
    |--------------------------------------------------------------------------
    |
    | Denylist + collapsible field-group defaults. Presentation only — does not
    | change MoSCoW validation under field_validation.
    |
    */

    'invoice_ui' => [
        'invoice_fields_hidden' => [],
        'invoice_line_fields_hidden' => [],
        'field_groups' => [
            'document' => ['default_open' => true],
            'supplier' => ['default_open' => true],
            'buyer' => ['default_open' => true],
            'delivery' => ['default_open' => true],
            'totals' => ['default_open' => true],
            'notes' => ['default_open' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Foreign invoice disposition
    |--------------------------------------------------------------------------
    |
    | After FilterForeignInvoiceJob classifies a non-domestic invoice:
    | ignore (default) — settle Ignored / IgnoredForeign only
    | forward — Source-PDF relay to inbox To, then settle (ADR 0006)
    |
    */

    'foreign' => [
        'disposition' => env('EBILLING_FOREIGN_DISPOSITION', 'ignore'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery dispatch
    |--------------------------------------------------------------------------
    |
    | When enabled, approving a document queues DispatchDocumentJob, which calls
    | each DeliveryChannelInterface listed in channels. The package ships no
    | transport; optional MailDeliveryChannel is an orchestrator that needs host
    | bindings for InvoiceMailSenderInterface and DeliveryRecipientResolverInterface.
    | Default false so the approval gate can be verified before anything is sent.
    |
    */

    'delivery' => [
        'enabled' => (bool) env('EBILLING_DELIVERY_ENABLED', false),
        'mailer' => env('EBILLING_DELIVERY_MAILER'),
        'recipients' => [
            // inbox_to | master | none
            'mail_source' => env('EBILLING_DELIVERY_RECIPIENTS_MAIL_SOURCE', 'inbox_to'),
            'manual_upload' => env('EBILLING_DELIVERY_RECIPIENTS_MANUAL_UPLOAD', 'none'),
        ],
        'channels' => [
            // e.g. Moox\EBilling\Delivery\MailDeliveryChannel::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Review notification announce
    |--------------------------------------------------------------------------
    |
    | When a document enters dispatch-approval review, the package emits
    | DocumentEnteredReview and hands off via NotifyDocumentsNeedReviewJob.
    | It never sends mail. Hosts consume the event and/or job payload.
    |
    | strategy: immediate (one notify job per document) or batched (collect
    | document ids under a cache batch key; flush drains and dispatches one job).
    | batch_key: window (time bucket) or day (calendar day).
    | batch_window_minutes: size of the window bucket when batch_key=window.
    |
    */

    'notification' => [
        'strategy' => env('EBILLING_REVIEW_NOTIFICATION_STRATEGY', 'immediate'),
        'batch_key' => env('EBILLING_REVIEW_NOTIFICATION_BATCH_KEY', 'window'),
        'batch_window_minutes' => (int) env('EBILLING_REVIEW_NOTIFICATION_BATCH_WINDOW', 60),
        // Class implementing ReviewNotificationRecorderInterface, or null for no-op.
        // Same host-config pattern as `parser`.
        'recorder' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Approval escalation scan
    |--------------------------------------------------------------------------
    |
    | Scheduled scan (host crontab) finds pending documents past configured
    | thresholds and dispatches NotifyDocumentsNeedReviewJob with escalation_level.
    | Empty levels disables the feature. day_counting applies only to unit=days;
    | hours always use wall-clock time. No recipients or wording here.
    |
    */

    'escalation' => [
        'day_counting' => env('EBILLING_ESCALATION_DAY_COUNTING', 'working'),
        'working_weekdays' => [1, 2, 3, 4, 5],
        'exclude_dates' => [],
        'levels' => [
            // ['key' => 'reminder', 'after' => 4, 'unit' => 'hours'],
            // ['key' => 'escalate', 'after' => 1, 'unit' => 'days'],
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Invoice resource relations (merged into invoice.relations at boot)
    |--------------------------------------------------------------------------
    |
    | Read-only ConfigRelationManager tabs on InvoiceResource (delivery, KoSIT,
    | veraPDF). Soft-deps: entries whose model class is missing are skipped when
    | merged. Mail send logs stay in getDeclaredRelations() (OR invoice∥document).
    | Audit Activities come from moox/audit. Invoice detail is the e-billing hub.
    |
    */

    'invoice_relations' => [
        'delivery_attempts' => [
            'kind' => 'has_many',
            'presentation' => 'tab',
            'label' => 'trans//e-billing::fields.section_delivery_attempts',
            'relationship' => 'deliveryAttempts',
            'model' => EbillingDeliveryAttempt::class,
            'translation_prefix' => 'e-billing::fields',
            'display_columns' => [
                'channel',
                'recipient',
                'success_label',
                'failure_reason_label',
                'created_at',
                'correlation_id',
            ],
            'badge_columns' => ['success_label'],
            'sortable_columns' => ['created_at'],
            'default_order' => [
                ['column' => 'created_at', 'direction' => 'desc'],
            ],
            'actions' => [
                'header' => [],
                'record' => [],
                'toolbar' => [],
            ],
        ],
        'kosit_validations' => [
            'kind' => 'has_many',
            'presentation' => 'tab',
            'label' => 'trans//e-billing::fields.section_kosit_validations',
            'relationship' => 'kositValidations',
            'model' => KositValidation::class,
            'related_resource' => KositValidationResource::class,
            'translation_prefix' => 'kosit-validator::fields',
            'display_columns' => [
                'result',
                'filename',
                'errors_count',
                'validated_at',
            ],
            'badge_columns' => ['result'],
            'sortable_columns' => ['validated_at'],
            'actions' => [
                'header' => [],
                'record' => ['view'],
                'toolbar' => [],
            ],
        ],
        'verapdf_validations' => [
            'kind' => 'has_many',
            'presentation' => 'tab',
            'label' => 'trans//e-billing::fields.section_verapdf_validations',
            'relationship' => 'veraPdfValidations',
            'model' => VeraPdfValidation::class,
            'related_resource' => VeraPdfValidationResource::class,
            'translation_prefix' => 'verapdf::fields',
            'display_columns' => [
                'result',
                'filename',
                'validated_at',
            ],
            'badge_columns' => ['result'],
            'sortable_columns' => ['validated_at'],
            'actions' => [
                'header' => [],
                'record' => ['view'],
                'toolbar' => [],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Morph pivots (owner side → kosit_validatables)
    |--------------------------------------------------------------------------
    */

    'morph_relations' => [
        'kosit_validatables' => [
            'relationship' => 'kositValidations',
            'model' => KositValidation::class,
            'pivot_model' => KositValidatable::class,
            'pivot_table' => 'kosit_validatables',
            'morph_name' => 'validatable',
            'pivot_columns' => [],
            'related_key' => 'kosit_validation_id',
        ],
        'verapdf_validatables' => [
            'relationship' => 'veraPdfValidations',
            'model' => VeraPdfValidation::class,
            'pivot_model' => VeraPdfValidatable::class,
            'pivot_table' => 'verapdf_validatables',
            'morph_name' => 'validatable',
            'pivot_columns' => [],
            'related_key' => 'verapdf_validation_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit defaults
    |--------------------------------------------------------------------------
    |
    | Registered with moox/audit when installed. Override in config/audit.php.
    | Tracks the structured invoice and the gateway document (pipeline state).
    |
    */

    'audit' => [
        'enabled' => true,
        'models' => [
            Invoice::class => [
                'log_name' => 'e-billing',
                'subject_label_resolver' => InvoiceActivitySubjectLabel::class,
                'attribute_label_resolver' => EbillingActivityAttributeLabels::class,
                'attributes' => [
                    'invoice_number',
                    'invoice_date',
                    'document_type',
                    'due_date',
                    'currency',
                    'customer_number',
                    'customer_reference',
                    'order_number',
                    'order_date',
                    'delivery_date',
                    'payment_terms',
                    'shipping_method',
                    'delivery_terms',
                    'seller',
                    'buyer',
                    'delivery',
                    'payment_means',
                    'net_total',
                    'vat_rate',
                    'vat_amount',
                    'gross_total',
                ],
            ],
            EbillingDocument::class => [
                'log_name' => 'e-billing',
                'label' => 'trans//e-billing::ebilling.ebilling_document',
                'title_attribute' => 'format',
                'attribute_label_resolver' => EbillingActivityAttributeLabels::class,
                // Pipeline writes many intermediate rows; only terminal gateway
                // outcomes, review decisions, and approval changes create update audits.
                'significant_updates' => [
                    'gateway_status' => [
                        'generation_failed',
                        'validated',
                        'validation_failed',
                        'validator_error',
                        'ignored_foreign',
                        'ignored_identical_duplicate',
                    ],
                    'review_status' => [
                        'validated',
                        'db_validated',
                        'human_confirmed',
                    ],
                    'approval_status' => [
                        'pending',
                        'approved',
                        'rejected',
                    ],
                    'approval_reason' => '*',
                ],
                'attributes' => [
                    'format',
                    'gateway_status',
                    'review_status',
                    'approval_status',
                    'approval_reason',
                    'validation_score',
                    'artifact_content_hash',
                    'customer_id',
                    'company_id',
                    'attribution_source',
                    'invoice_id',
                    'ignored_reason',
                    'scope',
                    'storage_disk',
                ],
                'hidden_attributes' => [
                    'bill_data',
                    'field_validations',
                    'source_type',
                    'source_id',
                    'error_message',
                    'xml_storage_path',
                    'pdf_storage_path',
                    'copy_pdf_storage_path',
                ],
            ],
        ],
        'filament' => [
            InvoiceResource::class => [
                'owner_model' => Invoice::class,
                'aggregate_subjects' => [
                    EbillingDocument::class => 'ebillingDocument',
                ],
            ],
            CreditNoteResource::class => [
                'owner_model' => Invoice::class,
                'aggregate_subjects' => [
                    EbillingDocument::class => 'ebillingDocument',
                ],
            ],
        ],
    ],

];
