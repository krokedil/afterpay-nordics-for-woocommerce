<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Runs PreCheckCustomer for AfterPay payment methods
 *
 * @class    WC_AfterPay_Pre_Check_Customer
 * @version  1.0.0
 * @package  WC_Gateway_AfterPay/Classes
 * @category Class
 * @author   Krokedil
 */
class WC_AfterPay_Pre_Check_Customer {

	/** @var bool */
	private $testmode = false;

	/**
	 * WC_AfterPay_Pre_Check_Customer constructor.
	 */
	public function __construct() {
		$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
		$this->testmode    = 'yes' == $afterpay_settings['testmode'] ? true : false;

		$invoice_settings                 = get_option( 'woocommerce_afterpay_invoice_settings' );
		$part_payment_settings            = get_option( 'woocommerce_afterpay_part_payment_settings' );
		$account_settings                 = get_option( 'woocommerce_afterpay_account_settings' );
		$this->enabled_invoice            = $invoice_settings['enabled'];
		$this->display_get_address_no     = ( isset( $invoice_settings['display_get_address_no'] ) ) ? $invoice_settings['display_get_address_no'] : 'yes';
		$this->always_display_get_address = ( isset( $invoice_settings['always_display_get_address'] ) ) ? $invoice_settings['always_display_get_address'] : 'yes';
		$this->enabled_part_payment       = $part_payment_settings['enabled'];
		$this->enabled_account            = $account_settings['enabled'];
		$this->street_number_field        = ( isset( $invoice_settings['street_number_field'] ) ) ? $invoice_settings['street_number_field'] : '';

		// Enqueue JS file
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Register AJAX callback
		add_action( 'wp_ajax_afterpay_customer_lookup', array( $this, 'customer_lookup' ) );
		add_action( 'wp_ajax_nopriv_afterpay_customer_lookup', array( $this, 'customer_lookup' ) );

		add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'display_pre_check_form' ) );

		// Check if PreCheckCustomer was performed and successful
		add_action( 'woocommerce_before_checkout_process', array( $this, 'confirm_pre_check_customer' ) );

	}


	/**
	 * Check if customer has used PreCheckCustomer and received a positive response (if AfterPay method is selected)
	 */
	public function confirm_pre_check_customer() {
		$chosen_payment_method = WC()->session->chosen_payment_method;
		// Check Personal/organization number.
		if ( strpos( $chosen_payment_method, 'afterpay' ) !== false && 'DE' !== $_POST['billing_country'] ) {
			// Check if personal/organization number field is empty
			if ( empty( $_POST['afterpay-pre-check-customer-number'] ) && empty( $_POST['afterpay_invoice-check-customer-number-norway'] ) && empty( $_POST['afterpay_part_payment-check-customer-number-norway'] ) && empty( $_POST['afterpay_account-check-customer-number-norway'] ) ) {
				wc_add_notice( __( 'Personal/organization number is a required field.', 'afterpay-nordics-for-woocommerce' ), 'error' );
			}
		}
	}

	/**
	 * Display AfterPay PreCheckCustomer fields
	 */
	public function display_pre_check_form() {
		if ( 'yes' == $this->enabled_invoice || 'yes' == $this->enabled_part_payment || 'yes' == $this->enabled_account ) {
			$personal_number = WC()->session->get( 'afterpay_personal_no' ) ? WC()->session->get( 'afterpay_personal_no' ) : '';

			// Check settings for what customer type is wanted, and print the form according to that.
			$afterpay_settings           = get_option( 'woocommerce_afterpay_invoice_settings' );
			$customer_type               = $afterpay_settings['customer_type'];
			$separate_shipping_companies = isset( $afterpay_settings['separate_shipping_companies'] ) ? $afterpay_settings['separate_shipping_companies'] : 'no';
			?>
			<div id="afterpay-pre-check-customer" style="display:none">
				<?php
				if ( $customer_type === 'both' ) {

					echo $this->get_radiobutton_customer_type_both( $separate_shipping_companies );
					$label = __( 'Personal/organization number', 'afterpay-nordics-for-woocommerce' );

				} elseif ( $customer_type === 'private' ) {

					echo $this->get_radiobutton_customer_type_private();
					$label = __( 'Personal number', 'afterpay-nordics-for-woocommerce' );

				} elseif ( $customer_type === 'company' ) {

					echo $this->get_radiobutton_customer_type_company( $separate_shipping_companies );
					$label = __( 'Organization number', 'afterpay-nordics-for-woocommerce' );

				}

				$this->get_fields_no();

				$this->get_fields_se( $label, $personal_number );

				?>
			</div>
			<?php
		}
	}

	/**
	 * Get html for displaying customer category
	 * - Both person and company.
	 */
	public function get_radiobutton_customer_type_both( $separate_shipping_companies ) {
		?>
		<p>
			<input type="radio" class="input-radio" value="Person" name="afterpay_customer_category"
				   id="afterpay-customer-category-person" checked/>
			<label for="afterpay-customer-category-person"><?php _e( 'Person', 'afterpay-nordics-for-woocommerce' ); ?></label>
			<input type="hidden" id="separate_shipping_companies" value="<?php echo $separate_shipping_companies; ?>">
			<input type="radio" class="input-radio" value="Company" name="afterpay_customer_category"
				   id="afterpay-customer-category-company"/>
			<label
					for="afterpay-customer-category-company"><?php _e( 'Company', 'afterpay-nordics-for-woocommerce' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Get html for displaying customer category
	 * - Person/private.
	 */
	public function get_radiobutton_customer_type_private() {
		?>
		<p>
			<input type="radio" value="Person" name="afterpay_customer_category"
				   id="afterpay-customer-category-person" checked style="display:none;"/>
		</p>
		<style> #billing_company_field {
				display: none;
			} </style>
		<?php
	}

	/**
	 * Get html for displaying customer category
	 * - Company.
	 */
	public function get_radiobutton_customer_type_company( $separate_shipping_companies ) {
		?>
		<p>
			<input type="radio" value="Company" name="afterpay_customer_category"
				   id="afterpay-customer-category-company" checked style="display:none;"/>
			<input type="hidden" id="separate_shipping_companies" value="<?php echo $separate_shipping_companies; ?>">
		</p>
		<?php
	}

	/**
	 * Get html for displaying mobile number field
	 */
	public function get_fields_no() {
		?>
		<div class="afterpay-pre-check-no">
			<p class="form-row form-row-first validate-required">
				<label for="afterpay-pre-check-mobile-number"><?php _e( 'Mobile phone number', 'afterpay-nordics-for-woocommerce' ); ?></label>
				<input type="text" name="afterpay-pre-check-mobile-number" id="afterpay-pre-check-mobile-number"
					   class="afterpay-pre-check-mobile-number"/>
			</p>
			<p class="form-row form-row-last afterpay-get-address-button-row">
				<label for="afterpay-customer-lookup-button button"> &nbsp;</label>
				<button type="button"
						class="afterpay-customer-lookup-button button"><?php _e( 'Get address', 'afterpay-nordics-for-woocommerce' ); ?></button>
			</p>


		</div>
		<?php
	}

	/**
	 * Get html for displaying personal number field
	 */
	public function get_fields_se( $label, $personal_number ) {
		?>
		<div class="afterpay-pre-check-se">
			<p class="form-row form-row-first afterpay-dob-field">
				<label for="afterpay-pre-check-customer-number"><?php echo $label; ?> <span
							class="required">*</span></label>
				<input type="text" name="afterpay-pre-check-customer-number" id="afterpay-pre-check-customer-number"
					   class="afterpay-pre-check-customer-number"
					   placeholder="<?php _e( 'YYMMDDNNNN', 'afterpay-nordics-for-woocommerce' ); ?>"
					   value="<?php echo $personal_number; ?>"/>
			</p>
			<p class="form-row form-row-last afterpay-get-address-button-row">
				<label for="afterpay-customer-lookup-button button"> &nbsp;</label>
				<button type="button"
						class="afterpay-customer-lookup-button button"><?php _e( 'Get address', 'afterpay-nordics-for-woocommerce' ); ?></button>
			</p>
			<p class="form-row form-row-wide afterpay-privacy-info">
				<a href="https://documents.myafterpay.com/privacy-statement/sv_se/" target="_blank"><?php _e( 'This is how your data is used.', 'afterpay-nordics-for-woocommerce' ); ?></a>
			</p>
		</div>
		<?php
	}


	/**
	 * Load the JS & CSS file(s).
	 */
	public function enqueue_scripts() {

		if ( is_checkout() && ( 'yes' == $this->enabled_invoice || 'yes' == $this->enabled_part_payment || 'yes' == $this->enabled_account ) ) {
			if ( WC()->session->get( 'afterpay_personal_no' ) ) {
				$se_address_fetched = 'yes';
			} else {
				$se_address_fetched = 'no';
			}
			wp_register_script( 'afterpay_pre_check_customer', plugins_url( 'assets/js/pre-check-customer.js', __DIR__ ), array( 'jquery' ), AFTERPAY_VERSION, true );
			wp_localize_script(
				'afterpay_pre_check_customer',
				'WC_AfterPay',
				array(
					'ajaxurl'                           => admin_url( 'admin-ajax.php' ),
					'se_address_fetched'                => $se_address_fetched,
					'afterpay_pre_check_customer_nonce' => wp_create_nonce( 'afterpay_pre_check_customer_nonce' ),
					'street_number_field'               => $this->street_number_field,
					'display_get_address_no'            => $this->display_get_address_no,
					'always_display_get_address'        => $this->always_display_get_address,
				)
			);
			wp_enqueue_script( 'afterpay_pre_check_customer' );

			wp_register_style( 'afterpay_pre_check_customer', plugins_url( 'assets/css/afterpay-pre-check-customer.css', __DIR__ ), array(), AFTERPAY_VERSION );
			wp_enqueue_style( 'afterpay_pre_check_customer' );
		}
	}


	/**
	 * Check billing fields against shipping fields for differences
	 *
	 * @param $order
	 *
	 * @return bool
	 */
	public function check_against_fields( $order ) {
		$return = false;
		if ( $order->shipping_address_1 != '' || $order->shipping_postcode != '' || $order->shipping_city != '' || $order->shipping_first_name != '' || $order->shipping_last_name != '' ) {
			if ( $order->billing_address_1 != $order->shipping_address_1 ) {
				$return = true;
			}
			if ( $order->billing_postcode != $order->shipping_postcode ) {
				$return = true;
			}
			if ( $order->billing_city != $order->shipping_city ) {
				$return = true;
			}
			if ( $order->billing_first_name != $order->shipping_first_name ) {
				$return = true;
			}
			if ( $order->billing_last_name != $order->shipping_last_name ) {
				$return = true;
			}

			return $return;
		} else {
			return $return;
		}
	}

	/**
	 * AfterPay PreCheckCustomer request
	 *
	 * @param $personal_number
	 * @param $payment_method
	 * @param string          $customer_category
	 *
	 * @return bool
	 */
	public function pre_check_customer_request( $personal_number, $email, $payment_method, $customer_category, $billing_country, $order = false ) {
		$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
		switch ( get_woocommerce_currency() ) {
			case 'SEK':
				$this->x_auth_key = $afterpay_settings['x_auth_key_se'];
				break;
			case 'NOK':
				$this->x_auth_key = $afterpay_settings['x_auth_key_no'];
				break;
			case 'EUR':
				$this->x_auth_key = $afterpay_settings['x_auth_key_de'];
				break;
			default:
				$this->x_auth_key = $afterpay_settings['x_auth_key_se'];
				break;
		}

		$request  = new WC_AfterPay_Request_Available_Payment_Methods( $this->x_auth_key, $this->testmode );
		$response = $request->response( $personal_number, $email, $customer_category );
		$response = json_decode( $response );

		if ( ! is_wp_error( $response ) ) {
			if ( $response->customer->firstName ) {
				// Customer information
				$afterpay_customer_details = array(
					'first_name' => $response->customer->firstName,
					'last_name'  => $response->customer->lastName,
					'address_1'  => $response->customer->addressList[0]->street,
					'postcode'   => $response->customer->addressList[0]->postalCode,
					'city'       => $response->customer->addressList[0]->postalPlace,
					'country'    => $response->customer->addressList[0]->countryCode,
				);

				WC()->session->set( 'afterpay_customer_no', $response->customer->customerNumber );
				WC()->session->set( 'afterpay_personal_no', $personal_number );
				WC()->session->set( 'afterpay_customer_details', $afterpay_customer_details );
				WC()->session->set( 'afterpay_cart_total', WC()->cart->total );

				// Set session data
				WC()->session->set( 'afterpay_allowed_payment_methods', $response->paymentMethods );
				WC()->session->set( 'afterpay_checkout_id', $response->checkoutId );

				// Send success
				return $afterpay_customer_details;

			} else {
				// We didn't get a customer address in response
				if ( $response->message ) {
					$error_meassage = $response->message;
				} else {
					$error_meassage = __( 'No address was found. Please check your personal number or choose another payment method.', 'afterpay-nordics-for-woocommerce' );
				}

				return new WP_Error( 'failure', sprintf( __( '%s', 'afterpay-nordics-for-woocommerce' ), $error_meassage ) );
			}
		} else {
			WC()->session->__unset( 'afterpay_checkout_id' );
			WC()->session->__unset( 'afterpay_customer_no' );
			WC()->session->__unset( 'afterpay_personal_no' );
			WC()->session->__unset( 'afterpay_allowed_payment_methods' );
			WC()->session->__unset( 'afterpay_customer_details' );
			WC()->session->__unset( 'afterpay_cart_total' );

			return new WP_Error( 'failure', __( 'Fel', 'afterpay-nordics-for-woocommerce' ) );
		}

	}


	/**
	 * Filter checkout fields so they use data retrieved by PreCheckCustomer
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	public function filter_pre_checked_value( $value ) {
		// Only do this for AfterPay methods
		$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );
		if ( strpos( $chosen_payment_method, 'afterpay' ) !== false ) {
			$current_filter   = current_filter();
			$current_field    = str_replace(
				array(
					'woocommerce_process_checkout_field_billing_',
					'woocommerce_process_checkout_field_shipping_',
				),
				'',
				$current_filter
			);
			$customer_details = WC()->session->get( 'afterpay_customer_details' );
			if ( isset( $customer_details[ $current_field ] ) && '' != $customer_details[ $current_field ] ) {
				return $customer_details[ $current_field ];
			} else {
				return $value;
			}
		}

		return $value;
	}


	/**
	 * AJAX CustomerLookup for AfterPay payment methods.
	 */
	public function customer_lookup() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'afterpay_pre_check_customer_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$data              = array();
		$mobile_number     = '';
		$personal_number   = '';
		$payment_method    = $_REQUEST['payment_method'];
		$customer_category = $_REQUEST['customer_category'];
		$billing_country   = $_REQUEST['billing_country'];

		if ( 'NO' == $billing_country ) {
			$mobile_number = $_REQUEST['mobile_number'];
		}
		if ( 'SE' == $billing_country ) {
			$personal_number = $_REQUEST['personal_number'];
			WC()->session->set( 'afterpay_personal_no', $personal_number );
		}

		if ( $customer_category != 'Company' ) {
			$customer_category = 'Person';
		}
		$customer_lookup_response = $this->customer_lookup_request( $mobile_number, $personal_number, $payment_method, $customer_category, $billing_country );
		$data['response']         = $customer_lookup_response;

		if ( ! is_wp_error( $customer_lookup_response ) ) {
			$data['message'] = __(
				'Address found and added to checkout form.',
				'afterpay-nordics-for-woocommerce'
			);
			wp_send_json_success( $data );
		} else {
			$data['message'] = $customer_lookup_response->get_error_message();
			wp_send_json_error( $data );
		}
		wp_die();
	}

	/**
	 * AfterPay PreCheckCustomer request
	 *
	 * @param $personal_number
	 * @param $payment_method
	 * @param string          $customer_category
	 *
	 * @return bool
	 */
	public function customer_lookup_request( $mobile_number, $personal_number, $payment_method, $customer_category, $billing_country, $order = false ) {
		$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
		switch ( get_woocommerce_currency() ) {
			case 'SEK':
				$this->x_auth_key = $afterpay_settings['x_auth_key_se'];
				break;
			case 'NOK':
				$this->x_auth_key = $afterpay_settings['x_auth_key_no'];
				break;
			case 'EUR':
				$this->x_auth_key = $afterpay_settings['x_auth_key_de'];
				break;
			default:
				$this->x_auth_key = $afterpay_settings['x_auth_key_se'];
				break;
		}

		$personal_number = str_replace( '-', '', $personal_number );
		$request         = new WC_AfterPay_Request_Customer_Lookup( $this->x_auth_key, $this->testmode );
		$response        = $request->response( $mobile_number, $personal_number, $billing_country, $customer_category );
		$response        = json_decode( $response );

		if ( ! is_wp_error( $response ) ) {
			if ( $response->userProfiles[0]->firstName || $response->userProfiles[0]->eMail ) {
				// Customer information
				$afterpay_customer_details = array(
					'first_name' => $response->userProfiles[0]->firstName,
					'last_name'  => $response->userProfiles[0]->lastName,
					'address_1'  => $response->userProfiles[0]->addressList[0]->street,
					'postcode'   => $response->userProfiles[0]->addressList[0]->postalCode,
					'city'       => $response->userProfiles[0]->addressList[0]->city,
					'country'    => $response->userProfiles[0]->addressList[0]->countryCode,
				);
				if ( $response->userProfiles[0]->eMail ) {
					$afterpay_customer_details['email'] = $response->userProfiles[0]->eMail;
				}
				WC()->session->set( 'afterpay_customer_details', $afterpay_customer_details );
				WC()->session->set( 'afterpay_cart_total', WC()->cart->total );

				// Send success
				return $afterpay_customer_details;

			} else {
				// We didn't get a customer address in response
				if ( 'NO' == $billing_country ) {
					$identifier = 'mobile phone number';
				} else {
					$identifier = 'personal/organization number';
				}
				// And because AfterPay returns the response in different formats depending on the error
				if ( is_array( $response ) ) {
					$response_message = $response[0]->message;
				} else {
					$response_message = $response->message;
				}

				$error_meassage = sprintf( __( 'No address was found (%1$s). Please check your %2$s or choose another payment method.', 'afterpay-nordics-for-woocommerce' ), $response_message, $identifier );

				return new WP_Error( 'failure', sprintf( __( '%s', 'afterpay-nordics-for-woocommerce' ), $error_meassage ) );
			}
		} else {
			WC()->session->__unset( 'afterpay_checkout_id' );
			WC()->session->__unset( 'afterpay_customer_no' );
			WC()->session->__unset( 'afterpay_personal_no' );
			WC()->session->__unset( 'afterpay_allowed_payment_methods' );
			WC()->session->__unset( 'afterpay_customer_details' );
			WC()->session->__unset( 'afterpay_cart_total' );

			return new WP_Error( 'failure', __( 'Fel', 'afterpay-nordics-for-woocommerce' ) );
		}

	}
}

$wc_afterpay_pre_check_customer = new WC_AfterPay_Pre_Check_Customer();
