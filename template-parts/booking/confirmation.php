<?php
	/**
	 * Booking confirmation display.
	 *
	 * @var array{
	 *     first_name?: string,
	 *     advisor?: WP_Post|null,
	 *     vacation_type?: WP_Term|null,
	 *     post_context?: array{
	 *         query: WP_Query,
	 *         matched_trip: bool,
	 *         trip_type: ?WP_Term
	 *     }|null
	 * } $args
	 *
	 * @package PrelaunchWP
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$first_name = isset( $args['first_name'] )
		? trim( (string) $args['first_name'] )
		: '';

	$advisor      = $args['advisor'] ?? null;
	$post_context = $args['post_context'] ?? null;

	/*
	 * Completion section.
	 *
	 * No copy fallbacks are used here. If an ACF field is empty,
	 * that piece of content simply does not render.
	 */
	$background_type = (string) get_field(
		'booking_confirmation_background_type'
	);

	$background_image_id = absint(
		get_field( 'booking_confirmation_background_image' )
	);

	$success_headline = trim(
		(string) get_field(
			'booking_confirmation_success_headline'
		)
	);

	$success_message = (string) get_field(
		'booking_confirmation_success_message'
	);

	$done_message = trim(
		(string) get_field(
			'booking_confirmation_done_message'
		)
	);

	/*
	 * Personalize the success headline with {fname}.
	 *
	 * If a first name was not carried through the confirmation redirect,
	 * remove the token cleanly rather than displaying the placeholder.
	 */
	if ( '' !== $first_name ) {
		$success_headline = str_replace(
			'{fname}',
			$first_name,
			$success_headline
		);
	} else {
		$success_headline = str_replace(
			[
				'{fname}, ',
				'{fname} ',
				'{fname}',
			],
			'',
			$success_headline
		);
	}

	/*
	 * Next steps.
	 */
	$next_intro = (string) get_field(
		'booking_confirmation_next_intro'
	);

	$step_1_title = trim(
		(string) get_field(
			'booking_confirmation_step_1_title'
		)
	);

	$step_1_copy = trim(
		(string) get_field(
			'booking_confirmation_step_1_copy'
		)
	);

	$step_2_title = trim(
		(string) get_field(
			'booking_confirmation_step_2_title'
		)
	);

	$step_2_copy = trim(
		(string) get_field(
			'booking_confirmation_step_2_copy'
		)
	);

	$step_3_title = trim(
		(string) get_field(
			'booking_confirmation_step_3_title'
		)
	);

	$step_3_copy = trim(
		(string) get_field(
			'booking_confirmation_step_3_copy'
		)
	);

	/*
	 * Article section.
	 */
	$articles_intro = (string) get_field(
		'booking_confirmation_articles_intro'
	);

	/*
	 * Advisor placeholders.
	 *
	 * A selected advisor always uses their first name:
	 *
	 * {advisor} -> Josh
	 * {Advisor} -> Josh
	 *
	 * Generic bookings use:
	 *
	 * {advisor} -> your advisor
	 * {Advisor} -> Your advisor
	 *
	 * These placeholders may be used in any of the three
	 * next-step titles or descriptions.
	 */
	$advisor_token_lower = __(
		'your advisor',
		'prelaunch-wp'
	);

	$advisor_token_upper = __(
		'Your advisor',
		'prelaunch-wp'
	);

	if ( $advisor instanceof WP_Post ) {
		$advisor_name = trim(
			wp_strip_all_tags(
				get_the_title( $advisor )
			)
		);

		if ( '' !== $advisor_name ) {
			$advisor_first_name = strtok(
				$advisor_name,
				' '
			);

			if (
				false !== $advisor_first_name &&
				'' !== trim( $advisor_first_name )
			) {
				$advisor_first_name = trim(
					$advisor_first_name
				);

				$advisor_token_lower = $advisor_first_name;
				$advisor_token_upper = $advisor_first_name;
			}
		}
	}

	$replace_advisor_tokens = static function (
		string $value
	) use (
		$advisor_token_lower,
		$advisor_token_upper
	): string {
		return str_replace(
			[
				'{Advisor}',
				'{advisor}',
			],
			[
				$advisor_token_upper,
				$advisor_token_lower,
			],
			$value
		);
	};

	$step_1_title = $replace_advisor_tokens(
		$step_1_title
	);

	$step_1_copy = $replace_advisor_tokens(
		$step_1_copy
	);

	$step_2_title = $replace_advisor_tokens(
		$step_2_title
	);

	$step_2_copy = $replace_advisor_tokens(
		$step_2_copy
	);

	$step_3_title = $replace_advisor_tokens(
		$step_3_title
	);

	$step_3_copy = $replace_advisor_tokens(
		$step_3_copy
	);

	/*
	 * Build the three next-step cards.
	 */
	$steps = [
		[
			'title' => $step_1_title,
			'copy'  => $step_1_copy,
		],
		[
			'title' => $step_2_title,
			'copy'  => $step_2_copy,
		],
		[
			'title' => $step_3_title,
			'copy'  => $step_3_copy,
		],
	];

	/*
	 * Recommended article query prepared by booking-confirmation.php.
	 */
	$recommendation_query = (
		is_array( $post_context ) &&
		isset( $post_context['query'] ) &&
		$post_context['query'] instanceof WP_Query
	)
		? $post_context['query']
		: null;

	$posts_url = get_post_type_archive_link( 'post' )
		?: home_url( '/posts/' );

	/*
	 * Primary-gradient sections use the same optional global
	 * texture as page.php.
	 */
	$textured_image = function_exists( 'get_field' )
		? get_field( 'textured_image', 'option' )
		: '';

	$textured_image_url = '';

	if (
		is_array( $textured_image ) &&
		! empty( $textured_image['url'] )
	) {
		$textured_image_url = $textured_image['url'];
	} elseif ( is_string( $textured_image ) ) {
		$textured_image_url = $textured_image;
	}

	$primary_background_style = '';

	if ( $textured_image_url ) {
		$primary_background_style =
			' style="--bg-texture: url(\''
			. esc_url( $textured_image_url )
			. '\');"';
	}

	/*
	 * Optional completion-section image background.
	 *
	 * Generic mode remains plain white.
	 */
	$completion_background_class = 'bg-white';
	$completion_background_style = '';

	if (
		'image' === $background_type &&
		$background_image_id
	) {
		$background_image_url = wp_get_attachment_image_url(
			$background_image_id,
			'full'
		);

		if ( $background_image_url ) {
			$completion_background_class =
				'bg-cover bg-center bg-no-repeat';

			$completion_background_style =
				' style="background-image: url(\''
				. esc_url( $background_image_url )
				. '\');"';
		}
	}
?>

<main id="primary" class="booking-confirmation">

	<section
		class="<?php echo esc_attr( $completion_background_class ); ?> py-16 md:py-24"
		<?php echo $completion_background_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	>
		<div class="wrap">
			<div
				class="mx-auto max-w-4xl rounded-2xl border-[3px] border-secondary bg-soft-1 p-8 text-center shadow-xl md:p-12 lg:p-16">

				<div
					class="booking-confirmation__success-icon mx-auto grid size-20 place-items-center rounded-full bg-secondary text-white md:size-24">
					<span
						class="booking-confirmation__spark booking-confirmation__spark--one"
						aria-hidden="true"
					></span>

					<span
						class="booking-confirmation__spark booking-confirmation__spark--two"
						aria-hidden="true"
					></span>

					<i
						class="fa-solid fa-badge-check text-5xl md:text-6xl"
						aria-hidden="true"
					></i>
				</div>

				<?php if ( $success_headline ) : ?>
					<h1 class="heading-1 mt-6 normal-case">
						<?php echo esc_html( $success_headline ); ?>
					</h1>
				<?php endif; ?>

				<?php if ( trim( wp_strip_all_tags( $success_message ) ) ) : ?>
					<div class="prose-theme mx-auto mt-5 max-w-2xl text-lg">
						<?php echo wp_kses_post( $success_message ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $done_message ) : ?>
					<div
						class="mx-auto mt-8 max-w-2xl rounded-xl border-2 border-secondary bg-white px-5 py-4 text-base font-semibold md:px-6">
						<i
							class="fa-solid fa-circle-check mr-2 text-secondary"
							aria-hidden="true"
						></i>

						<?php echo esc_html( $done_message ); ?>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</section>

	<div class="alt-bg-wrap">

		<section
			class="bg-alternating-gradient bg-alternating-odd<?php echo $textured_image_url ? ' bg-texture' : ''; ?>"
			<?php echo $primary_background_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<div class="py-16 wrap">
				<div class="grid grid-cols-12 gap-y-10 md:gap-x-12 lg:gap-x-16">

					<div class="col-span-12 md:col-span-4">
						<div class="md:sticky md:top-24">
							<?php if ( trim( wp_strip_all_tags( $next_intro ) ) ) : ?>
								<div class="prose-theme">
									<?php echo wp_kses_post( $next_intro ); ?>
								</div>

								<div
									class="mt-5 h-[3px] w-16 rounded-full bg-secondary"
									aria-hidden="true"
								></div>
							<?php endif; ?>
						</div>
					</div>

					<div class="col-span-12 md:col-span-8">
						<div class="grid gap-6 md:gap-8">

							<?php foreach ( $steps as $index => $step ) : ?>
								<?php if ( '' === $step['title'] && '' === $step['copy'] ) : ?>
									<?php continue; ?>
								<?php endif; ?>

								<article
									class="rounded-xl border-[3px] border-secondary bg-white/90 p-6 shadow-lg md:p-7">
									<div class="prose-theme prose-compact">
										<?php if ( $step['title'] ) : ?>
											<h3 class="heading-4">
												<?php
													printf(
														'%d. %s',
														$index + 1,
														esc_html( $step['title'] )
													);
												?>
											</h3>
										<?php endif; ?>

										<?php if ( $step['copy'] ) : ?>
											<p>
												<?php echo nl2br( esc_html( $step['copy'] ) ); ?>
											</p>
										<?php endif; ?>
									</div>
								</article>
							<?php endforeach; ?>

						</div>
					</div>

				</div>
			</div>
		</section>

		<?php if ( $recommendation_query instanceof WP_Query && $recommendation_query->have_posts() ) : ?>
			<section class="bg-alternating-gradient bg-alternating-even">
				<div class="wrap py-10 md:py-16">

					<?php if ( trim( wp_strip_all_tags( $articles_intro ) ) ) : ?>
						<div class="mx-auto max-w-4xl text-center">
							<div class="prose-theme">
								<?php echo wp_kses_post( $articles_intro ); ?>
							</div>
						</div>
					<?php endif; ?>

					<div
						class="<?php echo trim( wp_strip_all_tags( $articles_intro ) ) ? 'mt-10 ' : ''; ?>grid-12 gap-y-8">
						<?php while ( $recommendation_query->have_posts() ) : ?>
							<?php $recommendation_query->the_post(); ?>

							<div class="col-span-12 md:col-span-6 lg:col-span-4">
								<?php get_template_part( 'template-parts/blog/card' ); ?>
							</div>
						<?php endwhile; ?>
					</div>

					<div class="mt-10 text-center">
						<a
							class="btn_main"
							href="<?php echo esc_url( $posts_url ); ?>"
						>
							<span>
								<?php esc_html_e( 'Browse all articles', 'prelaunch-wp' ); ?>
							</span>

							<i
								class="fa-solid fa-arrow-right"
								aria-hidden="true"
							></i>
						</a>
					</div>

				</div>
			</section>

			<?php wp_reset_postdata(); ?>
		<?php endif; ?>

	</div>

</main>
