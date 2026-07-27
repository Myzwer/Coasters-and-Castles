<?php

	/**
	 * Advisor travel expertise section.
	 *
	 * Displays:
	 * - Professional Training
	 * - Firsthand Experience
	 *
	 * Background alternation and texture are handled by single-advisor.php.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id = get_the_ID();

	$training   = get_field( 'advisor_training', $advisor_id );
	$experience = get_field( 'advisor_firsthand_experience', $advisor_id );

	$training   = is_array( $training ) ? $training : [];
	$experience = is_array( $experience ) ? $experience : [];

	if ( ! $training && ! $experience ) {
		return;
	}

	$has_both = $training && $experience;
?>

<section class="py-10 md:py-16 wrap">
	<div class="grid-12">

		<div class="col-span-12">
			<h2 class="heading-2 text-center">
				<?php esc_html_e( 'Travel Expertise', 'prelaunch-wp' ); ?>
			</h2>

			<p class="mx-auto mt-3 max-w-2xl text-left md:text-center text-lg">
				<?php
					esc_html_e(
						'Training, certifications, and firsthand knowledge that help me plan better trips.',
						'prelaunch-wp'
					);
				?>
			</p>
		</div>

		<?php if ( $training ) : ?>
			<div
				class="<?php
					echo esc_attr(
						$has_both
							? 'col-span-12 mt-8 md:col-span-6'
							: 'col-span-12 mt-8 md:col-span-8 md:col-start-3'
					);
				?>"
			>
				<div class="h-full rounded-xl bg-white p-6 shadow-lg md:p-8 border-secondary border-3">

					<h3 class="heading-3">
						<?php esc_html_e( 'Professional Training', 'prelaunch-wp' ); ?>
					</h3>

					<ul class="mt-5 grid gap-3">
						<?php foreach ( $training as $training_item ) : ?>
							<?php
							$training_name = $training_item['training_name'] ?? '';

							if ( ! $training_name ) {
								continue;
							}
							?>

							<li class="grid grid-cols-[auto_1fr] gap-3">
								<i
									class="fa-solid fa-check mt-1 text-secondary"
									aria-hidden="true"
								></i>

								<span>
									<?php echo esc_html( $training_name ); ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>

				</div>
			</div>
		<?php endif; ?>

		<?php if ( $experience ) : ?>
			<div
				class="<?php
					echo esc_attr(
						$has_both
							? 'col-span-12 mt-8 md:col-span-6'
							: 'col-span-12 mt-8 md:col-span-8 md:col-start-3'
					);
				?>"
			>
				<div class="h-full rounded-xl bg-white p-6 shadow-lg md:p-8 border-secondary border-3">

					<h3 class="heading-3">
						<?php esc_html_e( 'Firsthand Experience', 'prelaunch-wp' ); ?>
					</h3>

					<ul class="mt-5 grid gap-3">
						<?php foreach ( $experience as $experience_item ) : ?>
							<?php
							$experience_text = $experience_item['experience'] ?? '';

							if ( ! $experience_text ) {
								continue;
							}
							?>

							<li class="grid grid-cols-[auto_1fr] gap-3">
								<i
									class="fa-solid fa-location-dot mt-1 text-secondary"
									aria-hidden="true"
								></i>

								<span>
									<?php echo esc_html( $experience_text ); ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>

				</div>
			</div>
		<?php endif; ?>

	</div>
</section>
