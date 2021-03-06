<?php
/**
 * Cancel payment request.
 *
 * @package AfterPay for WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class WC_AfterPay_Request_Cancel_Payment
 */
class WC_AfterPay_Request_Cancel_Payment extends WC_AfterPay_Request {
	/** @var string AfterPay API request path. */
	private $request_path   = '';
	/** @var string AfterPay API request method. */
	private $request_method = 'POST';
	/**
	 * Returns Cancel Payment response.
	 *
	 * @return array|WP_Error
	 */
	public function response( $order_number ) {

		$this->request_path = '/api/v3/orders/' . $order_number . '/voids';
		
		$request_url = $this->base_url . $this->request_path;
		WC_Gateway_AfterPay_Factory::log( 'Cancel payment request sent to: ' . $request_url );
		$request     = wp_remote_request( $request_url, $this->get_request_args( ) );
		WC_Gateway_AfterPay_Factory::log( 'Cancel payment response: ' . var_export( $request, true ) );
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
	private function get_request_args() {
		$request_args = array(
			'headers' => $this->request_header(),
			'method'  => $this->request_method,
		);
		return $request_args;
	}
}
