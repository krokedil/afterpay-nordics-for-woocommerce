<?php
/**
 * Refund payment request.
 *
 * @package AfterPay for WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_AfterPay_Request_Refund_Payment
 */
class WC_AfterPay_Request_Refund_Payment extends WC_AfterPay_Request {
	/** @var string AfterPay API request path. */
	private $request_path = '';
	/** @var string AfterPay API request method. */
	private $request_method = 'POST';

	/**
	 * Returns Cancel Payment response.
	 *
	 * @return array|WP_Error
	 */
	public function response( $order_id, $amount, $reason ) {
		$order              = wc_get_order( $order_id );
		$order_number       = $order->get_order_number();
		$this->request_path = '/api/v3/orders/' . $order_number . '/refunds';

		$request_url = $this->base_url . $this->request_path;
		WC_Gateway_AfterPay_Factory::log( 'Refund payment request sent to: ' . $request_url );
		$request = wp_remote_request( $request_url, $this->get_request_args( $order_id, $amount, $reason ) );
		WC_Gateway_AfterPay_Factory::log( 'Refund payment response: ' . var_export( $request, true ) );

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
	private function get_request_args( $order_id, $amount, $reason ) {
		$request_args = array(
			'headers' => $this->request_header(),
			'body'    => $this->get_request_body( $order_id, $amount, $reason ),
			'method'  => $this->request_method,
		);
		WC_Gateway_AfterPay_Factory::log( 'Refund payment request args: ' . var_export( $request_args, true ) );

		return $request_args;
	}

	private function get_request_body( $order_id, $amount, $reason ) {
		$order = wc_get_order( $order_id );

		// Only refund if the order contains one tax rate. 
		// @todo - improve this.
		if ( 1 == count( $order->get_taxes() ) ) {
			$tax_rate                 = (int) ( $order->get_total_tax() / ( $order->get_total() - $order->get_total_tax() ) * 100 );
			$tax_rate_for_calculation = 1 . '.' . $tax_rate;

			$request_body = array(
				'orderItems'    => array(
					'description'    => $reason,
					'grossUnitPrice' => round( $amount, 2 ),
					'NetUnitPrice'   => round( $amount / $tax_rate_for_calculation, 2 ),
					'vatPercent'     => $tax_rate,
					'quantity'       => 1,
					'productId'      => 'test'
				),
				'captureNumber' => $order->get_transaction_id(),
				'refundType'    => 'Refund'
			);

			return wp_json_encode( $request_body );
		} else {
			$order->add_order_note( __( 'Order contains multiple tax rates. This is not supported in the AfterPay plugin when making a refund in AfterPays system from WooCommerce. Aborting refund.', 'afterpay-nordics-for-woocommerce' ) );

			return new WP_Error( 'error', __( 'Order contains multiple tax rates. This is not supported in the AfterPay plugin. Aborting refund.', 'afterpay-nordics-for-woocommerce' ) );
		}
	}
}
