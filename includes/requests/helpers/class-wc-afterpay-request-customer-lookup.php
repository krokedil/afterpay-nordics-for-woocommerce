<?php
/**
 * Customer lookup request.
 *
 * @package AfterPay for WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class WC_AfterPay_Request_Customer_Lookup
 */
class WC_AfterPay_Request_Customer_Lookup extends WC_AfterPay_Request {
	/** @var string AfterPay API request path. */
	public $request_path   = '/api/v3/lookup/customer';
	/** @var string AfterPay API request method. */
	public $request_method = 'POST';
	/**
	 * Returns Customer Lookup request response.
	 *
	 * @return array|WP_Error
	 */
	public function response( $mobile_number, $personal_number, $billing_country, $customer_category ) {
		$request_url = $this->base_url . $this->request_path;
		$request     = wp_remote_retrieve_body( wp_remote_request( $request_url, $this->get_request_args( $mobile_number, $personal_number, $billing_country, $customer_category ) ) );
		WC_Gateway_AfterPay_Factory::log( 'WC_AfterPay_Request_Customer_Lookup response: ' . var_export( $request, true) );
		return $request;
	}
	/**
	 * Gets Create Cart request arguments.
	 *
	 * @return array
	 */
	private function get_request_args( $mobile_number, $personal_number, $billing_country, $customer_category ) {
		$request_args = array(
			'headers' => $this->request_header(),
			'body'    => $this->get_request_body( $mobile_number, $personal_number, $billing_country, $customer_category ),
			'method'  => $this->request_method,
		);
		WC_Gateway_AfterPay_Factory::log( 'WC_AfterPay_Request_Customer_Lookup request args: ' . var_export( $request_args, true ) );
		return $request_args;
	}
	/**
	 * Gets Create Cart request body.
	 *
	 * @return false|string
	 */
	private function get_request_body( $mobile_number, $personal_number, $billing_country, $customer_category ) {
		$formatted_request_body = array(
			'countryCode'  		=> $billing_country,
		);
		if( 'NO' == $billing_country ) {
			$formatted_request_body['mobilePhone'] = $mobile_number;
		} else {
			$formatted_request_body['identificationNumber'] = $personal_number;
		}
		return wp_json_encode( $formatted_request_body );
	}
}