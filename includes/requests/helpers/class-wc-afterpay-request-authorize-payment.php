<?php
/**
 * Available payment methods request.
 *
 * @package AfterPay for WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_AfterPay_Request_Authorize_Payment
 */
class WC_AfterPay_Request_Authorize_Payment extends WC_AfterPay_Request {
	/** @var string AfterPay API request path. */
	private $request_path = '/api/v3/checkout/authorize';
	/** @var string AfterPay API request method. */
	private $request_method = 'POST';

	/**
	 * Returns Create Cart request response.
	 *
	 * @return array|WP_Error
	 */
	public function response( $order_id, $payment_method_name, $profile_no = false ) {
		$request_url = $this->base_url . $this->request_path;
		WC_Gateway_AfterPay_Factory::log( 'Authorize payment request sent to: ' . $request_url );
		$request = wp_remote_request( $request_url, $this->get_request_args( $order_id, $payment_method_name, $profile_no ) );
		WC_Gateway_AfterPay_Factory::log( 'Authorize payment response: ' . var_export( $request, true ) );
		if ( ! is_wp_error( $request ) && 200 == $request['response']['code'] ) {
			return wp_remote_retrieve_body( $request );
		} else {
			return new WP_Error( 'error', wp_remote_retrieve_body( $request ) );
		}
	}

	/**
	 * Gets Create Cart request arguments.
	 *
	 * @return array
	 */
	private function get_request_args( $order_id, $payment_method_name, $profile_no = false ) {
		$request_args = array(
			'headers' => $this->request_header(),
			'body'    => $this->get_request_body( $order_id, $payment_method_name, $profile_no ),
			'timeout' => 15,
			'method'  => $this->request_method,
		);
		WC_Gateway_AfterPay_Factory::log( 'Authorize payment request args: ' . var_export( $request_args, true ) );

		return $request_args;
	}

	/**
	 * Gets Create Cart request body.
	 *
	 * @return false|string
	 */
	private function get_request_body( $order_id, $payment_method_name, $profile_no = false ) {
		$order = wc_get_order( $order_id );

		// Prepare order lines for AfterPay.
		$order_lines_processor = new WC_AfterPay_Process_Order_Lines();
		$order_lines           = $order_lines_processor->get_order_lines( $order_id );
		$net_total_amount      = 0;
		foreach ( $order_lines as $key => $value ) {
			$net_total_amount = $net_total_amount + ( round( $value['netUnitPrice'] * $value['quantity'], 2 ) );
		}
		$net_total_amount = round( $net_total_amount, 2 );

		$customer_category      = get_post_meta( $order_id, '_afterpay_customer_category', true );
		$formatted_request_body = array(
			'payment'  => array( 'type' => $payment_method_name ),
			'customer' => array(
				'customerCategory'     => $customer_category,
				'firstName'            => substr( krokedil_get_order_property( $order_id, 'billing_first_name' ), 0, 50 ),
				'lastName'             => substr( krokedil_get_order_property( $order_id, 'billing_last_name' ), 0, 50 ),
				'email'                => krokedil_get_order_property( $order_id, 'billing_email' ),
				'identificationNumber' => WC()->session->get( 'afterpay_personal_no' ),
				'address'              => array(
					'street'      => krokedil_get_order_property( $order_id, 'billing_address_1' ),
					'postalCode'  => krokedil_get_order_property( $order_id, 'billing_postcode' ),
					'postalPlace' => krokedil_get_order_property( $order_id, 'billing_city' ),
					'countryCode' => krokedil_get_order_property( $order_id, 'billing_country' ),
				),
			),
			'order'    => array(
				'number'           => $order->get_order_number(),
				'totalGrossAmount' => round( $order->get_total(), 2 ),
				'TotalNetAmount'   => round( $net_total_amount, 2 ),
				'currency'         => $order->get_currency(),
				'items'            => $order_lines,
			),
		);

		// Customer name or company name depending on type of customer.
		// Contact person name for B2B is sent in capture call
		if ( 'Company' == $customer_category ) {
			$formatted_request_body['customer']['firstName'] = '';
			$formatted_request_body['customer']['lastName']  = substr( krokedil_get_order_property( $order_id, 'billing_company' ), 0, 50 );
		} else {
			$formatted_request_body['customer']['firstName'] = substr( krokedil_get_order_property( $order_id, 'billing_first_name' ), 0, 50 );
			$formatted_request_body['customer']['lastName']  = substr( krokedil_get_order_property( $order_id, 'billing_last_name' ), 0, 50 );
		}

		// Send phone number if it exist. Optional for DACH.
		$phone_number = krokedil_get_order_property( $order_id, 'billing_phone' );
		if ( ! empty( $phone_number ) ) {
			$formatted_request_body['customer']['mobilePhone'] = substr( $phone_number, 0, 50 );
		}

		// Street number.
		if ( ! empty( $this->street_number_field ) ) {
			$street_number = ( ! empty( get_post_meta( $order_id, $this->street_number_field, true ) ) ) ? get_post_meta( $order_id, $this->street_number_field, true ) : get_post_meta( $order_id, '_' . $this->street_number_field, true );
			$formatted_request_body['customer']['address']['streetNumber'] = $street_number;
		}

		// Add profileNo for Account
		if ( isset( $profile_no ) && 'Account' === $payment_method_name ) {
			$formatted_request_body['payment']['account'] = array(
				'profileNo' => $profile_no,
			);
		}
		// Add profileNo for Partpayment
		if ( isset( $profile_no ) && 'Installment' === $payment_method_name ) {
			if ( $profile_no < 1 ) {
				$profile_no = 1;
			}
			$formatted_request_body['payment']['installment'] = array(
				'profileNo' => $profile_no,
			);
		}

		return wp_json_encode( apply_filters( 'afterpay_authorize_order', $formatted_request_body, $order_id ) );
	}
}
