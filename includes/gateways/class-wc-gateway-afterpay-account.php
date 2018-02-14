<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'plugins_loaded', 'init_wc_gateway_afterpay_account_class' );
add_filter( 'woocommerce_payment_gateways', 'add_afterpay_account_method' );

/**
 * Initialize AfterPay Account payment gateway
 *
 * @wp_hook plugins_loaded
 */
function init_wc_gateway_afterpay_account_class() {
	/**
	 * AfterPay Account Payment Gateway.
	 *
	 * Provides AfterPay Account Payment Gateway for WooCommerce.
	 *
	 * @class       WC_Gateway_AfterPay_Account
	 * @extends     WC_Gateway_AfterPay_Factory
	 * @version     0.1
	 * @author      Krokedil
	 */
	class WC_Gateway_AfterPay_Account extends WC_Gateway_AfterPay_Factory {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id                 = 'afterpay_account';
			$this->method_title       = __( 'AfterPay Account', 'woocommerce-gateway-afterpay' );
			$this->has_fields         = true;
			$this->method_description = __( 'Allows payments through ' . $this->method_title . '.', 'woocommerce-gateway-afterpay' );

			// Define user set variables.
			$this->title       		= $this->get_option( 'title' );
			$this->description_se 	= $this->get_option( 'description_se' );
			$this->description_no 	= $this->get_option( 'description_no' );
			$this->client_id_se   	= $this->get_option( 'client_id_se' );
			$this->username_se    	= $this->get_option( 'username_se' );
			$this->password_se    	= $this->get_option( 'password_se' );
			$this->client_id_no   	= $this->get_option( 'client_id_no' );
			$this->username_no    	= $this->get_option( 'username_no' );
			$this->password_no    	= $this->get_option( 'password_no' );
			$this->debug       		= $this->get_option( 'debug' );
			$this->api_key       	= $this->get_option( 'api_key' );
			$this->x_auth_key_se    = $this->get_option( 'x_auth_key_se' );
			$this->x_auth_key_no    = $this->get_option( 'x_auth_key_no' );
			$this->testmode       	= $this->get_option( 'testmode' );

			// Set country and merchant credentials based on currency.
			switch ( get_woocommerce_currency() ) {
				case 'NOK' :
					$this->afterpay_country 	= 'NO';
					$this->client_id  			= $this->client_id_no;
					$this->username     		= $this->username_no;
					$this->password     		= $this->password_no;
					$this->x_auth_key			= $this->x_auth_key_no;
					break;
				case 'SEK' :
					$this->afterpay_country		= 'SE';
					$this->client_id  			= $this->client_id_se;
					$this->username     		= $this->username_se;
					$this->password     		= $this->password_se;
					$this->x_auth_key			= $this->x_auth_key_se;
					break;
				default:
					$this->afterpay_country 	= '';
					$this->client_id  			= '';
					$this->username     		= '';
					$this->password     		= '';
					$this->x_auth_key			= '';
			}
			
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			$this->supports = array(
				'products',
				'refunds'
			);

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
			add_action( 'woocommerce_thankyou', array( 
				$this, 
				'clear_afterpay_sessions' 
			) );
			add_action( 'woocommerce_checkout_process', array( 
				$this, 
				'process_checkout_fields' 
			) );
		}
		
		/**
		 * Display payment fields for Part Payment
		 */
		public function payment_fields() {
			parent::payment_fields();
			
			echo $this->get_afterpay_info();
		}
	}

}

/**
 * Add AfterPay payment gateway
 *
 * @wp_hook woocommerce_payment_gateways
 *
 * @param  $methods Array All registered payment methods
 *
 * @return $methods Array All registered payment methods
 */
function add_afterpay_account_method( $methods ) {
	$methods[] = 'WC_Gateway_AfterPay_Account';

	return $methods;
}