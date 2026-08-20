<?php
/**
 * Template Name: Booking Confirmation
 * Template Post Type: page
 *
 * Dedicated confirmation experience for Gravity Form 4 booking inquiries.
 *
 * @package PrelaunchWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$advisor = function_exists( 'prelaunch_get_booking_confirmation_advisor' )
	? prelaunch_get_booking_confirmation_advisor()
	: null;

$vacation_type = function_exists( 'prelaunch_get_booking_confirmation_vacation_type' )
	? prelaunch_get_booking_confirmation_vacation_type()
	: null;

$post_context = function_exists( 'prelaunch_get_booking_confirmation_posts' )
	? prelaunch_get_booking_confirmation_posts( $vacation_type, 3 )
	: null;

get_template_part(
	'template-parts/booking/confirmation',
	null,
	[
		'advisor'       => $advisor,
		'vacation_type' => $vacation_type,
		'post_context'  => $post_context,
	]
);

get_footer();
