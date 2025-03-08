# Bounces

One of the main reasons to build a own mail solution is to have full control over the emails that are sent and received. All current solutions for Laravel do allow to distinguish between bounce subtypes or scopes.

The following table shows the different types of bounces and what to do about them.

| Type | Scope   | Meaning                                          | What to do        |
| ---- | ------- | ------------------------------------------------ | ----------------- |
| Soft | User    | 📬 Mailbox full, quota exceeded                  | Retry later       |
| Soft | Server  | 🏢 Mail server down, temporary issue             | Retry later       |
| Soft | Content | 🛑 Spam or policy rejection, may succeed later   | Adjust content    |
| Hard | User    | ❌ No mailbox, user does not exist               | Remove from list  |
| Hard | Server  | 🌍 No DNS, no mail server, domain does not exist | Remove from list  |
| Hard | Content | 🚫 Blocked permanently (blacklist, DMARC policy) | Sender reputation |

This information is available by all known mail services:

-   [Postmark](https://postmarkapp.com)
-   [Mailgun](https://mailgun.com)
-   [Sendgrid](https://sendgrid.com)
-   [Mailjet](https://mailjet.com)
-   [Brevo (ex. Sendinblue)](https://brevo.com)
-   [Resend](https://resend.com)
-   [Amazon SES](https://aws.amazon.com/ses/)
-   [Mailchimp](https://mailchimp.com)

Currently, the following mail services are supported by the Laravel ecosystem:

-   Postmark, by [vormkracht10/laravel-mails](https://github.com/vormkracht10/laravel-mails)
-   Mailgun, by [vormkracht10/laravel-mailgun](https://github.com/vormkracht10/laravel-mailgun)
-   Resend, Open PR for [vormkracht10/laravel-resend](https://github.com/vormkracht10/laravel-resend)

There are some other packages and articles about this topic:

-   [Tracking email delivery in Laravel without third-party services](https://medium.com/@python-javascript-php-html-css/tracking-email-delivery-in-laravel-without-third-party-services-748648460597)
-   [jdavidbakr/mail-tracker](https://github.com/jdavidbakr/mail-tracker)
-   [stefanzweifel/laravel-sends](https://github.com/stefanzweifel/laravel-sends)
