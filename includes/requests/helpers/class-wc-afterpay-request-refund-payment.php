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
		$request_args       = $this->get_request_args( $order_id, $amount, $reason );
		$request_url        = $this->base_url . $this->request_path;
		WC_Gateway_AfterPay_Factory::log( 'Refund payment request sent to: ' . $request_url );
		WC_Gateway_AfterPay_Factory::log( 'Refund payment request args: ' . wp_json_encode( $request_args ) );
		$request = wp_remote_request( $request_url, $request_args );
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
		$order                   = wc_get_order( $order_id );
		'' === $reason ? $reason = __( 'No reason given', 'afterpay-nordics-for-woocommerce' ) : $reason;

		$request_body = array(
			'orderItems'    => self::get_items( $order_id, $amount, $reason ),
			'captureNumber' => $order->get_transaction_id(),
			'refundType'    => 'Refund',
		);

		return wp_json_encode( $request_body );
	}


	/**
	 * Gets items.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public static function get_items( $order_id, $amount, $reason ) {

		$refund_id = self::get_refunded_order_id( $order_id );
		if ( '' === $reason ) {
			$reason = '';
		} else {
			$reason = ' (' . $reason . ')';
		}

		$order       = wc_get_order( $order_id );
		$line_number = 0;
		$items       = array();

		if ( null !== $refund_id ) {
			$refund_order   = wc_get_order( $refund_id );
			$refunded_items = $refund_order->get_items();

			if ( $refunded_items || $refund_order->get_shipping_total() < 0 || ! empty( $refund_order->get_fees() ) ) {
				// Cart.
				foreach ( $refunded_items as $item ) {
					$formated_item = self::get_item( $item );
					array_push( $items, $formated_item );
				}
				// Shipping.
				if ( $refund_order->get_shipping_total() < 0 ) {
					$formated_shipping = self::get_shipping( $refund_order );
					array_push( $items, $formated_shipping );
				}
				// Fees.
				WC_Gateway_AfterPay_Factory::log( '$refund_order->get_fees(): ' . var_export( $refund_order->get_fees(), true ) );
				foreach ( $refund_order->get_fees() as $fee ) {
					$formated_fee = self::get_fee( $fee );
					array_push( $items, $formated_fee );
				}
			} else {
				$formated_item = array(
					'productId'      => 'ref1',
					'description'    => 'Refund #' . $refund_id . $reason,
					'grossUnitPrice' => round( $amount ),
					'vatRate'        => 0,
					'quantity'       => 1,
				);
				array_push( $items, $formated_item );
			}
			update_post_meta( $refund_id, '_krokedil_refunded', 'true' );
		} else {
			// Log empty response?
		}

		return $items;
	}

	/**
	 * Gets single item.
	 *
	 * @param array $item
	 * @return array
	 */
	private static function get_item( $item ) {
		$product = $item->get_product();

		if ( $item['variation_id'] ) {
			$product_id = $item['variation_id'];
		} else {
			$product_id = $item['product_id'];
		}
		return array(
			'productId'      => self::get_sku( $product, $product_id ),
			'description'    => $product->get_name(),
			'grossUnitPrice' => round( ( $item->get_total() + $item->get_total_tax() ) / $item['qty'], 2 ),
			'netUnitPrice'   => round( ( $item->get_total() ) / $item['qty'], 2 ),
			'vatAmount'      => round( ( $item->get_total_tax() ) / $item['qty'], 2 ),
			'vatPercent'     => self::product_vat_rate( $item ),
			'quantity'       => abs( $item['qty'] ),
		);
	}

	/**
	 * Gets shipping
	 *
	 * @param string $shipping_method
	 * @param int    $line_number
	 * @return array
	 */
	private static function get_shipping( $refund_order ) {

		return array(
			'productId'      => 'shipping',
			'description'    => $refund_order->get_shipping_method(),
			'grossUnitPrice' => abs( round( $refund_order->get_shipping_total() + $refund_order->get_shipping_tax(), 2 ) ),
			'netUnitPrice'   => abs( round( $refund_order->get_shipping_total(), 2 ) ),
			'vatAmount'      => abs( round( $refund_order->get_shipping_tax(), 2 ) ),
			'vatPercent'     => self::get_shipping_vat_rate( $refund_order ),
			'quantity'       => 1,
		);
	}

	/**
	 * Gets order Fee.
	 *
	 * @param array $fee
	 * @param int   $line_number
	 * @return array
	 */
	private static function get_fee( $fee ) {
		if ( $fee->get_total_tax() ) {
			$fee_tax_rate = abs( round( $fee->get_total_tax() / $fee->get_total(), 2, PHP_ROUND_HALF_UP ) );
			$fee_vat_code = $fee_tax_rate;
		} else {
			$fee_vat_code = 0;
		}

		return array(
			'description'    => $fee->get_name(),
			'productId'      => $fee->get_id(),
			'grossUnitPrice' => abs( round( $fee->get_total() + $fee->get_total_tax(), 2, PHP_ROUND_HALF_UP ) ),
			'netUnitPrice'   => abs( round( $fee->get_total(), 2, PHP_ROUND_HALF_UP ) ),
			'vatAmount'      => abs( round( $fee->get_total_tax(), 2, PHP_ROUND_HALF_UP ) ),
			'vatPercent'     => $fee_vat_code,
			'quantity'       => 1,
		);
	}


	/**
	 * Gets SKU
	 *
	 * @param array $product
	 * @param int   $product_id
	 * @return string
	 */
	private static function get_sku( $product, $product_id ) {
		if ( get_post_meta( $product_id, '_sku', true ) !== '' ) {
			$part_number = $product->get_sku();
		} else {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
	}

	/**
	 * @param $cart_item
	 *
	 * @TODO: Add tax rates for other countries once they are available.
	 * @return string|WP_Error
	 */
	private static function product_vat_rate( $item ) {
		if ( $item['line_subtotal_tax'] ) {
			$tax_rate = round( abs( $item['line_subtotal_tax'] ) / abs( $item['line_subtotal'] ) * 100 );
			return $tax_rate;
		} else {
			return 0;
		}
	}

	/**
	 * @param $refund_order
	 *
	 * @TODO: Add tax rates for other countries once they are available.
	 * @return string|WP_Error
	 */
	private static function get_shipping_vat_rate( $refund_order ) {
		if ( $refund_order->get_shipping_tax() ) {
			$tax_rate = round( abs( $refund_order->get_shipping_tax() ) / abs( $refund_order->get_shipping_total() ) * 100 );
			return $tax_rate;
		} else {
			return 0;
		}
	}

	/**
	 * Gets refunded order
	 *
	 * @param int $order_id
	 * @return string
	 */
	public static function get_refunded_order_id( $order_id ) {
		$query_args = array(
			'fields'         => 'id=>parent',
			'post_type'      => 'shop_order_refund',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		);
		$refunds    = get_posts( $query_args );
		$refund_id  = array_search( $order_id, $refunds );
		if ( is_array( $refund_id ) ) {
			foreach ( $refund_id as $key => $value ) {
				if ( ! get_post_meta( $value, '_krokedil_refunded' ) ) {
					$refund_id = $value;
					break;
				}
			}
		}
		return $refund_id;
	}
}
