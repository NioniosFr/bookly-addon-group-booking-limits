<?php
/*
Plugin Name: Bookly Group Booking Limits (Add-on)
Plugin URI: https://www.github.com/nioniosfr/bookly-addon-group-booking-limits
Description: Bookly Group Booking Limits add-on allows you to map services to groups and limit customer appointment bookings based on those mappings.
Version: 0.0.1
Author: Dionysios Fryganas <dfryganas@gmail.com>
Author URI: https://www.github.com/nioniosfr
Text Domain: bagbl
Domain Path: /languages
License: MIT
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Display a warning in admin sections when the plugin cannot be used.
 */
function dfr_bagbl_admin_notice() {
	echo '<div class="error"><h3>Bookly Group Booking Limits (Add-on)</h3><p>To install this plugin - <strong>Bookly Group Booking (Add-on)</strong> plugin is required.</p></div>';
}

/**
 * Initialization logic of this plugin.
 */
function dfr_bagbl_init() {
	if ( ! is_plugin_active( 'bookly-addon-group-booking/main.php' ) ) {

		add_action( is_network_admin() ? 'network_admin_notices' : 'admin_notices', 'dfr_bagbl_admin_notice' );
		return;
	}
	add_filter( 'bookly_appointments_limit', 'dfr_bagbl_group_limit', 1, 3 );
}

add_action( 'init', 'dfr_bagbl_init' );

/**
 * Filter the current limit based on the users group.
 *
 * In bookly-responsive-appointment-booking-tool/lib/entities/Service.php, inside appointmentsLimitReached method, line ~375
 * Add the following filter:
 *
 * $limit = apply_filters( 'bookly_appointments_limit', $this->getAppointmentsLimit(), $service_id, $customer_id, $appointment_dates );
 * if ( $db_count + $cart_count > $limit ) {
 *   return true;
 * }
 *
 * @param int $default_limit The service limit.
 *
 * @param int $service_id The service being checked for limits.
 *
 * @param int $customer_id The bookly customer.
 *
 * @return int
 */
function dfr_bagbl_group_limit( $default_limit, $service_id, $customer_id ) {
	$customer = new \Bookly\Lib\Entities\Customer();
	$customer->load( $customer_id );

	if ( null === $customer || ! $customer->isLoaded() ) {
		return $default_limit;
	}

	if ( null === $customer->getGroupId() ) {
		return $default_limit;
	}

	$customer_group = new \BooklyCustomerGroups\Lib\Entities\CustomerGroups();
	$customer_group->load( $customer->getGroupId() );

	$description = $customer_group->getDescription();

	if ( null === $description || '' === $description ) {
		return $default_limit;
	}

	$limits_meta = json_decode( $description, true );

	if ( null === $limits_meta || empty( $limits_meta ) ) {
		return $default_limit;
	}

	foreach ( $limits_meta as $limitation ) {
		if ( $limitation['serviceId'] == $service_id ) {
			return $limitation['limit'];
		}
	}

	return $default_limit;

}
