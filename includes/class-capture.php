<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Capture AfterPay reservation
 *
 * Check if order was created using AfterPay and if yes, capture AfterPay reservation when WooCommerce order is marked
 * completed.
 *
 * @class WC_AfterPay_Capture
 * @version 1.0.0
 * @package WC_Gateway_AfterPay/Classes
 * @category Class
 * @author Krokedil
 */
class WC_AfterPay_Capture {

	/**
	 * WC_AfterPay_Cancel_Reservation constructor.
	 */
	public function __construct() {
		$afterpay_settings 			= get_option( 'woocommerce_afterpay_invoice_settings' );
		$this->testmode 			= $afterpay_settings['testmode'];
		$this->order_management 	= 'yes' == $afterpay_settings['order_management'] ? true : false;
		$this->log_enabled 			= 'yes' == $afterpay_settings['debug'] ? true : false;

		add_action( 'woocommerce_order_status_completed', array( $this, 'capture_full' ) );
	}
	
	/**
	 * Process reservation capture.
	 *
	 * @param $order_id
	 */
	 
	public function capture_full( $order_id ) {
		$order = wc_get_order( $order_id );
		// If this order wasn't created using an AfterPay payment method, bail.
		if ( ! $this->check_if_afterpay_order( $order_id ) ) {
			return;
		}
		
		// If payment method is set to not capture orders automatically, bail.
		if ( ! $this->order_management ) {
			return;
		}
		
		// If this reservation was already captured, do nothing.
		if ( get_post_meta( $order_id, '_afterpay_reservation_captured', true ) ) {
			$order->add_order_note(
				__( 'Could not capture AfterPay reservation, AfterPay reservation is already captured.', 'afterpay-nordics-for-woocommerce' )
			);
			return;
		}
		
		$country  = strtolower( krokedil_get_order_property( $order_id, 'billing_country' ) );
		$afterpay_settings = get_option( 'woocommerce_afterpay_invoice_settings' );
		$this->x_auth_key = $afterpay_settings['x_auth_key_' . $country];
		
		$request  = new WC_AfterPay_Request_Capture_Payment( $this->x_auth_key, $this->testmode );
		$response = $request->response( $order_id );
		$response = json_decode( $response );
		if ( $response->captureNumber ) {
			// Add time stamp, used to prevent duplicate cancellations for the same order.
			update_post_meta( $order_id, '_afterpay_reservation_captured', current_time( 'mysql' ) );
			update_post_meta( $order_id, '_transaction_id', $response->captureNumber );
				
			$order->add_order_note( sprintf( __( 'Payment captured with AfterPay with capture number %s', 'afterpay-nordics-for-woocommerce' ), $response->captureNumber ) );
		} else {
			if( is_array( $response ) ) {
				$response_message = $response[0]->message;
			} else {
				$response_message = json_encode( $response );
			}
			$order->add_order_note( sprintf( __( 'Payment failed to be captured by AfterPay. Error message: %s', 'afterpay-nordics-for-woocommerce' ), $response_message ) );
			$order->update_status( apply_filters( 'afterpay_failed_capture_status', 'processing', $order_id ) );
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
$wc_afterpay_capture = new WC_AfterPay_Capture;
