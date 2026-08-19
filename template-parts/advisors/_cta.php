<?php

	/**
	 * Advisor booking CTA.
	 *
	 * Displays the fixed closing call to action on individual Advisor profiles.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id = get_the_ID();

	$booking_url = function_exists( 'prelaunch_get_advisor_booking_url' )
		? prelaunch_get_advisor_booking_url( $advisor_id )
		: '';
?>

<section>
	<div class="py-16 wrap md:py-20">
		<div class="grid-12 gap-y-10">

			<div class="col-span-12 md:col-span-6">
				<h2 class="text-6xl font-bold uppercase">
					<?php
						echo nl2br(
							esc_html__(
								"Dream big.\nLet me do the\nrest.",
								'prelaunch-wp'
							)
						);
					?>
				</h2>
			</div>

			<div class="col-span-12 grid md:col-span-6 md:items-center">
				<div class="grid justify-items-start gap-6">

					<p class="max-w-xl text-lg">
						<?php
							esc_html_e(
								'Tell me a little more about your trip, and I’ll create the perfect vacation to make your travel dreams a reality.',
								'prelaunch-wp'
							);
						?>
					</p>

					<?php if ( $booking_url ) : ?>
						<a
							class="btn_main"
							href="<?php echo esc_url( $booking_url ); ?>"
						>
							<span>
								<?php esc_html_e( 'Book with me', 'prelaunch-wp' ); ?>
							</span>

							<i
								class="fa-solid fa-arrow-right"
								aria-hidden="true"
							></i>
						</a>
					<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
						<p class="text-sm">
							<?php
								esc_html_e(
									'Booking page is not currently configured.',
									'prelaunch-wp'
								);
							?>
						</p>
					<?php endif; ?>

				</div>
			</div>

		</div>
	</div>
</section>
