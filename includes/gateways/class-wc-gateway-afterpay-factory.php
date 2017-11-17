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

			if( WC()->customer ) {
				// Only activate the payment gateway if the customers country is the same as the shop country ($this->afterpay_country)
				if ( WC()->customer->get_billing_country() == true && WC()->customer->get_billing_country() != $this->afterpay_country ) {
					return false;
				}

				// Check if payment method is configured
				$payment_method 			= $this->id;
				$country 					= strtolower(WC()->customer->get_billing_country());
				$payment_method_settings 	= get_option( 'woocommerce_' . $payment_method . '_settings' );
				// Don't display part payment and Account for Norwegian customers
				if ( WC()->customer->get_billing_country() == true && 'NO' == WC()->customer->get_billing_country() && ( 'afterpay_part_payment' == $this->id || 'afterpay_account' == $this->id ) ) {
					return false;
				}
			}

			// Check if PreCheckCustomer allows this payment method
			if ( WC()->session->get( 'afterpay_allowed_payment_methods' ) ) {
				switch ( $payment_method ) {
					case 'afterpay_invoice':
						$payment_method_name = 'Invoice';
						break;
					case 'afterpay_account':
						$payment_method_name = 'Account';
						break;
					case 'afterpay_part_payment':
						$payment_method_name = 'Installment';
						//$payment_method_name = 'Account';
						break;
					default:
						$payment_method_name = '';
				}
				$success = false;
				// Check PreCheckCustomer response for available payment methods
				foreach( WC()->session->get( 'afterpay_allowed_payment_methods' ) as $payment_option ) {
					if ( $payment_option->type == $payment_method_name ) {
						$success = true;
					}
				}

				if ( $success ) {
					return true;
				} else {
					return false;
				}
			}
			return true;
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'afterpay-nordics-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable ' . $this->method_title, 'afterpay-nordics-for-woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title'       => __( 'Title', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'afterpay-nordics-for-woocommerce' ),
					'default'     => __( $this->method_title, 'afterpay-nordics-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'textarea',
					'desc_tip'    => true,
					'description' => __( 'This controls the description which the user sees during checkout.', 'afterpay-nordics-for-woocommerce' ),
				),
				'x_auth_key_se' => array(
					'title'       => __( 'AfterPay X-Auth-Key Sweden', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'text',
					'description' => __(
						'Please enter your AfterPay X-Auth-Key for Sweden; this is needed in order to take payment',
						'afterpay-nordics-for-woocommerce'
					),
				),
				'x_auth_key_no' => array(
					'title'       => __( 'AfterPay X-Auth-Key Norway', 'afterpay-nordics-for-woocommerce' ),
					'type'        => 'text',
					'description' => __(
						'Please enter your AfterPay X-Auth-Key for Norway; this is needed in order to take payment',
						'afterpay-nordics-for-woocommerce'
					),
				),
				
				
			);
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
				$form_fields['separate_shipping_companies'] = array(
					'title'   => __( 'Separate shipping address', 'afterpay-nordics-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable separate shipping address for companies', 'afterpay-nordics-for-woocommerce' ),
					'default' => 'no',
				);
				
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
			$form_fields['debug'] = array(
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
			$this->form_fields = $form_fields;
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
			// @Todo - check if this is needed (for Norway) since we don't do Available payment methods there
			if ( isset( $_POST['afterpay-pre-check-customer-number-norway'] ) && 'NO' == $_POST['billing_country'] ) {
				$personal_number = wc_clean( $_POST['afterpay-pre-check-customer-number-norway'] );
				WC()->session->set( 'afterpay_personal_no', $personal_number );
			}
			if ( isset( $_POST['afterpay-pre-check-customer-number'] ) && 'SE' == $_POST['billing_country'] ) {
				$personal_number = wc_clean( $_POST['afterpay-pre-check-customer-number'] );
				WC()->session->set( 'afterpay_personal_no', $personal_number );
			}
			
			
			// Fetch installment plan selected by custiner in checkout
			if ( isset( $_POST['afterpay_installment_plan'] ) ) {
				$profile_no = wc_clean( $_POST['afterpay_installment_plan'] );
			} else {
				$profile_no = '';
			}
			update_post_meta( $order_id, '_afterpay_installment_profile_number', $profile_no );
			
			// Fetch installment plan selected by custiner in checkout
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
				
			$order = wc_get_order( $order_id );
			
			if ( ! is_wp_error( $response ) ) {
				$response  = json_decode( $response );

				if( 'Accepted' == $response->outcome ) {
					
					// Mark payment complete on success.
					$order->payment_complete();

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
				} else {
					wc_add_notice( sprintf(__( 'The payment was %s.', 'afterpay-nordics-for-woocommerce' ), $response->outcome ), 'error' );
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
				
				if( is_array( $formatted_response ) ) {
				    $response_message = $formatted_response[0]->message;
			    } else {
				    $response_message = $formatted_response->message;
			    }
			    
				wc_add_notice( sprintf(__( '%s', 'afterpay-nordics-for-woocommerce' ), $response_message ), 'error' );
				return false;
			}
		}

		/**
		 * Display payment fields for all three payment methods
		 */
		function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->description ) );
			}
			echo $this->get_afterpay_dob_field();
		}

		/**
		 * Clear sessions on finalized purchase
		 */
		public function get_afterpay_dob_field() {
			$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
			$customer_type = $afterpay_settings['customer_type'];
			if ( $customer_type === 'both' ) {
        		$label = __( 'Personal/organization number', 'afterpay-nordics-for-woocommerce' );
        	} else if ( $customer_type === 'private' ) {
                $label = __( 'Personal number', 'afterpay-nordics-for-woocommerce' );
            } else if ( $customer_type === 'company' ) {
            	$label = __( 'Organization number', 'afterpay-nordics-for-woocommerce' );
            }
            ?>
            <p class="personal-number-norway">
				<label for="afterpay-pre-check-customer-number-norway"><?php echo $label; ?> <span class="required">*</span></label>
		            <input type="text" name="afterpay-pre-check-customer-number-norway" id="afterpay-pre-check-customer-number-norway"
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
			$response  			= json_decode( $response );
			$order 				= wc_get_order( $order_id );
			$customer_category 	= get_post_meta( $order_id, '_afterpay_customer_category', true );
			$changed_fields 	= array();
		
			// Shipping address.
			if ( mb_strtoupper( $response->customer->addressList[0]->street ) != mb_strtoupper( $order->get_billing_address_1() ) ) {
				$changed_fields['billing_address_1'] = $response->customer->addressList[0]->street;
				//update_post_meta( $order->id, '_shipping_address_1', $response['address_1'] );
				//update_post_meta( $order->id, '_billing_address_1', $response['address_1'] );
			}
			// Post number.
			if ( mb_strtoupper( $response->customer->addressList[0]->postalCode ) != mb_strtoupper( $order->get_billing_postcode() ) ) {
				$changed_fields['billing_postcode'] = $response->customer->addressList[0]->postalCode;
				//update_post_meta( $order->id, '_shipping_postcode', $response['postcode'] );
				//update_post_meta( $order->id, '_billing_postcode', $response['postcode'] );
			}
			// City.
			if ( mb_strtoupper( $response->customer->addressList[0]->postalPlace ) != mb_strtoupper( $order->get_billing_city() ) ) {
				$changed_fields['billing_city'] = $response->customer->addressList[0]->postalPlace;
				//update_post_meta( $order->id, '_shipping_city', $response['city'] );
				//update_post_meta( $order->id, '_billing_city', $response['city'] );
			}
			
			// Person check
			if( 'Person' == $customer_category ) {
				// First name.
				if ( mb_strtoupper( $response->customer->firstName ) != mb_strtoupper( $order->get_billing_first_name() ) ) {
					$changed_fields['billing_first_name'] = $response->customer->firstName;
					//update_post_meta( $order->id, '_shipping_first_name', $response['first_name'] );
					//update_post_meta( $order->id, '_billing_first_name', $response['first_name'] );
				}
				// Last name.
				if ( mb_strtoupper( $response->customer->lastName ) != mb_strtoupper( $order->get_billing_last_name() ) ) {
					$changed_fields['billing_last_name'] = $response->customer->lastName;
					//update_post_meta( $order->id, '_shipping_last_name', $response['last_name'] );
					//update_post_meta( $order->id, '_billing_last_name', $response['last_name'] );
				}
			}
			
			// Company check
			if( 'Company' == $customer_category ) {
				// Company name.
				if ( mb_strtoupper( $response->customer->lastName ) != mb_strtoupper( $order->get_billing_company() ) ) {
					$changed_fields['billing_company'] = $response->customer->lastName;
					//update_post_meta( $order->id, '_shipping_last_name', $response['last_name'] );
					//update_post_meta( $order->id, '_billing_last_name', $response['last_name'] );
				}
			}
			
			if ( count( $changed_fields ) > 0 ) {
				// Add order note about the changes.
				$order->add_order_note(
					sprintf(
						__(
							'The registered address did not match the one specified in WooCommerce. The following fields was different: %s.',
							'woocommerce'
						),
						var_export( $changed_fields, true )
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
		 * @param  int    $order_id
		 * @param  float  $amount
		 * @param  string $reason
		 * @return bool True or false based on success, or a WP_Error object
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = wc_get_order( $order_id );

			if ( is_wp_error( $this->can_refund_order( $order, $amount ) ) ) {
				return $this->can_refund_order( $order, $amount );
			}

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
		 * @param  WC_Order $order
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
					$short_readmore 		= 'Les mer her';
					$afterpay_info ='<a target="_blank" href="https://www.afterpay.no/nb/vilkar">' . $short_readmore . '</a>';
					break;
				case 'SEK':
					$terms_url   			= 'https://www.arvato.com/content/dam/arvato/documents/norway-ecomm-terms-and-conditions/Vilk%C3%A5r%20for%20AfterPay%20Faktura.pdf';
					$terms_content			= wp_remote_retrieve_body( wp_remote_get( plugins_url() . '/afterpay-nordics-for-woocommerce/templates/afterpay-terms-' . $this->afterpay_country . '.html' ) );
					$terms_readmore 		= 'Läs mer om AfterPay <a href="' . $terms_url . '" target="_blank">här</a>.';
					$short_readmore 		= 'Läs mer här';
					$afterpay_info 			= '<div id="afterpay-terms-content" style="display:none;">';
					$afterpay_info 			.= $terms_content;
					$afterpay_info 			.='</div>';
					$afterpay_info 			.='<a href="#TB_inline?width=600&height=550&inlineId=afterpay-terms-content" class="thickbox">' . $short_readmore . '</a>';
					break;
				default:
					$terms_url   			= 'https://www.arvato.com/content/dam/arvato/documents/norway-ecomm-terms-and-conditions/Vilk%C3%A5r%20for%20AfterPay%20Faktura.pdf';
					$terms_content			= wp_remote_retrieve_body( wp_remote_get( plugins_url() . '/afterpay-nordics-for-woocommerce/templates/afterpay-terms-' . $this->afterpay_country . '.html' ) );
					$terms_readmore 		= 'Läs mer om AfterPay <a href="' . $terms_url . '" target="_blank">här</a>.';
					$short_readmore 		= 'Läs mer här';
					$afterpay_info 			= '<div id="afterpay-terms-content" style="display:none;">';
					$afterpay_info 			.= $terms_content;
					$afterpay_info 			.='</div>';
					$afterpay_info 			.='<a href="#TB_inline?width=600&height=550&inlineId=afterpay-terms-content" class="thickbox">' . $short_readmore . '</a>';
					break;
			}

			add_thickbox();

			return $afterpay_info;
		}


		/**
		 * Helper function - get Invoice fee price
		 */
		public function get_invoice_fee_price() {
			$invoice_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
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
			$request  = new WC_AfterPay_Request_Capture_Payment;
			$request->response( $order_id );
		}
	}
}
