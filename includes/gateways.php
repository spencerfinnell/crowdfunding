<?php
/**
 * Custom gateway functionality.
 *
 * @since Appthemer CrowdFunding 1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * If there is any custom gateway functionality included,
 * and the gateway is active, load the extra files.
 *
 * @since Appthemer Crowdfunding 1.1
 *
 * @return void
 */
function atcf_load_gateway_support() {
	if ( ! class_exists( 'Easy_Digital_Downloads' ) )
		return;

	$crowdfunding    = crowdfunding();
	$active_gateways = edd_get_enabled_payment_gateways();

	foreach ( $active_gateways as $gateway => $gateway_args ) {
		if ( @file_exists( $crowdfunding->includes_dir . 'gateways/' . $gateway . '.php' ) ) {
			require( $crowdfunding->includes_dir . 'gateways/' . $gateway . '.php' );
		}
	}
}
add_action( 'init', 'atcf_load_gateway_support', 200 );

function atcf_collect_funds_remove_failed( $gateways, $campaign ) {
	$failed_payments = get_post_meta( $campaign->ID, '_campaign_failed_payments', true );

	foreach ( $gateways as $gateway ) {
		foreach ( $gateway[ 'payments' ] as $payment ) {
			if ( ($key = array_search( $payment, $failed_payments ) ) !== false ) {
				unset( $failed_payments );
			}
		}
	}

	update_post_meta( $campaign->ID, '_campaign_failed_payments', $failed_payments );
}
add_action( 'atcf_collect_funds', 'atcf_collect_funds_remove_failed', 10, 2 );

/**
 * Determine if any of the currently active gateways have preapproval
 * functionality. There really isn't a standard way of doing this, so
 * they are manually defined in an array right now.
 * 
 * @since Appthemer Crowdfunding 1.1
 *
 * @return boolean $has_support If any of the currently active gateways support preapproval
 */
function atcf_has_preapproval_gateway() {
	global $edd_options;

	$has_support = false;
	$supports_preapproval = apply_filters( 'atcf_gateways_support_preapproval', array(
		'stripe',
		'paypal_adaptive_payments'
	) );

	$active_gateways = edd_get_enabled_payment_gateways();

	foreach ( $active_gateways as $gateway => $gateway_args ) {
		switch ( $gateway ) {
			case 'stripe' :

				if ( $edd_options[ 'stripe_preapprove_only' ] )
					$has_support = true;

				break;

			case 'paypal_adaptive_payments' : 

				if ( $edd_options[ 'epap_preapproval' ] )
					$has_support = true;

				break;

		}
	}

	return apply_filters( 'atcf_has_preapproval_gateway', $has_support, $active_gateways );
}