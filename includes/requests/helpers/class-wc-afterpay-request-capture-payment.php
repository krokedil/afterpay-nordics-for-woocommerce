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
 * Class WC_AfterPay_Request_Capture_Payment
 */
class WC_AfterPay_Request_Capture_Payment extends WC_AfterPay_Request {
	/** @var string AfterPay API request method. */
	private $request_method = 'POST';

	public function response( $order_id ) {
		$wc_order = wc_get_order( $order_id );
		$order_number = $wc_order->get_order_number();
		$request_url = $this->base_url . '/api/v3/orders/' . $order_number . '/captures';
		WC_Gateway_AfterPay_Factory::log( 'Capture payment request sent to: ' . $request_url );
		$request     = wp_remote_retrieve_body( wp_remote_request( $request_url, $this->get_request_args( $order_id ) ) );
		WC_Gateway_AfterPay_Factory::log( 'Capture payment response: ' . var_export( $request, true ) );
		return $request;
	}
	private function get_request_args( $order_id ) {
		$request_args = array(
			'headers' => $this->request_header(),
			'body'    => $this->get_request_body( $order_id ),
			'method'  => $this->request_method,
		);
		WC_Gateway_AfterPay_Factory::log( 'Capture payment request args: ' . var_export( $request_args, true ) );
		return $request_args;
	}

	private function get_request_body( $order_id ) {
		$order = wc_get_order( $order_id );
		$request_body = array(
			'orderDetails' => array(
				'totalGrossAmount' => $order->get_total(),
				'currency' => krokedil_get_order_property( $order_id, 'order_currency' ),
			),
		);
		return wp_json_encode( $request_body );
	}
}
