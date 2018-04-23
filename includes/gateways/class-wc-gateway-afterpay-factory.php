<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'plugins_loaded', 'init_wc_gateway_afterpay_factory_class' );

/**
 * Initialize AfterPay Invoice payment gateway
 *
 * @wp_hook plugins_loaded
 */
function init_wc_gateway_afterpay_factory_class() {
	/**
	 * AfterPay Payment Gateway Factory.
	 *
	 * Parent class for all AfterPay payment methods.
	 *
	 * @class       WC_Gateway_AfterPay_Factory
	 * @extends     WC_Payment_Gateway
	 * @version     0.1
	 * @author      Krokedil
	 */
	class WC_Gateway_AfterPay_Factory extends WC_Payment_Gateway {

		/** @var WC_Logger Logger instance */
		public static $log = false;
		public $testmode = '';

		/**
		 * Logging method.
		 *
		 * @param string $message
		 */
		public static function log( $message ) {
			$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
			if ( $afterpay_settings['debug'] == 'yes' ) {
				if ( empty( self::$log ) ) {
					self::$log = new WC_Logger();
				}
				self::$log->add( 'afterpay', $message );
			}
		}

		public $x_auth_key = '';

		public function __construct() {
			add_action( 'woocommerce_order_status_completed', array( $this, 'afterpay_order_completed' ) );
		}

		/**
		 * Check if payment method is available for current customer.
		 */
		public function is_available() {
			// Check if payment method is configured
			$payment_method          = $this->id;
			$payment_method_settings = get_option( 'woocommerce_' . $payment_method . '_settings' );
			$invoice_settings        = get_option( 'woocommerce_afterpay_invoice_settings' );

			// Is the payment method enabled?
			$enabled = $payment_method_settings['enabled'];
			if ( "yes" !== $enabled ) {
				return false;
			}

			// Don't display part payment if only selling to companies
			if ( 'afterpay_part_payment' == $this->id && 'company' == $invoice_settings['customer_type'] ) {
				return false;
			}

			if ( WC()->customer ) {
				// Only activate the payment gateway if the customers country is the same as the shop country ($this->afterpay_country)
                if ( is_callable( array( WC()->customer, 'get_billing_country' ) ) ) {
	                if ( WC()->customer->get_billing_country() == true && WC()->customer->get_billing_country() != $this->afterpay_country ) {
		                return false;
	                }
                } else {
	                if ( WC()->customer->get_country() == true && WC()->customer->get_country() != $this->afterpay_country ) {
		                return false;
	                }
                }

				// Don't display part payment and Account for Norwegian customers
				/*
				if ( WC()->customer->get_billing_country() == true && 'NO' == WC()->customer->get_billing_country() && ( 'afterpay_part_payment' == $this->id || 'afterpay_account' == $this->id ) ) {
					return false;
				}
				*/
			}

			return true;
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$form_fields = array(
				'enabled'        => array(
					'title'   => __( 'Enable/Disable', 'afterpay-nordics-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable ' . $this->method_title, 'afterpay-nordics-for-woocommerce' ),
					'default' => 'yes'
				),
				'title'          => array(
					'title'       => __( 'Title', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'afterpay-nordics-for-woocommerce' ),
					'default'     => __( $this->method_title, 'afterpay-nordics-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'x_auth_key_se'  => array(
					'title'       => __( 'AfterPay X-Auth-Key Sweden', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'text',
					'description' => __(
						'Please enter your AfterPay X-Auth-Key for Sweden; this is needed in order to take payment',
						'afterpay-nordics-for-woocommerce'
					),
				),
				'description_se' => array(
					'title'       => __( 'Description Sweden', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'textarea',
					'desc_tip'    => true,
					'description' => __( 'This controls the description which Swedish customers sees during checkout.', 'afterpay-nordics-for-woocommerce' ),
					'default'     => $this->get_default_description_sweden(),
				),
				'x_auth_key_no'  => array(
					'title'       => __( 'AfterPay X-Auth-Key Norway', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'text',
					'description' => __(
						'Please enter your AfterPay X-Auth-Key for Norway; this is needed in order to take payment',
						'afterpay-nordics-for-woocommerce'
					),
				),
				'description_no' => array(
					'title'       => __( 'Description Norway', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'textarea',
					'desc_tip'    => true,
					'description' => __( 'This controls the description which Norwegian customers sees during checkout.', 'afterpay-nordics-for-woocommerce' ),
					'default'     => $this->get_default_description_norway(),
				),


			);

			// Installment plan for Account (Account Profile number).
			if ( 'afterpay_account' === $this->id ) {
				$form_fields['account_profile_no'] = array(
					'title'       => __( 'Account profile number', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'text',
					'default'     => '1',
					'description' => __(
						'ID number for sent to AfterPay for Account purchases. Defaults to 1. Should usually not be changed.',
						'afterpay-nordics-for-woocommerce'
					),
				);
			}

			// Invoice fee for AfterPay Invoice.
			if ( 'afterpay_invoice' === $this->id ) {
				$form_fields['invoice_fee_id'] = array(
					'title'       => __( 'Invoice Fee', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'text',
					'description' => __(
						'Create a hidden (simple) product that acts as the invoice fee. Enter the ID number in this textfield. Leave blank to disable.',
						'afterpay-nordics-for-woocommerce'
					),
				);

				// Customer type, separate shipping address fand order management or all payment methods are in AfterPay Invoice settings.
				$form_fields['customer_type'] = array(
					'title'       => __( 'Customer type', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Select the type of customer that can make purchases through AfterPay', 'afterpay-nordics-for-woocommerce' ),
					'options'     => array(
						'both'    => __( 'Both person and company', 'afterpay-nordics-for-woocommerce' ),
						'private' => __( 'Person', 'afterpay-nordics-for-woocommerce' ),
						'company' => __( 'Company', 'afterpay-nordics-for-woocommerce' ),
					),
					'default'     => 'both',
				);
				/*
				$form_fields['separate_shipping_companies'] = array(
					'title'   => __( 'Separate shipping address', 'afterpay-nordics-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable separate shipping address for companies', 'afterpay-nordics-for-woocommerce' ),
					'default' => 'no',
				);
				*/
				$form_fields['order_management'] = array(
					'title'   => __( 'Enable Order Management', 'afterpay-nordics-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __(
						'Enable AfterPay order capture on WooCommerce order completion and AfterPay order cancellation on WooCommerce order cancellation',
						'afterpay-nordics-for-woocommerce'
					),
					'default' => 'yes',
				);
			}

			$form_fields['testmode'] = array(
				'title'   => __( 'AfterPay testmode', 'afterpay-nordics-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable AfterPay testmode', 'afterpay-nordics-for-woocommerce' ),
				'default' => 'no',
			);
			$form_fields['debug']    = array(
				'title'       => __( 'Debug Log', 'afterpay-nordics-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'afterpay-nordics-for-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf(
					__(
						'Log AfterPay events in <code>%s</code>',
						'afterpay-nordics-for-woocommerce'
					),
					wc_get_log_file_path( 'afterpay-invoice' )
				),
			);
			$this->form_fields       = $form_fields;
		}

		/**
		 * get_icon function.
		 *
		 * @return string
		 */
		public function get_icon() {
			$icon_html = '';

			$icon_html = '<img src="' . AFTERPAY_URL . '/assets/images/afterpay-logo.png" alt="AfterPay - Payments made easy" style="margin-left:10px; margin-right:10px;" width="100"/>';

			return apply_filters( 'wc_afterpay_icon_html', $icon_html );
		}

		/**
		 * Process the payment and return the result.
		 *
		 * @param  int $order_id
		 *
		 * @return array
		 * @throws Exception
		 */
		public function process_payment( $order_id ) {

			$order = wc_get_order( $order_id );

			// @Todo - check if this is needed (for Norway) since we don't do Available payment methods there
			$customer_number_norway = $this->id . '-check-customer-number-norway';

			if ( isset( $_POST[ $customer_number_norway ] ) && 'NO' == $_POST['billing_country'] ) {
				$personal_number = wc_clean( $_POST[ $customer_number_norway ] );
				$personal_number = str_replace( '-', '', $personal_number );
				WC()->session->set( 'afterpay_personal_no', $personal_number );
			}

			if ( isset( $_POST['afterpay-pre-check-customer-number'] ) && 'SE' == $_POST['billing_country'] ) {
				$personal_number = wc_clean( $_POST['afterpay-pre-check-customer-number'] );
				$personal_number = str_replace( '-', '', $personal_number );
				WC()->session->set( 'afterpay_personal_no', $personal_number );
			}
			
			// Is this a subscription payment
			/*
			if ( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) {
				// Save customer ID or personal number to order/subscription as subscription token
				$woo_customer_id = $order->get_user_id();
				if ( is_int( $woo_customer_id ) && $woo_customer_id > 0 ) {
					$subscription_token = $woo_customer_id;
				} else {
					$subscription_token = $personal_number;
				}
				update_post_meta( $order_id, 'afterpay_customer_number', $subscription_token );
				// @todo ? - adjust initial total price if there is a sign up fee?
			}
			*/
			// Fetch installment plan selected by customer in checkout
			if ( 'afterpay_account' == $this->id ) {
				$profile_no = isset( $this->account_profile_no ) ? $this->account_profile_no : '1';
			} elseif ( isset( $_POST['afterpay_installment_plan'] ) && ! empty( $_POST['afterpay_installment_plan'] ) ) {
				$profile_no = wc_clean( $_POST['afterpay_installment_plan'] );
			} else {
				$profile_no = '';
			}
			update_post_meta( $order_id, '_afterpay_installment_profile_number', $profile_no );

			// Fetch installment plan selected by customer in checkout
			if ( isset( $_POST['afterpay_customer_category'] ) ) {
				$customer_category = wc_clean( $_POST['afterpay_customer_category'] );
			} else {
				$customer_category = '';
			}
			update_post_meta( $order_id, '_afterpay_customer_category', $customer_category );
			// If needed, run PreCheckCustomer.
			/*
			if ( ! WC()->session->get( 'afterpay_checkout_id' ) ) {
				// Check available payment methods
				$request  = new WC_AfterPay_Request_Available_Payment_Methods( $this->x_auth_key, $this->testmode );
				$response = $request->response( $order_id );
				$response = json_decode( $response );

				WC_Gateway_AfterPay_Factory::log( 'WC_AfterPay_Request_Available_Payment_Methods response in process_payment(): ' . var_export( $response, true ) );

				if ( is_wp_error( $response ) ) {
					// Throw new Exception.
					wc_add_notice( $response->get_error_message(), 'error' );

					return false;
				}

				WC()->session->set( 'afterpay_allowed_payment_methods', $response->paymentMethods );
				WC()->session->set( 'afterpay_checkout_id', $response->checkoutId );
			}
			*/
			$request  = new WC_AfterPay_Request_Authorize_Payment( $this->x_auth_key, $this->testmode );
			$response = $request->response( $order_id, $this->get_formatted_payment_method_name(), $profile_no );

			// Compare the received address (from AfterPay) with the one entered by the customer in checkout.
			// Change it if they don't match
			$this->check_used_address( $response, $order_id );

			if ( ! is_wp_error( $response ) ) {
				$response = json_decode( $response );

				if ( 'Accepted' == $response->outcome ) {

					// Mark payment complete on success.
					$order->payment_complete();

					update_post_meta( $order_id, '_afterpay_reservation_id', $response->reservationId );

					// Save AfterPay customer Number
					if( $response->customer->customerNumber ) {
						update_post_meta( $order_id, 'afterpay_customer_number', $response->customer->customerNumber );
						if ( email_exists( krokedil_get_order_property( $order_id, 'billing_email' ) ) ) {
							$user    = get_user_by( 'email', krokedil_get_order_property( $order_id, 'billing_email' ) );
							$user_id = $user->ID;
							update_user_meta( $user_id, 'afterpay_customer_number', $response->customer->customerNumber );
						}
					}

					// Store reservation ID as order note.
					$order->add_order_note(
						sprintf(
							__(
								'AfterPay reservation created, reservation ID: %s.',
								'afterpay-nordics-for-woocommerce'
							),
							$response->reservationId
						)
					);
				} else {
					wc_add_notice( sprintf( __( 'The payment was %s.', 'afterpay-nordics-for-woocommerce' ), $response->outcome ), 'error' );

					return false;
				}

				// Remove cart.
				WC()->cart->empty_cart();

				// Return thank you redirect.
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				$formatted_response = json_decode( $response->get_error_message() );

				if ( is_array( $formatted_response ) ) {
					$response_message = $formatted_response[0]->message;
				} else {
					$response_message = $formatted_response->message;
				}

				wc_add_notice( sprintf( __( '%s', 'afterpay-nordics-for-woocommerce' ), $response_message ), 'error' );

				return false;
			}
		}

		/**
		 * Display payment fields for all three payment methods
		 */
		function payment_fields() {
			switch ( get_woocommerce_currency() ) {
				case 'SEK':
					$description = $this->description_se;
					break;
				case 'NOK':
					$description = $this->description_no;
					break;
				default:
					$description = '';
			}

			if ( $description ) {
				echo wpautop( wptexturize( $description ) );
			}
			echo $this->get_afterpay_dob_field();
		}

		/**
		 * Clear sessions on finalized purchase
		 */
		public function get_afterpay_dob_field() {
			$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
			$customer_type     = $afterpay_settings['customer_type'];
			if ( $customer_type === 'both' ) {
				$label = __( 'Personal/organization number', 'afterpay-nordics-for-woocommerce' );
			} else if ( $customer_type === 'private' ) {
				$label = __( 'Personal number', 'afterpay-nordics-for-woocommerce' );
			} else if ( $customer_type === 'company' ) {
				$label = __( 'Organization number', 'afterpay-nordics-for-woocommerce' );
			}
			?>
            <p class="personal-number-norway">
                <label for="<?php echo $this->id; ?>-check-customer-number-norway"><?php echo $label; ?> <span
                            class="required">*</span></label>
                <input type="text" name="<?php echo $this->id; ?>-check-customer-number-norway"
                       id="<?php echo $this->id; ?>-check-customer-number-norway"
                       class="afterpay-pre-check-customer-number norway"
                       value=""
                       placeholder="<?php _e( 'YYMMDDNNNN', 'afterpay-nordics-for-woocommerce' ); ?>"/>
            </p>
			<?php
		}

		/**
		 * Check used address
		 * Compare the address entered in the checkout with the registered address (returned from AfterPay)
		 **/
		public function check_used_address( $response, $order_id ) {
			$response          = json_decode( $response );
			$order             = wc_get_order( $order_id );
			$customer_category = get_post_meta( $order_id, '_afterpay_customer_category', true );
			$changed_fields    = array();

			$billing_first_name = sanitize_text_field( $response->customer->firstName );
			$billing_last_name  = sanitize_text_field( $response->customer->lastName );
			$billing_address    = sanitize_text_field( $response->customer->addressList[0]->street );
			$billing_postcode   = sanitize_text_field( $response->customer->addressList[0]->postalCode );
			$billing_city       = sanitize_text_field( $response->customer->addressList[0]->postalPlace );

			// Shipping address.
			if ( ! empty( $billing_address ) && mb_strtoupper( $billing_address ) != mb_strtoupper( krokedil_get_order_property( $order_id, 'billing_address_1' ) ) ) {
				$changed_fields['billing_address_1'] = $billing_address . ' (' . krokedil_get_order_property( $order_id, 'billing_address_1' ) . ')';
				update_post_meta( $order->id, '_shipping_address_1', $billing_address );
				update_post_meta( $order->id, '_billing_address_1', $billing_address );
			}
			// Post number.
			if ( ! empty( $billing_postcode ) && mb_strtoupper( $billing_postcode ) != mb_strtoupper( krokedil_get_order_property( $order_id, 'billing_postcode' ) ) ) {
				$changed_fields['billing_postcode'] = $billing_postcode . ' (' . krokedil_get_order_property( $order_id, 'billing_postcode' ) . ')';
				update_post_meta( $order->id, '_shipping_postcode', $billing_postcode );
				update_post_meta( $order->id, '_billing_postcode', $billing_postcode );
			}
			// City.
			if ( ! empty( $billing_city ) && mb_strtoupper( $billing_city ) != mb_strtoupper( krokedil_get_order_property( $order_id, 'billing_city' ) ) ) {
				$changed_fields['billing_city'] = $billing_city . ' (' . $order->get_billing_city() . ')';
				update_post_meta( $order->id, '_shipping_city', $billing_city );
				update_post_meta( $order->id, '_billing_city', $billing_city );
			}

			// Person check
			if ( 'Person' == $customer_category ) {
				// First name.
				if ( ! empty( $billing_first_name ) && mb_strtoupper( $billing_first_name ) != mb_strtoupper( krokedil_get_order_property( $order_id, 'billing_first_name' ) ) ) {
					$changed_fields['billing_first_name'] = $billing_first_name . ' (' . krokedil_get_order_property( $order_id, 'billing_first_name' ) . ')';
					update_post_meta( $order->id, '_shipping_first_name', $billing_first_name );
					update_post_meta( $order->id, '_billing_first_name', $billing_first_name );
				}
				// Last name.
				if ( ! empty( $billing_last_name ) && mb_strtoupper( $billing_last_name ) != mb_strtoupper( krokedil_get_order_property( $order_id, 'billing_last_name' ) ) ) {
					$changed_fields['billing_last_name'] = $billing_last_name . ' (' . krokedil_get_order_property( $order_id, 'billing_last_name' ) . ')';
					update_post_meta( $order->id, '_shipping_last_name', $billing_last_name );
					update_post_meta( $order->id, '_billing_last_name', $billing_last_name );
				}
			}

			// Company check
			if ( 'Company' == $customer_category ) {
				// Company name.
				if ( ! empty( $billing_last_name ) && mb_strtoupper( $billing_last_name ) != mb_strtoupper( krokedil_get_order_property( $order_id, 'billing_company' ) ) ) {
					$changed_fields['billing_company'] = $billing_last_name . ' (' . krokedil_get_order_property( $order_id, 'billing_company' ) . ')';
					update_post_meta( $order->id, '_billing_company', $billing_last_name );
					update_post_meta( $order->id, '_shipping_company', $billing_last_name );
				}
			}

			if ( count( $changed_fields ) > 0 ) {
				// Add order note about the changes.
				$order->add_order_note(
					sprintf(
						__(
							'The registered address did not match the one specified in WooCommerce. The following fields was different and updated in the order: %s.',
							'woocommerce'
						),
						json_encode( $changed_fields, JSON_PRETTY_PRINT )
					)
				);
			}
		}

		/**
		 * Clear sessions on finalized purchase
		 */
		public function clear_afterpay_sessions() {

			WC()->session->__unset( 'afterpay_checkout_id' );
			WC()->session->__unset( 'afterpay_customer_no' );
			WC()->session->__unset( 'afterpay_personal_no' );
			WC()->session->__unset( 'afterpay_allowed_payment_methods' );
			WC()->session->__unset( 'afterpay_customer_details' );
			WC()->session->__unset( 'afterpay_cart_total' );

		}

		/**
		 * Process a refund if supported.
		 *
		 * @param  int $order_id
		 * @param  float $amount
		 * @param  string $reason
		 *
		 * @return bool True or false based on success, or a WP_Error object
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = wc_get_order( $order_id );
			/*
			if ( is_wp_error( $this->can_refund_order( $order, $amount ) ) ) {
				return $this->can_refund_order( $order, $amount );
			}
			*/
			include_once( plugin_dir_path( __DIR__ ) . 'class-refund.php' );

			$wc_afterpay_refund = new WC_AfterPay_Refund( $order_id, $this->id );

			$result = $wc_afterpay_refund->refund_invoice( $order_id, $amount, $reason );

			if ( is_wp_error( $result ) ) {
				$this->log( 'Refund Failed: ' . $result->get_error_message() );

				return new WP_Error( 'error', $result->get_error_message() );
			}

			return true;
		}

		/**
		 * Can the order be refunded via AfterPay AfterPay?
		 *
		 * @param  WC_Order $order
		 *
		 * @return bool
		 */
		public function can_refund_order( $order, $amount ) {
			// Check if there's a transaction ID (invoice number).
			//if ( ! $order->get_transaction_id() ) {
			//	$this->log( 'Refund failed: No AfterPay invoice number ID.' );
			//	return new WP_Error( 'error', __( 'Refund failed: No AfterPay invoice number ID.', 'woocommerce' ) );
			//}
			// At the moment, only full refund is possible, because we can't send refunded order lines to AfterPay.
			if ( $amount !== $order->get_total() ) {
				$this->log( 'Refund failed: Only full order amount can be refunded via AfterPay.' );

				return new WP_Error(
					'error',
					__(
						'Refund failed: Only full order amount can be refunded via AfterPay.',
						'woocommerce'
					)
				);
			}

			return true;
		}

		/**
		 * Check entered personal/organisation number
		 *
		 **/
		public function process_checkout_fields() {
			if ( 'afterpay_invoice' === $_POST['payment_method'] || 'afterpay_account' === $_POST['payment_method'] || 'afterpay_part_payment' === $_POST['payment_method'] ) { // Input var okay.
				if ( ! is_numeric( $_POST['afterpay-pre-check-customer-number'] ) ) { // Input var okay.
					$format = __( 'YYMMDDNNNN', 'afterpay-nordics-for-woocommerce' );
					wc_add_notice(
						sprintf(
							__(
								'<strong>Personal/organization number</strong> needs to be numeric and in the following format: %s.',
								'afterpay-nordics-for-woocommerce'
							),
							$format
						),
						'error'
					);
				}
			}
		}


		/**
		 * Helper function for displaying the AfterPay Invoice terms
		 */
		public function get_afterpay_info() {

			switch ( get_woocommerce_currency() ) {

				case 'NOK':
					$afterpay_info = '';

					//if( 'afterpay_account' == $this->id ) {
					//$afterpay_info		= wp_remote_retrieve_body( wp_remote_get( plugins_url() . '/afterpay-nordics-for-woocommerce/templates/afterpay-account-content-' . $this->afterpay_country . '.html' ) );
					//}
					$afterpay_info  = '<p class="afterpay-credit-check-info"><small>Ved bruk av denne tjenesten gjøres en kredittsjekk. Gjenpartsbrev sendes fortrinnsvis elektronisk. Varene sendes kun till folkeregistret adresse.</small></p>';
					$short_readmore = 'Les mer her';
					$afterpay_info  .= '<a target="_blank" href="https://www.afterpay.no/nb/vilkar">' . $short_readmore . '</a>';
					break;
				case 'SEK':
					$terms_url      = 'https://www.arvato.com/content/dam/arvato/documents/norway-ecomm-terms-and-conditions/Vilk%C3%A5r%20for%20AfterPay%20Faktura.pdf';
					$terms_content  = wp_remote_retrieve_body( wp_remote_get( plugins_url() . '/afterpay-nordics-for-woocommerce/templates/afterpay-terms-' . $this->afterpay_country . '.html' ) );
					$terms_readmore = 'Läs mer om AfterPay <a href="' . $terms_url . '" target="_blank">här</a>.';
					$short_readmore = 'Läs mer här';
					$afterpay_info  = '<div id="afterpay-terms-content" style="display:none;">';
					$afterpay_info  .= $terms_content;
					$afterpay_info  .= '</div>';
					$afterpay_info  .= '<a href="#TB_inline?width=600&height=550&inlineId=afterpay-terms-content" class="thickbox">' . $short_readmore . '</a>';
					break;
				default:
					$terms_url      = 'https://www.arvato.com/content/dam/arvato/documents/norway-ecomm-terms-and-conditions/Vilk%C3%A5r%20for%20AfterPay%20Faktura.pdf';
					$terms_content  = wp_remote_retrieve_body( wp_remote_get( plugins_url() . '/afterpay-nordics-for-woocommerce/templates/afterpay-terms-' . $this->afterpay_country . '.html' ) );
					$terms_readmore = 'Läs mer om AfterPay <a href="' . $terms_url . '" target="_blank">här</a>.';
					$short_readmore = 'Läs mer här';
					$afterpay_info  = '<div id="afterpay-terms-content" style="display:none;">';
					$afterpay_info  .= $terms_content;
					$afterpay_info  .= '</div>';
					$afterpay_info  .= '<a href="#TB_inline?width=600&height=550&inlineId=afterpay-terms-content" class="thickbox">' . $short_readmore . '</a>';
					break;
			}

			add_thickbox();

			return $afterpay_info;
		}


		/**
		 * Helper function - get Invoice fee price
		 */
		public function get_invoice_fee_price() {
			$invoice_settings     = get_option( 'woocommerce_afterpay_invoice_settings' );
			$this->invoice_fee_id = $invoice_settings['invoice_fee_id'];
			if ( $this->invoice_fee_id > 0 ) {
				$product = wc_get_product( $this->invoice_fee_id );
				if ( $product ) {
					return $product->get_price();
				} else {
					return 0;
				}
			} else {
				return 0;
			}
		}

		/**
		 * get_default_description_norway function.
		 *
		 * @return string
		 */
		public function get_default_description_norway() {
			switch ( $this->id ) {
				case 'afterpay_invoice':
					$description = 'Betal varene om 14 dager.';
					break;
				case 'afterpay_account':
					$description = '<h4>Detaljer:</h4>Månedspris: <strong>Minimum 100 NOK eller 1/24 av totalbeløp</strong><br>Etableringsgebyr: <strong>0 Kr</strong><br>Rente: <strong>19.95%</strong></p><p><small>Ved et kjøp på 5000 NOK der netbetalingen foregår over 1 år, der betalingen har et fakturagebyr på 39 NOK med en rente på 19.95% vil du få en årlig sammenlignbar rente på 45,18%. Den totale kredittkjøpsprisen vil være 6084 NOK.</small></p>';
					break;
				case 'afterpay_part_payment':
					$description = 'Del opp betalingen i faste avdrag.';
					break;
				default:
					$description = '';
			}

			return $description;
		}

		/**
		 * get_default_description_sweden function.
		 *
		 * @return string
		 */
		public function get_default_description_sweden() {
			switch ( $this->id ) {
				case 'afterpay_invoice':
					$description = 'Upplev först, betala inom 14 dagar.';
					break;
				case 'afterpay_account':
					$description = '• Lägg till flera inköp i en faktura<br> • Bestäm hur mycket du vill betala varje månad<br>• Betala det totala beloppet när som helst du vill<p>Ingen uppläggningskostnad, aviseringsavgift 29 kr och månatlig ränta 1,63 %.</p>';
					break;
				case 'afterpay_part_payment':
					$description = '• Samma belopp varje månad<br> •Du kan även betala det totala beloppet när du vill';
					break;
				default:
					$description = '';
			}

			return $description;
		}

		/**
		 * Helper function - get Invoice fee price
		 */
		public function get_formatted_payment_method_name() {
			switch ( $this->id ) {
				case 'afterpay_invoice':
					$payment_method_name = 'Invoice';
					break;
				case 'afterpay_account':
					$payment_method_name = 'Account';
					break;
				case 'afterpay_part_payment':
					$payment_method_name = 'Installment';
					break;
				default:
					$payment_method_name = '';
			}

			return $payment_method_name;
		}

		public function afterpay_order_completed( $order_id ) {
			$request = new WC_AfterPay_Request_Capture_Payment;
			$request->response( $order_id );
		}

		/**
		 * Process a scheduled subscription payment.
		 *
		 * @param $amount_to_charge
		 * @param $order
		 */
		function scheduled_subscription_payment( $amount_to_charge, $order ) {
			// This function may get triggered multiple times because the class is instantiated one time per payment method (card, invoice & mobile pay). Only run it for card payments.
			// TODO: Restructure the classes so this doesn't happen.
			if( 'afterpay_invoice' != $this->id ) {
				return;
			}
			
			$result = $this->process_subscription_payment( $order, $amount_to_charge );
			if ( false == $result ) {
				// Debug
				$this->log( 'Scheduled subscription payment failed for order ID: ' . $order->get_id() );
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
			} else {
				// Debug
				if ( $this->debug == 'yes' ) {
					$this->log( 'Scheduled subscription payment succeeded for order ID: ' . $order->get_id() );
				}
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
				$order->payment_complete();
			}
		}

		/**
		 * Process subscription payment.
		 *
		 * @since  0.6.0
		 **/
		public function process_subscription_payment( $order, $amount_to_charge ) {
			if ( 0 == $amount_to_charge ) {
				return true;
			}
			$order_id = $order->get_id();
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order->get_id() );
			reset( $subscriptions );
			$subscription_id = key( $subscriptions );

			// Reccuring token
			$afterpay_customer_number = get_post_meta( $order_id, 'afterpay_customer_number', true );
			// If the recurring token isn't stored in the subscription, grab it from parent order.
			if( empty( $afterpay_customer_number ) ) {
				$afterpay_customer_number = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), 'afterpay_customer_number', true );
				$afterpay_customer_category = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_afterpay_customer_category', true );
				update_post_meta( $order_id, 'afterpay_customer_number', $afterpay_customer_number );
				update_post_meta( $order_id, '_afterpay_customer_category', $afterpay_customer_category );
				update_post_meta( $subscription_id, 'afterpay_customer_number', $afterpay_customer_number );
				update_post_meta( $subscription_id, '_afterpay_customer_category', $afterpay_customer_category );
				$this->log( 'AfterPay subscription token could not be retrieved from subscription. Grabbing it from parent order instead. Order ID: ' . $order->get_id() );
			}
			if( empty( $afterpay_customer_number ) ) {
				$order->add_order_note( __( 'AfterPay subscription token could not be retrieved.', 'woocommerce-gateway-klarna' ) );
				$this->log( 'AfterPay subscription token could not be retrieved. Order ID: ' . $order->get_id() );
				return false;
			}

			// Check order currency to be able to send correct x_auth_key
			$currency = krokedil_get_order_property( $order_id, 'order_currency' );
			switch ( $currency ) {
				case 'NOK' :
					$this->x_auth_key			= $this->x_auth_key_no;
					break;
				case 'SEK' :
					$this->x_auth_key			= $this->x_auth_key_se;
					break;
				default:
					$this->x_auth_key			= '';
			}

			$request  = new WC_AfterPay_Request_Authorize_Subscription_Payment( $this->x_auth_key, $this->testmode );
			$response = $request->response( $order_id, $this->get_formatted_payment_method_name() );

			if ( ! is_wp_error( $response ) ) {
				$response = json_decode( $response );

				if ( 'Accepted' == $response->outcome ) {

					update_post_meta( $order_id, '_afterpay_reservation_id', $response->reservationId );

					// Store reservation ID as order note.
					$order->add_order_note(
						sprintf(
							__(
								'AfterPay reservation created, reservation ID: %s.',
								'afterpay-nordics-for-woocommerce'
							),
							$response->reservationId
						)
					);
					return true;
				} else {
					$order->add_order_note( __( 'AfterPay subscription authorization error. Response message: ' . $response->outcome, 'woocommerce-gateway-klarna' ) );
					$this->log( 'AfterPay subscription authorization error. Response message: ' . $response->outcome );
					return false;
				}
			} else {
				$formatted_response = json_decode( $response->get_error_message() );

				if ( is_array( $formatted_response ) ) {
					$response_message = $formatted_response[0]->message;
				} else {
					$response_message = $formatted_response->message;
				}
				$order->add_order_note( __( 'AfterPay subscription authorization error. Response message: ' . $response_message, 'woocommerce-gateway-klarna' ) );
				$this->log( 'AfterPay subscription authorization error. Response message: ' . $response_message );
				return false;
			}
			
		}

	}
}
