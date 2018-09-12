<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Cancel AfterPay reservation
 *
 * Check if order was created using AfterPay and if yes, cancel AfterPay reservation when WooCommerce order is marked
 * cancelled.
 *
 * @class WC_AfterPay_Cancel_Reservation
 * @version 1.0.0
 * @package WC_Gateway_AfterPay/Classes
 * @category Class
 * @author Krokedil
 */
class WC_AfterPay_Cancel_Reservation {

	public $x_auth_key = '';

	public $testmode = '';
	/**
	 * WC_AfterPay_Cancel_Reservation constructor.
	 */
	public function __construct() {
		$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
		$this->order_management = 'yes' == $afterpay_settings['order_management'] ? true : false;
		
		
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_reservation' ) );
	}

	/**
	 * Process reservation cancellation.
	 *
	 * @param $order_id
	 */
	public function cancel_reservation( $order_id ) {
		$order = wc_get_order( $order_id );
		
		// If this order wasn't created using an AfterPay payment method, bail.
		if ( ! $this->check_if_afterpay_order( $order_id ) ) {
			return;
		}
		
		// If payment method is set to not cancel orders automatically, bail.
		if ( ! $this->order_management ) {
			return;
		}
		
		// If this reservation was already cancelled, do nothing.
		if ( get_post_meta( $order_id, '_afterpay_reservation_cancelled', true ) ) {
			$order->add_order_note(
				__( 'Could not cancel AfterPay reservation, AfterPay reservation is already cancelled.', 'afterpay-nordics-for-woocommerce' )
			);
			return;
		}

		// If this reservation was already captured, do nothing.
		if ( get_post_meta( $order_id, '_afterpay_reservation_captured', true ) ) {
			$order->add_order_note(
				__( 'Could not cancel AfterPay reservation, AfterPay reservation is already captured. Order needs to be refunded for changes to be reflected in AfterPays system.', 'afterpay-nordics-for-woocommerce' )
			);
			return;
		}
		
		$payment_method 			= krokedil_get_order_property( $order_id, 'payment_method' );
		$payment_method_settings 	= get_option( 'woocommerce_' . $payment_method . '_settings' );
		$country  					= strtolower( krokedil_get_order_property( $order_id, 'billing_country' ) );
		$this->x_auth_key 			= $payment_method_settings['x_auth_key_' . $country];
		$this->testmode 			= $payment_method_settings['testmode'];

		$order_number 				= $order->get_order_number();
		$request  					= new WC_AfterPay_Request_Cancel_Payment( $this->x_auth_key, $this->testmode );
		$response					= $request->response( $order_number );
		$response 					= json_decode( $response );
		
		if ( 0 == $response->totalCapturedAmount ) {
			// Add time stamp, used to prevent duplicate cancellations for the same order.
			update_post_meta( $order_id, '_afterpay_reservation_cancelled', current_time( 'mysql' ) );
			$order->add_order_note( __( 'AfterPay reservation was successfully cancelled.', 'afterpay-nordics-for-woocommerce' ) );
		} else {
			$order->add_order_note( __( 'AfterPay reservation could not be cancelled.', 'afterpay-nordics-for-woocommerce' ) );
		}
	}
	
	/**
	 * Check if order was created using one of AfterPay's payment options.
	 *
	 * @return boolean
	 */
	public function check_if_afterpay_order( $order_id ) {
		$order                = wc_get_order( $order_id );
		$order_payment_method = $order->payment_method;
		if ( strpos( $order_payment_method, 'afterpay' ) !== false ) {
			return true;
		}
		return false;
	}
}
$wc_afterpay_cancel_reservation = new WC_AfterPay_Cancel_Reservation;
