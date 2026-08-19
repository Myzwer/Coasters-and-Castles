<?php
	/**
	 * Booking form display.
	 *
	 * @var array{advisor?: WP_Post|null} $args Template-part arguments.
	 *
	 * @package PrelaunchWP
	 */

	$advisor = $args['advisor'] ?? null;

	$is_advisor_booking = $advisor instanceof WP_Post;

	$advisor_name  = '';
	$advisor_title = '';
	$advisor_image = 0;

	/*
	 * Generic booking rail copy.
	 *
	 * Editors may change the messaging without gaining access to the
	 * booking form itself or any advisor-routing behavior.
	 */
	$generic_brand = function_exists( 'get_field' )
		? trim( (string) get_field( 'booking_generic_brand' ) )
		: '';

	$generic_title = function_exists( 'get_field' )
		? trim( (string) get_field( 'booking_generic_title' ) )
		: '';

	$generic_copy = function_exists( 'get_field' )
		? (string) get_field( 'booking_generic_copy' )
		: '';

	/*
	 * Defensive fallbacks preserve the intended booking experience
	 * if the ACF fields are empty or have not yet been imported.
	 */
	if ( '' === $generic_brand ) {
		$generic_brand = __( 'Coasters & Castles Travel', 'prelaunch-wp' );
	}

	if ( '' === $generic_title ) {
		$generic_title = __( 'Your best trip starts here.', 'prelaunch-wp' );
	}

	if ( '' === trim( wp_strip_all_tags( $generic_copy ) ) ) {
		$generic_copy = '<p>'
						. esc_html__(
							'Tell us what you’ve been dreaming about. Let us take it from there.',
							'prelaunch-wp'
						)
						. '</p>';
	}

	if ( $is_advisor_booking ) {
		$advisor_name = trim(
			wp_strip_all_tags( get_the_title( $advisor ) )
		);

		$advisor_title = function_exists( 'get_field' )
			? trim( (string) get_field( 'advisor_title', $advisor->ID ) )
			: '';

		if ( '' === $advisor_title ) {
			$advisor_title = __( 'Travel Advisor', 'prelaunch-wp' );
		}

		if ( function_exists( 'get_field' ) ) {
			$advisor_image = absint(
				get_field( 'advisor_headshot', $advisor->ID )
			);

			if ( 0 === $advisor_image ) {
				$advisor_image = absint(
					get_field( 'advisor_professional_headshot', $advisor->ID )
				);
			}
		}
	}
?>

<section class="form-block booking-form<?php echo $is_advisor_booking ? ' booking-form--advisor' : ''; ?>">
	<div class="form-block__layout">

		<div class="form-block__main">
			<div class="form-block__form-wrap">

				<?php if ( function_exists( 'gravity_form' ) ) : ?>

					<?php
					gravity_form(
						4,
						false,
						false,
						false,
						null,
						false
					);
					?>

				<?php elseif ( current_user_can( 'manage_options' ) ) : ?>

					<p>
						<?php esc_html_e( 'Gravity Forms is not available.', 'prelaunch-wp' ); ?>
					</p>

				<?php endif; ?>

			</div>
		</div>

		<aside
			class="form-block__rail"
			aria-label="<?php esc_attr_e( 'Booking information', 'prelaunch-wp' ); ?>"
		>
			<div class="form-block__rail-inner">

				<?php if ( $is_advisor_booking ) : ?>

					<p class="form-block__brand booking-form__eyebrow">
						<?php esc_html_e( 'You’re planning with', 'prelaunch-wp' ); ?>
					</p>

					<?php if ( $advisor_image ) : ?>

						<div class="booking-form__advisor-photo">
							<?php
								echo wp_get_attachment_image(
									$advisor_image,
									'medium_large',
									false,
									[
										'class' => 'booking-form__advisor-image',
										'alt'   => $advisor_name,
									]
								);
							?>
						</div>

					<?php endif; ?>

					<h1 class="form-block__title booking-form__advisor-name">
						<?php echo esc_html( $advisor_name ); ?>
					</h1>

					<p class="booking-form__advisor-title">
						<?php echo esc_html( $advisor_title ); ?>
					</p>

					<div class="form-block__copy booking-form__advisor-copy">
						<p>
							<?php
								esc_html_e(
									'Tell me a little about the trip you’re dreaming about. By submitting this form, your inquiry will be sent to me so we can start planning together.',
									'prelaunch-wp'
								);
							?>
						</p>
					</div>

				<?php else : ?>

					<p class="form-block__brand">
						<?php echo esc_html( $generic_brand ); ?>
					</p>

					<h1 class="form-block__title">
						<?php echo esc_html( $generic_title ); ?>
					</h1>

					<div class="form-block__copy">
						<?php echo wp_kses_post( $generic_copy ); ?>
					</div>

				<?php endif; ?>

			</div>
		</aside>

	</div>
</section>
