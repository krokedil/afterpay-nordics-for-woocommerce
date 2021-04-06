<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'plugins_loaded', 'init_wc_gateway_afterpay_part_payment_class' );
add_filter( 'woocommerce_payment_gateways', 'add_afterpay_part_payment_method' );

/**
 * Initialize AfterPay Part_Payment payment gateway
 *
 * @wp_hook plugins_loaded
 */
function init_wc_gateway_afterpay_part_payment_class() {
	/**
	 * AfterPay Part_Payment Payment Gateway.
	 *
	 * Provides AfterPay Part_Payment Payment Gateway for WooCommerce.
	 *
	 * @class       WC_Gateway_AfterPay_Part_Payment
	 * @extends     WC_Gateway_AfterPay_Factory
	 * @version     0.1
	 * @author      Krokedil
	 */
	class WC_Gateway_AfterPay_Part_Payment extends WC_Gateway_AfterPay_Factory {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id           = 'afterpay_part_payment';
			$this->method_title = __( 'AfterPay Part Payment', 'afterpay-nordics-for-woocommerce' );

			// $this->icon               = apply_filters( 'woocommerce_afterpay_part_payment_icon', AFTERPAY_URL . '/assets/images/logo.png' );
			$this->has_fields         = true;
			$this->method_description = __( 'Allows payments through ' . $this->method_title . '.', 'afterpay-nordics-for-woocommerce' );

			// Define user set variables
			$this->title              = $this->get_option( 'title' );
			$this->description_se     = $this->get_option( 'description_se' );
			$this->description_no     = $this->get_option( 'description_no' );
			$this->client_id_se       = $this->get_option( 'client_id_se' );
			$this->username_se        = $this->get_option( 'username_se' );
			$this->password_se        = $this->get_option( 'password_se' );
			$this->client_id_no       = $this->get_option( 'client_id_no' );
			$this->username_no        = $this->get_option( 'username_no' );
			$this->password_no        = $this->get_option( 'password_no' );
			$this->debug              = $this->get_option( 'debug' );
			$this->api_key            = $this->get_option( 'api_key' );
			$this->x_auth_key_se      = $this->get_option( 'x_auth_key_se' );
			$this->x_auth_key_no      = $this->get_option( 'x_auth_key_no' );
			$this->account_profile_no = $this->get_option( 'account_profile_no' );
			$this->testmode           = $this->get_option( 'testmode' );

			// Set country and merchant credentials based on currency.
			switch ( get_woocommerce_currency() ) {
				case 'NOK':
					$this->afterpay_country = 'NO';
					$this->client_id        = $this->client_id_no;
					$this->username         = $this->username_no;
					$this->password         = $this->password_no;
					$this->x_auth_key       = $this->x_auth_key_no;
					break;
				case 'SEK':
					$this->afterpay_country = 'SE';
					$this->client_id        = $this->client_id_se;
					$this->username         = $this->username_se;
					$this->password         = $this->password_se;
					$this->x_auth_key       = $this->x_auth_key_se;
					break;
				default:
					$this->afterpay_country = '';
					$this->client_id        = '';
					$this->username         = '';
					$this->password         = '';
					$this->x_auth_key       = '';
			}

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			$this->supports = array(
				'products',
				'refunds',
			);

			// Actions
			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array(
					$this,
					'process_admin_options',
				)
			);
			add_action(
				'woocommerce_thankyou',
				array(
					$this,
					'clear_afterpay_sessions',
				)
			);
			add_action(
				'woocommerce_checkout_process',
				array(
					$this,
					'process_checkout_fields',
				)
			);
		}

		/**
		 * Display payment fields for Part Payment
		 */
		public function payment_fields() {
			parent::payment_fields();

			// Print AfterPay Terms & Conditions link
			echo $this->get_afterpay_info();

			$this->get_available_installment_plans();

		}

		/**
		 * Sort payment plans before displaying them, shortest to longest
		 *
		 * @param $plana
		 * @param $planb
		 *
		 * @return int
		 */
		public function sort_payment_plans( $plana, $planb ) {
			return $plana->NumberOfInstallments > $planb->NumberOfInstallments;
		}

		/**
		 * Get available installment plans
		 */
		public function get_available_installment_plans() {
			switch ( get_woocommerce_currency() ) {
				case 'SEK':
					$country_code = 'SE';
					break;
				case 'NOK':
					$country_code = 'NO';
					break;
				default:
					$country_code = 'SE';
			}

			$request                        = new WC_AfterPay_Request_Available_Installment_Plans( $this->x_auth_key, $this->testmode );
			$response                       = $request->response( WC()->cart->total, get_woocommerce_currency(), $country_code );
			$response                       = json_decode( $response );
			$payment_options_details_output = '';
			/*
			echo '<pre>';
			print_r( $response );
			echo '</pre>';
			*/

			if ( ! is_wp_error( $response ) ) {
				if ( is_array( $response ) && 'BusinessError' == $response[0]->type ) {
					echo '<p>' . $response[0]->message . '</p>';
				} else {
					$installment_plans = $response->availableInstallmentPlans;
					if ( $installment_plans ) {

						// WC()->session->set( 'afterpay_available_installment_plans', $response->availableInstallmentPlans );

						echo '<p>' . __( 'Please select a payment plan:', 'afterpay-nordics-for-woocommerce' ) . '</p>';
						$i = 0;
						foreach ( $installment_plans as $key => $installment_plan ) {

							if ( $installment_plan->installmentProfileNumber < 11 ) {
								$i ++;
								$label = sprintf(
									'%1$s %2$s, %3$s / %4$s',
									$installment_plan->numberOfInstallments,
									__( 'months', 'afterpay-nordics-for-woocommerce' ),
									wc_price( round( $installment_plan->installmentAmount ) ),
									__( 'mo', 'afterpay-nordics-for-woocommerce' )
								);

								// Create payment plan details output
								if ( $i < 2 ) {
									$inline_style = 'style="clear:both;position:relative"';
									$extra_class  = 'visible-ppp';
								} else {
									$inline_style = 'style="clear:both;display:none;position:relative"';
									$extra_class  = '';
								}

								$payment_options_details_output .= '<div class="afterpay-ppp-details ' . $extra_class . '" data-campaign="' . $installment_plan->installmentProfileNumber . '" ' . $inline_style . '><small>';

								$payment_options_details_output .= sprintf(
									__( 'Start fee: %1$s. Monthly fee: %2$s. Rate: %3$s%5$s. Annual effective rate: %4$s%5$s. Total: %6$s.', 'afterpay-nordics-for-woocommerce' ),
									wc_price( $installment_plan->startupFee ),
									wc_price( $installment_plan->monthlyFee ),
									$installment_plan->interestRate,
									$installment_plan->effectiveAnnualPercentageRate,
									'%',
									wc_price( $installment_plan->totalAmount )
								);
								$payment_options_details_output .= '</small></div>';

								echo '<input type="radio" name="afterpay_installment_plan" id="afterpay-installment-plan-' . $installment_plan->installmentProfileNumber . '" value="' . $installment_plan->installmentProfileNumber . '" ' . checked( $key, 0, false ) . ' />';
								echo '<label for="afterpay-installment-plan-' . $installment_plan->installmentProfileNumber . '"> ' . $label . '</label>';
								echo '<br>';
							}
						}

						// Print payment plan details
						echo $payment_options_details_output;

					}
				}
			} else {
				// WC()->session->__unset( 'afterpay_installment_plans' );

			}
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
function add_afterpay_part_payment_method( $methods ) {
	$methods[] = 'WC_Gateway_AfterPay_Part_Payment';

	return $methods;
}
