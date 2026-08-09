<?php

	/**
	 * Advisor specialized planning support section.
	 *
	 * Displays areas where the advisor has meaningful experience
	 * supporting travelers with accessibility, sensory, dietary,
	 * mobility, medical, or other specialized planning needs.
	 *
	 * Background alternation and texture are handled by single-advisor.php.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id          = get_the_ID();
	$specialized_support = get_field( 'advisor_specialized_planning', $advisor_id );

	$specialized_support = is_array( $specialized_support )
		? $specialized_support
		: [];

	/*
	 * Remove completely empty repeater rows.
	 */
	$specialized_support = array_values(
		array_filter(
			$specialized_support,
			static function ( array $support_item ): bool {
				$title       = trim( (string) ( $support_item['title'] ?? '' ) );
				$description = trim( (string) ( $support_item['description'] ?? '' ) );

				return '' !== $title || '' !== $description;
			}
		)
	);

	if ( ! $specialized_support ) {
		return;
	}

	$support_count = count( $specialized_support );

	/*
	 * A single support area spans the full width.
	 * Multiple support areas display two per row on desktop.
	 */
	$card_class = 1 === $support_count
		? 'col-span-12'
		: 'col-span-12 md:col-span-6';
?>

<section class="py-10 md:py-16 wrap">
	<div class="grid-12">

		<div class="col-span-12">
			<h2 class="heading-2 text-center">
				<?php esc_html_e( 'Specialized Planning Support', 'prelaunch-wp' ); ?>
			</h2>

			<p class="mx-auto mt-3 max-w-2xl text-left text-lg md:text-center">
				<?php
					esc_html_e(
						'Additional experience helping travelers plan around the needs that can make every trip a little different.',
						'prelaunch-wp'
					);
				?>
			</p>
		</div>

		<div class="col-span-12 mt-8 grid-12 gap-y-6">
			<?php foreach ( $specialized_support as $support_item ) : ?>
				<?php
				$title       = trim( (string) ( $support_item['title'] ?? '' ) );
				$description = trim( (string) ( $support_item['description'] ?? '' ) );
				?>

				<article class="<?php echo esc_attr( $card_class ); ?>">
					<div class="h-full rounded-xl border-3 border-secondary bg-white p-6 shadow-lg md:p-8">

						<?php if ( $title ) : ?>
							<h3 class="heading-3">
								<?php echo esc_html( $title ); ?>
							</h3>
						<?php endif; ?>

						<?php if ( $description ) : ?>
							<div class="<?php echo $title ? 'mt-4 ' : ''; ?>leading-relaxed">
								<?php echo wp_kses_post( wpautop( $description ) ); ?>
							</div>
						<?php endif; ?>

					</div>
				</article>

			<?php endforeach; ?>
		</div>

	</div>
</section>
