<?php
/**
 * Template Name: Booking
 * Template Post Type: page
 *
 * Dedicated booking page.
 *
 * The booking form is intentionally hard-coded and always appears first. Editors
 * may add only the restricted ACF content assigned to this template beneath it.
 *
 * @package PrelaunchWP
 */

get_header();

$booking_advisor = function_exists( 'prelaunch_get_booking_advisor' )
	? prelaunch_get_booking_advisor()
	: null;

get_template_part(
	'template-parts/booking/form',
	null,
	[
		'advisor' => $booking_advisor,
	]
);

/**
 * Render the restricted post-form booking content using the same partials and
 * alternating-background behavior as the normal page builder.
 */
if ( have_rows( 'booking_body_sections' ) ) :
	$background_index = 0;

	$textured_image     = function_exists( 'get_field' ) ? get_field( 'textured_image', 'option' ) : '';
	$textured_image_url = '';

	if ( is_array( $textured_image ) && ! empty( $textured_image['url'] ) ) {
		$textured_image_url = $textured_image['url'];
	} elseif ( is_string( $textured_image ) ) {
		$textured_image_url = $textured_image;
	}

	echo '<div class="alt-bg-wrap booking-page__content">';

	while ( have_rows( 'booking_body_sections' ) ) :
		the_row();

		$layout = get_row_layout();

		$allowed_layouts = [
			'text_block'       => 'flex/blocks/_text',
			'image_text_block' => 'flex/blocks/_image-text',
		];

		if ( empty( $allowed_layouts[ $layout ] ) ) {
			continue;
		}

		$path = $allowed_layouts[ $layout ];

		if ( ! locate_template( $path . '.php', false, false ) ) {
			error_log( 'Missing booking content block template: ' . $layout . ' → ' . $path . '.php' );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				echo '<div style="padding:1rem;border:2px dashed red;margin:1rem 0;">';
				echo '<strong>Missing booking block template:</strong> ' . esc_html( $layout );
				echo '</div>';
			}

			continue;
		}

		ob_start();
		get_template_part( $path );
		$markup = trim( (string) ob_get_clean() );

		if ( '' === $markup ) {
			continue;
		}

		$background_index++;
		$is_even_background = 0 === $background_index % 2;

		$background_class = $is_even_background
			? 'bg-alternating-gradient bg-alternating-even'
			: 'bg-alternating-gradient bg-alternating-odd';

		$background_style = '';

		if ( ! $is_even_background && $textured_image_url ) {
			$background_class .= ' bg-texture';
			$background_style = ' style="--bg-texture: url(\'' . esc_url( $textured_image_url ) . '\');"';
		}

		echo '<div class="' . esc_attr( $background_class ) . '" data-layout="' . esc_attr( $layout ) . '"' . $background_style . '>';
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered theme partial markup.
		echo '</div>';
	endwhile;

	echo '</div>';
endif;

get_footer();
