<?php

	/**
	 * Advisor reviews section.
	 *
	 * Displays up to three client reviews in a stacked layout.
	 * Background alternation and texture are handled by single-advisor.php.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id      = get_the_ID();
	$reviews         = get_field( 'advisor_reviews', $advisor_id );
	$tln_profile_url = get_field( 'tln_profile_link', $advisor_id );

	$reviews = is_array( $reviews ) ? $reviews : [];

	/*
	 * Remove completely empty repeater rows.
	 */
	$reviews = array_values(
		array_filter(
			$reviews,
			static function ( array $review ): bool {
				return ! empty( $review['review_text'] );
			}
		)
	);

	if ( ! $reviews ) {
		return;
	}
?>

<section class="py-10 md:py-16 wrap">
	<div class="grid-12">

		<div class="col-span-12 text-center">
			<h2 class="heading-2">
				<?php esc_html_e( 'What My Travelers Say', 'prelaunch-wp' ); ?>
			</h2>
		</div>

		<?php foreach ( $reviews as $review ) : ?>
			<?php
			$review_text = $review['review_text'] ?? '';
			$reviewer    = $review['reviewer_name'] ?? '';
			$trip        = $review['review_trip_details'] ?? '';
			$source      = $review['review_source'] ?? '';
			$source_url  = $review['review_url'] ?? '';
			?>

			<div class="col-span-12 mt-8">
				<article class="rounded-xl bg-white p-6 shadow-lg md:p-8 lg:p-10">

					<i
						class="fa-solid fa-quote-left text-3xl text-secondary"
						aria-hidden="true"
					></i>

					<blockquote class="mt-4 text-lg leading-relaxed">
						<?php echo wp_kses_post( wpautop( $review_text ) ); ?>
					</blockquote>

					<footer class="mt-6 border-t border-black/15 pt-5">
						<?php if ( $reviewer ) : ?>
							<p class="font-semibold">
								<?php echo esc_html( $reviewer ); ?>
							</p>
						<?php endif; ?>

						<?php if ( $trip ) : ?>
							<p class="mt-1 text-sm italic">
								<?php echo esc_html( $trip ); ?>
							</p>
						<?php endif; ?>

						<?php if ( $source ) : ?>
							<p class="mt-3 text-sm">
								<?php if ( $source_url ) : ?>
									<a
										class="font-semibold text-secondary underline underline-offset-4"
										href="<?php echo esc_url( $source_url ); ?>"
										target="_blank"
										rel="noopener noreferrer"
									>
										<?php
											printf(
											/* translators: %s: Review source name. */
												esc_html__( 'Read on %s', 'prelaunch-wp' ),
												esc_html( $source )
											);
										?>
									</a>
								<?php else : ?>
									<span>
										<?php
											printf(
											/* translators: %s: Review source name. */
												esc_html__( 'Source: %s', 'prelaunch-wp' ),
												esc_html( $source )
											);
										?>
									</span>
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</footer>

				</article>
			</div>

		<?php endforeach; ?>

		<?php if ( $tln_profile_url ) : ?>
			<div class="col-span-12 mt-8 text-center">
				<a
					class="btn_main inline-flex items-center gap-2"
					href="<?php echo esc_url( $tln_profile_url ); ?>"
					target="_blank"
					rel="noopener noreferrer"
				>
					<?php esc_html_e( 'See More Reviews', 'prelaunch-wp' ); ?>
					<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
				</a>
			</div>
		<?php endif; ?>

	</div>
</section>
