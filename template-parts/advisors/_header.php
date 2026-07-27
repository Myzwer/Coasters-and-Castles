<?php

	/**
	 * Advisor profile header.
	 *
	 * Displays:
	 * - Advisor name
	 * - Professional title
	 * - Vacation Types
	 * - Group Types
	 * - Booking CTA
	 * - Advisor portrait
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id = get_the_ID();

	$advisor_name  = get_the_title();
	$advisor_title = get_field( 'advisor_title', $advisor_id );
	$headshot_id   = absint( get_field( 'advisor_headshot', $advisor_id ) );

	$advisor_first_name = strtok( $advisor_name, ' ' );

	if ( false === $advisor_first_name ) {
		$advisor_first_name = $advisor_name;
	}

	$vacation_types = get_the_terms( $advisor_id, 'vacation_type' );
	$group_types    = get_the_terms( $advisor_id, 'group_type' );

	$vacation_types = is_array( $vacation_types ) ? $vacation_types : [];
	$group_types    = is_array( $group_types ) ? $group_types : [];

	/**
	 * Pass the Advisor post ID to the booking page.
	 *
	 * Gravity Forms can later use this query value to populate an advisor field.
	 */
	$booking_url = add_query_arg(
		[
			'advisor' => $advisor_id,
		],
		home_url( '/booking/' )
	);

	$advisor_background     = get_field( 'advisor_background', 'option' );
	$advisor_background_url = '';

	if ( is_array( $advisor_background ) && ! empty( $advisor_background['url'] ) ) {
		$advisor_background_url = $advisor_background['url'];
	}
?>

<section
	class="<?php echo esc_attr( $advisor_background_url ? 'bg-media bg-cover bg-center bg-no-repeat' : 'bg-secondary-gradient' ); ?> py-12 md:py-16 lg:py-20"
	<?php if ( $advisor_background_url ) : ?>
		style="--bg-image: url('<?php echo esc_url( $advisor_background_url ); ?>');"
	<?php endif; ?>
>
	<div class="wrap">
		<div class="grid-12 items-center gap-y-10 md:gap-x-10 lg:gap-x-16">

			<div class="col-span-12 md:col-span-6 lg:col-span-5">
				<div class="rounded-xl border-[3px] border-secondary bg-white p-6 shadow-lg md:p-8 lg:p-10">

					<header>
						<h1 class="heading-2 normal-case">
							<?php echo esc_html( $advisor_name ); ?>
						</h1>

						<?php if ( $advisor_title ) : ?>
							<p class="mt-1 text-lg italic">
								<?php echo esc_html( $advisor_title ); ?>
							</p>
						<?php endif; ?>
					</header>

					<?php if ( $vacation_types ) : ?>
						<div class="mt-8">
							<h2 class="text-xl font-semibold">
								<?php esc_html_e( 'Vacation Types', 'prelaunch-wp' ); ?>
							</h2>

							<p class="mt-1 italic">
								<?php
									echo esc_html(
										implode(
											', ',
											wp_list_pluck( $vacation_types, 'name' )
										)
									);
								?>
							</p>
						</div>
					<?php endif; ?>

					<?php if ( $group_types ) : ?>
						<div class="mt-6">
							<h2 class="text-xl font-semibold">
								<?php esc_html_e( 'Group Types', 'prelaunch-wp' ); ?>
							</h2>

							<p class="mt-1 italic">
								<?php
									echo esc_html(
										implode(
											', ',
											wp_list_pluck( $group_types, 'name' )
										)
									);
								?>
							</p>
						</div>
					<?php endif; ?>

					<div class="mt-8">
						<a
							class="btn_main w-full"
							href="<?php echo esc_url( $booking_url ); ?>"
						>
							<span>
								<?php
									printf(
									/* translators: %s: Advisor first name. */
										esc_html__( 'Book with %s', 'prelaunch-wp' ),
										esc_html( $advisor_first_name )
									);
								?>
							</span>

							<i
								class="fa-solid fa-arrow-right"
								aria-hidden="true"
							></i>
						</a>
					</div>

				</div>
			</div>

			<?php if ( $headshot_id ) : ?>
				<div class="col-span-12 md:col-span-6 lg:col-span-6 lg:col-start-7">
					<figure class="mx-auto max-w-md rotate-3 bg-white p-4 pb-12 shadow-xl md:p-5 md:pb-14">
						<?php
							echo wp_get_attachment_image(
								$headshot_id,
								'large',
								false,
								[
									'class' => 'aspect-[4/5] w-full object-cover',
									'sizes' => '(min-width: 1024px) 500px, (min-width: 768px) 45vw, 90vw',
								]
							);
						?>
					</figure>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
