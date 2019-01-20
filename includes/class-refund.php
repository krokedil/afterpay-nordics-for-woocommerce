<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Refund AfterPay invoice
 *
 * Check if refund is possible, then process it. Currently only supports RefundFull.
 *
 * @class WC_AfterPay_Refund
 * @version 1.0.0
 * @package WC_Gateway_AfterPay/Classes
 * @category Class
 * @author Krokedil
 */
class WC_AfterPay_Refund {

	/** @var int */
	private $order_id = '';

	/** @var bool */
	private $testmode = false;

	/** @var string */
	private $x_auth_key = '';

	/**
	 * WC_AfterPay_Refund constructor.
	 */
	public function __construct() {
		$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
		$this->testmode = 'yes' == $afterpay_settings['testmode'] ? true : false;
	}

	/**
	 * Process refund.
	 *
	 * @param $order_id
	 * @return boolean
	 */
	public function refund_invoice( $order_id, $amount = null, $reason = '' ) {
		$order 						= wc_get_order( $order_id );
		$payment_method 			= $order->payment_method;
		$payment_method_settings 	= get_option( 'woocommerce_' . $payment_method . '_settings' );
		$country  					= strtolower( $order->get_billing_country() );
		$this->x_auth_key 			= $payment_method_settings['x_auth_key_' . $country];

		$order_number 				= $order->get_order_number();
		$request      				= new WC_AfterPay_Request_Refund_Payment( $this->x_auth_key, $this->testmode );
		$response 					= $request->response( $order_number );
		$response 					= json_decode( $response );
		
		if ( $response->totalCapturedAmount ) {
			// Add time stamp, used to prevent duplicate cancellations for the same order.
			update_post_meta( $this->order_id, '_afterpay_invoice_refunded', current_time( 'mysql' ) );
			$order->add_order_note( __( 'AfterPay refund was successfully processed.', 'afterpay-nordics-for-woocommerce' ) );
			return $response;
		} else {
			$order->add_order_note( __( 'AfterPay refund could not be processed.', 'afterpay-nordics-for-woocommerce' ) );
			return new WP_Error( 'afterpay-refund', __( 'Refund failed.', 'afterpay-nordics-for-woocommerce' ) );
		}
			
	}

}
$wc_afterpay_refund = new WC_AfterPay_Refund;
