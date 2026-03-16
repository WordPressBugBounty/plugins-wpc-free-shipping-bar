<?php
defined( 'ABSPATH' ) || exit;

register_activation_hook( defined( 'WPCFB_LITE' ) ? WPCFB_LITE : WPCFB_FILE, 'wpcfb_activate' );
register_deactivation_hook( defined( 'WPCFB_LITE' ) ? WPCFB_LITE : WPCFB_FILE, 'wpcfb_deactivate' );
add_action( 'admin_init', 'wpcfb_check_version' );

function wpcfb_check_version() {
	if ( ! empty( get_option( 'wpcfb_version' ) ) && ( get_option( 'wpcfb_version' ) < WPCFB_VERSION ) ) {
		wpc_log( 'wpcfb', 'upgraded' );
		update_option( 'wpcfb_version', WPCFB_VERSION, false );
	}
}

function wpcfb_activate() {
	wpc_log( 'wpcfb', 'installed' );
	update_option( 'wpcfb_version', WPCFB_VERSION, false );
}

function wpcfb_deactivate() {
	wpc_log( 'wpcfb', 'deactivated' );
}

if ( ! function_exists( 'wpc_log' ) ) {
	function wpc_log( $prefix, $action ) {
		$logs = get_option( 'wpc_logs', [] );
		$user = wp_get_current_user();

		if ( ! isset( $logs[ $prefix ] ) ) {
			$logs[ $prefix ] = [];
		}

		$logs[ $prefix ][] = [
			'time'   => current_time( 'mysql' ),
			'user'   => $user->display_name . ' (ID: ' . $user->ID . ')',
			'action' => $action
		];

		update_option( 'wpc_logs', $logs, false );
	}
}