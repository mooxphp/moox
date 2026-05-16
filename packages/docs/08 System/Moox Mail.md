# Moox Mail

Mail for everything, transactional, marketing, Moox-integrated.

https://maizzle.com/



## Mail Templates

| **Field**  | **Type** | **Beschreibung**                    |
| ---------- | -------- | ----------------------------------- |
| id         | UUID     | Primärschlüssel                     |
| key        | String   | order.placed, newsletter.welcome, … |
| layout_id  | UUID     | FK auf mail_layouts                 |
| is_enabled | Boolean  | Deaktivieren ohne Löschen           |
| package    | String   | z. B. moox/order, moox/shop         |
| created_at | TS       |                                     |
| updated_at | TS       |                                     |

Mail Template Translations

| **Field**        | **Type**    | **Beschreibung**              |
| ---------------- | ----------- | ----------------------------- |
| id               | UUID        |                               |
| mail_template_id | FK → parent |                               |
| locale           | String(5)   | en, de, fr                    |
| subject          | String      | Betreff mit Platzhaltern      |
| body_markdown    | Text        | Mailtext (Markdown oder HTML) |
| preview_data     | JSON        | Optional: für UI-Vorschau     |
| _at/by fields    | Optional    | Audit trail                   |

https://github.com/aw-studio/laravel-maillog

https://github.com/gearbox-solutions/mail-log

https://filamentphp.com/plugins/rickdbcn-email



https://github.com/backstagephp/laravel-mails ... also for Filament