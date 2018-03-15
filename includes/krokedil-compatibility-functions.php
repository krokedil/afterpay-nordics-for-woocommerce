<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Provides compatibility functions for WC2.6 and before
 */

/**
 * Gets order property.
 *
 * @param  $order_id int Order ID
 * @return string|bool
 */
function krokedil_get_order_property( $order_id, $property ) {
	$order = wc_get_order( $order_id );

	if ( is_callable( array( $order, 'get_' . $property ) ) ) {
		$value = $order->{'get_' . $property}();
	} else {
		$value = get_post_meta( $order_id, '_' . $property, true );
	}

	return '' !== $value ? $value : false;
}

/**
 * Sets an order property.
 *
 * @param $order_id
 * @param $property
 */
function krokedil_set_order_property( $order_id, $property ) {
	$order = wc_get_order( $order_id );

	if ( is_callable( array( $order, 'set_' . $property ) ) ) {
		$order->{'set_' . $property}();
	} else {
		update_post_meta( $order_id, $property, true );
	}

	if ( is_callable( $order, 'save' ) ) {
		$order->save();
	}
}