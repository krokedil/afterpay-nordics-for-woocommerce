=== AfterPay Nordics for WooCommerce ===
Contributors: krokedil, arvato, NiklasHogefjord, slobodanmanic
Tags: ecommerce, e-commerce, woocommerce, afterpay, arvato
Requires at least: 4.2
Tested up to: 5.7.2
Requires PHP: 5.6
WC requires at least: 4.0.0
WC tested up to: 5.3.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

AfterPay Nordics for WooCommerce is a plugin that extends WooCommerce, allowing you to take payments via AfterPay's new RESTful Json API.

== Description ==

With this extension you get access to [AfterPay's](http://www.afterpay.se/en/) payment methods - Invoice and Part Payment in Sweden & Invoice in Norway.

= Get started =
More information on how to get started can be found in the [plugin documentation](http://docs.krokedil.com/documentation/afterpay-nordics-for-woocommerce/).

== Installation ==

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go to --> WooCommerce --> Settings --> Checkout and configure your AfterPay settings.

== Frequently Asked Questions ==
= Which countries does this payment gateway support? =
Invoice payments work for Sweden and Norway. Part payment only works for Sweden at the moment. Norway will be added in short.

= Where can I find AfterPay for WooCommerce documentation? =
For help setting up and configuring AfterPay for WooCommerce please refer to our [documentation](http://docs.krokedil.com/documentation/afterpay-nordics-for-woocommerce/).

= Where can I get support? =
If you get stuck, you can send a support ticket to support@krokedil.se. You can ask your question in English, Swedish or Norwegian. Support tickets written in Norwegian will be answered in Swedish. 



== Changelog ==

= 2021.05.31    - version 1.0.4 =
* Tweak         - Remove gulpfile.

= 2021.05.31    - version 1.0.1 =
* Tweak         - Update supported version number.

= 2021.02.17    - version 1.0.0 =
* Feature       - Adds support for invoice payments for Danish customers.
* Tweak         - Adds totalNetAmount & order lines to capture payment request.
* Tweak         - Adds netUnitPrice & vatAmount to refund payment requests.
* Fix           - WC deprecated notices & PHP notices fix.
* Fix           - Do not overwrite the customer address in Woo order if company address isn't returned at all from AfterPay.

= 2020.01.14    - version 0.9.0 =
* Feature       - Add setting for display/hide Get Address field in checkout for Norwegian customers.
* Feature       - Add setting for wether or not to display Get address field if AfterPay isn't the selected payment method.
* Tweak         - Send vatAmount in requests to AfterPay.

= 2019.06.07 	- version 0.8.5 =
* Fix           - Don't check and correct billing address in Woo order for DE customers. This is handled by AfterPay before order is approved in checkout.

= 2019.06.05 	- version 0.8.4 =
* Fix           - Add response code 200.104 (Address Correction) during Authorize request response as valid response to adjust WooCommerce checkout address.
* Fix           - Fixed customer last name not being set correctly during address adjustement for DE customers.

= 2019.06.04 	- version 0.8.3 =
* Feature       - Added setting for allowing Street number to be a separate field in WooCommerce checkout. That input field will be sent as streetNumber to AfterPay. 
* Tweak         - Improved handling of Street number, Additional street number & Care of returned from AfterPay. Mainly for DE customers.

= 2019.05.28 	- version 0.8.2 =
* Fix           - Don't send mobilePhone param to AfterPay if billing phone is not added to WC order.

= 2019.05.27 	- version 0.8.1 =
* Fix           - Don't trigger numeric Date of birth check in checkout for DE.

= 2019.05.27 	- version 0.8.0 =
* Feature       - Add support for invoice payments for German customers.

= 2019.03.05 	- version 0.7.0 =
* Feature       - Improved handling of refunds (allow order line refunds).
* Fix           - Updated/corrected Norwegian translation.

= 2019.01.30 	- version 0.6.7 =
* Fix           - Fixed refunds on order with no tax rate.
* Fix           - Description on refund no longer required.
* Fix           - Improved error handling of refunds.

= 2018.10.19 	- version 0.6.6 =
* Tweak			- Limit first and last name to max 50 characters when sending them to AfterPay.
* Tweak			- Changed the limit for yourReference to max 19 characters when sending it to AfterPay.
* Tweak			- Add support for multiple subscriptions.
* Tweak			- Keep address fields disabled in checkout for SE after get address request (even if checkout page is reloaded).

= 2018.10.02 	- version 0.6.5 =
* Tweak			- Added filter afterpay_failed_capture_status.
* Tweak			- Send free shipping  info to AfterPay as well (previously only shipping with a price where sent).
* Fix			- Tax rate fix if multiple shipping methods exist in order.

= 2018.09.27 	- version 0.6.4 =
* Fix			- Limit yourReference to 20 characters (sent to AfterPy for B2B purchases in order capture request).
* Fix			- PHP notice fix.

= 2018.09.12 	- version 0.6.3 =
* Tweak			- Improved messaging when entered personal number is in wrong format.
* Tweak			- Display part payment example for all countries (previously only displayed for Norway).
* Tweak			- Updated default Norwegian account payyment method description.
* Tweak			- Translation updates.
* Tweak			- Change AfterPay terms page link for SE.
* Tweak			- Change order of when AfterPay terms link is displayed next to payment method.
* Fix			- CSS change - make sure get address response is displayed after a linebreak.
* Fix			- Make sure TotalNetAmount is sent with 2 decimals.
* Fix			- Add order note that order hasn't been cancelled in AfterPays system when trying to cancel order after it already being captured.

= 2018.06.12 	- version 0.6.2 =
* Tweak			- Add error message as order note + revert status to Processing if AfterPay capture fails.
* Tweak			- Changed url to Swedish afterpay terms URL.
* Tweak			- Added link to AfterPay privacy policy next to get address field (for Sweden).
* Fix			- Fixed PHP warning that caused refunds to not work in some environments.


= 2018.05.09 	- version 0.6.1 =
* Tweak			- Improved messaging to customer in checkout when purchase (Authorize request) is denied by AfterPay.
* Tweak			- Display get address form even if AfterPay isn't the selected payment gateway.

= 2018.04.29 	- version 0.6.0 =
* Feature       - Add support for recurring payments (for invoice) via WooCommerce Subscriptions.
* Tweak         - Store AfterPay customer number in WooCommerce order.
* Tweak         - Added filters afterpay_authorize_order & afterpay_authorize_subscription_renewal_order.
* Tweak         - Only display detailed part payment info to Norwegian customers.
* Fix           - WC 2.6 support in Cancel reservation request.
* Fix           - Send correct params in authorize and capture request for B2B purchases.
* Fix           - Sync afterpay-pre-check-mobile-number field with billing phone field.

= 2018.04.04 	- version 0.5.1 =
* Fix           - Send correct shipping vat to AfterPay even for v2.6.x.

= 2018.03.23 	- version 0.5.0 =
* Tweak         - Compatible with WC 2.6.
* Fix           - Fix so products with 0 amount price & 0% vat can be included in order data sent to AfterPay.

= 2018.03.12 	- version 0.4.0 =
* Feature		- Added Account payments.
* Feature		- Added Part payment Norway.
* Feature		- Add support for partial refunds (if order only contain one tax rate).
* Tweak			- Add setting for separate Description fields for Sweden & Norway.
* Tweak			- Plugin checked against AfterPay design guidelines.
* Tweak			- Remove feature for allowing separate shipping address for companies.
* Tweak			- Remove get address masking.
* Tweak			- Make address fields readonly for Sweden when get address feature has been used.
* Tweak			- Added loading spinner to get address button.
* Tweak			- Added setting for account profile number.
* Fix			- Send correct payment type (installment) for part payment.
* Tweak			- Don’t change address data when switching payment method.
* Tweak			- Update WC order with address information received from AfterPay.

= 2017.11.24 	- version 0.3.2 =
* Fix			- Remove - from personal/organization number sent to AfterPay.

= 2017.11.19 	- version 0.3.1 =
* Fix			- Don’t display payment method if it isn’t enabled in settings.
* Fix			- Don’t display part payment if only selling to companies.
* Tweak			- Hide part payment if customer type company is selected.

= 2017.11.17 	- version 0.3 =
* WordPress.org release

= 2017.11.09 	- version 0.2.1 =
* Fix			- Get address feature is not available for companies.
* Tweak			- Improved handling of returned address data.

= 2017.11.08 	- version 0.2.0 =
* Tweak			- Changed textdomain name.

= 2017.08.24 	- version 0.1 =
* Initial release
