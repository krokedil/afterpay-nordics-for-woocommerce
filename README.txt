=== AfterPay Nordics for WooCommerce ===
Contributors: krokedil, arvato, NiklasHogefjord, slobodanmanic
Tags: ecommerce, e-commerce, woocommerce, afterpay, arvato
Requires at least: 4.2
Tested up to: 4.9
Requires PHP: 5.6
Stable tag: trunk
Requires WooCommerce at least: 3.0.0
Tested WooCommerce up to: 3.2.3
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

= 0.4.0 		-  2018.03.12 =
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

= 0.3.2 		- 2017.11.24 =
* Fix			- Remove - from personal/organization number sent to AfterPay.

= 0.3.1 		- 2017.11.19 =
* Fix			- Don’t display payment method if it isn’t enabled in settings.
* Fix			- Don’t display part payment if only selling to companies.
* Tweak			- Hide part payment if customer type company is selected.

= 0.3 			- 2017.11.17 =
* WordPress.org release

= 0.2.1 		- 2017.11.09 =
* Fix			- Get address feature is not available for companies.
* Tweak			- Improved handling of returned address data.

= 0.2.0 		- 2017.11.08 =
* Tweak			- Changed textdomain name.

= 0.1 			- 2017.08.24 =
* Initial release