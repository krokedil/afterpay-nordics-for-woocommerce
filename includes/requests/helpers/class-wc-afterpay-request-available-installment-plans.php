<?php
/**
 * Available installment plans request.
 *
 * @package AfterPay for WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class WC_AfterPay_Request_Available_Installment_Plans
 */
class WC_AfterPay_Request_Available_Installment_Plans extends WC_AfterPay_Request {
	/** @var string AfterPay API request path. */
	public $request_path   = '/api/v3/lookup/installment-plans';
	/** @var string AfterPay API request method. */
	public $request_method = 'POST';
	/**
	 * Returns Create Cart request response.
	 *
	 * @return array|WP_Error
	 */
	public function response( $amount, $currency, $country ) {
		$request_url = $this->base_url . $this->request_path;
		WC_Gateway_AfterPay_Factory::log( 'Available Installment plans request sent to: ' . $request_url );
		$request     = wp_remote_retrieve_body( wp_remote_request( $request_url, $this->get_request_args( $amount, $currency, $country ) ) );
		WC_Gateway_AfterPay_Factory::log( 'Available Installment plans response: ' . var_export( $request, true ) );
		return $request;
	}
	/**
	 * Gets Create Cart request arguments.
	 *
	 * @return array
	 */
	private function get_request_args( $amount, $currency, $country ) {
		$request_args = array(
			'headers' => $this->request_header(),
			'body'    => $this->get_request_body( $amount, $currency, $country ),
			'method'  => $this->request_method,
		);
		WC_Gateway_AfterPay_Factory::log( 'Available Installment plans request args: ' . var_export( $request_args, true ) );
		return $request_args;
	}
	/**
	 * Gets Create Cart request body.
	 *
	 * @return false|string
	 */
	private function get_request_body( $amount, $currency, $country ) {
		$formatted_request_body = array(
			'amount'		=> $amount,
			'currency'  	=> $currency,
			'countryCode'  	=> $country,
		);
		return wp_json_encode( $formatted_request_body );
	}
}