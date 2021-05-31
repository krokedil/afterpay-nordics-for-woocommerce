<?php
/**
 * WooCommerce AfterPay Gateway
 *
 * @since 0.1
 *
 * @package WC_Gateway_AfterPay
 *
 * @wordpress-plugin
 * Plugin Name:     AfterPay Nordics for WooCommerce
 * Plugin URI:      https://krokedil.se/afterpay/
 * Description:     Provides an AfterPay v3 payment gateway for WooCommerce.
 * Version:         1.0.4
 * Author:          Krokedil
 * Author URI:      https://krokedil.se/
 * Developer:       Krokedil
 * Developer URI:   https://krokedil.se/
 * Text Domain:     afterpay-nordics-for-woocommerce
 * Domain Path:     /languages
 *
 * WC requires at least: 4.0.0
 * WC tested up to: 5.3.0
 *
 * Copyright:       © 2017-2021 Krokedil.
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Localisation.
 */
load_plugin_textdomain( 'afterpay-nordics-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );


// Define plugin paths
define( 'AFTERPAY_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
define( 'AFTERPAY_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'AFTERPAY_VERSION', '1.0.4' );

// Compatibility functions
require_once AFTERPAY_PATH . '/includes/krokedil-compatibility-functions.php';

require_once AFTERPAY_PATH . '/includes/gateways/class-wc-gateway-afterpay-factory.php';
require_once AFTERPAY_PATH . '/includes/gateways/class-wc-gateway-afterpay-invoice.php';
require_once AFTERPAY_PATH . '/includes/gateways/class-wc-gateway-afterpay-part-payment.php';
require_once AFTERPAY_PATH . '/includes/gateways/class-wc-gateway-afterpay-account.php';

require_once AFTERPAY_PATH . '/includes/class-pre-check-customer.php';
require_once AFTERPAY_PATH . '/includes/class-cancel-reservation.php';

require_once AFTERPAY_PATH . '/includes/class-process-order-lines.php';
require_once AFTERPAY_PATH . '/includes/class-invoice-fee.php';

// V3
require_once AFTERPAY_PATH . '/includes/requests/class-wc-afterpay-request.php';
require_once AFTERPAY_PATH . '/includes/class-capture.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-customer.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-authorize-payment.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-authorize-subscription-payment.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-create-contract.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-capture-payment.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-cancel-payment.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-refund-payment.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-customer-lookup.php';
require_once AFTERPAY_PATH . '/includes/requests/helpers/class-wc-afterpay-request-available-installment-plans.php';

// Define server endpoints
define(
	'ARVATO_CHECKOUT_LIVE',
	'https://api.afterpay.io'
);
define(
	'ARVATO_CHECKOUT_TEST',
	'https://sandboxapi.horizonafs.com/eCommerceServicesWebApi'
);
