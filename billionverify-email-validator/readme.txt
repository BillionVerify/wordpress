=== BillionVerify Email Validator ===
Contributors: billionverify
Tags: email validator, email verifier, form validation, spam protection, user registration
Requires at least: 4.7
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Real-time email verification for your WordPress forms. Block invalid, disposable, catch-all and role addresses using the BillionVerify API.

== Description ==

BillionVerify Email Validator checks email addresses in real time as your forms are submitted, stopping fake, mistyped, disposable and undeliverable emails before they reach your database. Cleaner signups mean fewer bounces, less spam and healthier email campaigns.

The plugin verifies each address against the BillionVerify API — performing syntax, MX and SMTP mailbox checks — and rejects the submission when the address matches a status you have chosen to block.

**Key features**

* Real-time verification during form submission (no front-end changes required).
* Detects valid, invalid, disposable, catch-all, role and risky addresses.
* Choose exactly which statuses block a submission — invalid only by default.
* Custom error message shown to visitors.
* Built-in connection test and single-email test tool in the admin.
* Result caching to save credits on repeated addresses.
* Fail-open by default: if the API is unreachable or credits run out, forms keep working.

**Supported forms**

* Default WordPress registration form (including multisite signup)
* WordPress comment form
* WordPress lost-password form
* WooCommerce checkout and account registration
* Contact Form 7
* WPForms
* Gravity Forms
* Elementor Pro Forms
* Fluent Forms

== Third-Party Service Usage ==

This plugin sends email addresses to the BillionVerify API (https://billionverify.com/) for verification. Through API calls to BillionVerify servers it verifies email addresses and retrieves account credit information, using the following endpoints:

* Verify an email address: https://api.billionverify.com/v1/verify/single
* Retrieve credit balance: https://api.billionverify.com/v1/credits

By installing and activating this plugin, you consent to the transmission of submitted email addresses to these URLs for the purpose of verification. Use of the BillionVerify service is subject to BillionVerify's Terms of Service and Privacy Policy at https://billionverify.com/.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/billionverify-email-validator`, or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings → BillionVerify.
4. Enter your BillionVerify API key and click "Test connection".
5. Choose which statuses to block and which forms to protect.
6. Use the "Test an Email" tool to confirm everything works.

== Frequently Asked Questions ==

= How does it work? =

When a visitor submits a protected form, the plugin sends the email address to the BillionVerify API. If the address matches a status you chose to block (for example "invalid" or "disposable"), the submission is rejected and the visitor is asked to use a different email. No email is sent to the address during verification.

= What happens if my account runs out of credits? =

By default the plugin "fails open": if verification cannot be completed, the email is accepted and your forms keep working as if the plugin were not installed. You can switch this to "fail closed" in the settings.

= Does it consume a credit on every submission? =

Each unique address is verified once and cached for the lifetime you configure, so repeated submissions of the same address do not spend extra credits. The "unknown" status is never charged by BillionVerify.

= Do I need to modify my existing forms? =

No. Enable the integrations you want in Settings → BillionVerify and the plugin hooks into those forms automatically.

== Changelog ==

= 1.0.0 =
* Initial release.
