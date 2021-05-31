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
 * Class WC_AfterPay_Request_Authorize_Subscription_Payment
 */
class WC_AfterPay_Request_Authorize_Subscription_Payment extends WC_AfterPay_Request {
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
		WC_Gateway_AfterPay_Factory::log( 'Authorize subscription payment request sent to: ' . $request_url );
		$request = wp_remote_request( $request_url, $this->get_request_args( $order_id, $payment_method_name, $profile_no ) );
		WC_Gateway_AfterPay_Factory::log( 'Authorize subscription payment response: ' . stripslashes_deep( json_encode( $request ) ) );
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
		WC_Gateway_AfterPay_Factory::log( 'Authorize subscription payment request args: ' . stripslashes_deep( json_encode( $request_args ) ) );

		return $request_args;
	}

	/**
	 * Gets Create Cart request body.
	 *
	 * @return false|string
	 */
	private function get_request_body( $order_id, $payment_method_name, $profile_no = false ) {
		$order = wc_get_order( $order_id );

		// Prepare order lines for AfterPay
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
				'customerCategory' => $customer_category,
				'firstName'        => substr( krokedil_get_order_property( $order_id, 'billing_first_name' ), 0, 50 ),
				'lastName'         => substr( krokedil_get_order_property( $order_id, 'billing_last_name' ), 0, 50 ),
				'email'            => krokedil_get_order_property( $order_id, 'billing_email' ),
				'mobilePhone'      => krokedil_get_order_property( $order_id, 'billing_phone' ),
				'address'          => array(
					'street'      => krokedil_get_order_property( $order_id, 'billing_address_1' ),
					'postalCode'  => krokedil_get_order_property( $order_id, 'billing_postcode' ),
					'postalPlace' => krokedil_get_order_property( $order_id, 'billing_city' ),
					'countryCode' => krokedil_get_order_property( $order_id, 'billing_country' ),
				),
				'customerNumber'   => get_post_meta( $order_id, 'afterpay_customer_number', true ),
			),
			'order'    => array(
				'number'           => $order->get_order_number(),
				'totalGrossAmount' => $order->get_total(),
				'TotalNetAmount'   => $net_total_amount,
				'currency'         => $order->get_currency(),
				'items'            => $order_lines,
			),
		);
		return wp_json_encode( apply_filters( 'afterpay_authorize_subscription_renewal_order', $formatted_request_body, $order_id ) );
	}
}
