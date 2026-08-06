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

	/**
	 * Pass the Advisor post ID to the booking page.
	 *
	 * Gravity Forms can later use this query value to dynamically populate
	 * the appropriate advisor field before sending data to the CRM.
	 */
	$booking_url = add_query_arg(
		[
			'advisor' => $advisor_id,
		],
		home_url( '/booking/' )
	);
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

				</div>
			</div>

		</div>
	</div>
</section>
