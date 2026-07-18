<?php
	/**
	 * Quote / testimonial block.
	 *
	 * Renders a 3-card testimonial/review section.
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Expected fields:
	 * - intro: WYSIWYG
	 * - reviews: Repeater, capped/required at 3
	 *   - image: Image
	 *   - name: Text
	 *   - quote: WYSIWYG Editor
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$intro = get_sub_field( 'intro' );

	/*
	 * Rotation classes are assigned by index so the repeater can stay clean.
	 * The ACF field is capped at 3 reviews, so these map directly to:
	 * 1 = slight left tilt
	 * 2 = nearly straight
	 * 3 = slight right tilt
	 *
	 * On mobile, rotation is removed so cards stack cleanly and don't cause
	 * awkward overflow.
	 */
	$review_card_transforms = [
		'md:-rotate-8',
		'md:-rotate-2',
		'md:rotate-8',
	];
?>

<section class="py-16 wrap">
	<div class="grid-12 gap-y-10">

		<?php if ( $intro ) : ?>
			<div class="col-span-12 mx-auto max-w-4xl text-center mb-10">
				<div class="prose-theme">
					<?php echo wp_kses_post( $intro ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( have_rows( 'reviews' ) ) : ?>
			<div class="col-span-12">
				<div class="grid grid-cols-12 gap-y-10 md:gap-x-10 md:items-start">

					<?php
						$review_index = 0;

						while ( have_rows( 'reviews' ) ) :
							the_row();

							$image = get_sub_field( 'image' );
							$name  = get_sub_field( 'name' );
							$quote = get_sub_field( 'quote' );

							$image_id = ! empty( $image['ID'] ) ? absint( $image['ID'] ) : 0;

							$rotation_class = $review_card_transforms[ $review_index ] ?? '';
							$review_index ++;
							?>

							<article class="col-span-12 md:col-span-4">
								<figure
									class="grid min-h-full rounded-xl border-4 border-white bg-secondary p-6 text-white shadow-xl transition-transform <?php echo esc_attr( $rotation_class ); ?>">

									<?php if ( $image_id || $name ) : ?>
										<figcaption class="grid grid-cols-[auto_1fr] items-center gap-4 pb-6">
											<?php if ( $image_id ) : ?>
												<div class="h-18 w-18 overflow-hidden">
													<?php
														echo wp_get_attachment_image(
															$image_id,
															'thumbnail',
															false,
															[
																'class' => 'h-full w-full object-cover rounded-md',
															]
														);
													?>
												</div>
											<?php endif; ?>

											<?php if ( $name ) : ?>
												<p class="m-0 text-lg font-bold leading-tight">
													<?php echo esc_html( $name ); ?>
												</p>
											<?php endif; ?>
										</figcaption>
									<?php endif; ?>

									<?php if ( $quote ) : ?>
										<div class="prose-theme prose-compact theme-invert text-base leading-snug">
											<?php echo wp_kses_post( $quote ); ?>
										</div>
									<?php endif; ?>

									<div class="mt-4 justify-self-end text-5xl font-bold leading-none"
										 aria-hidden="true">
										&rdquo;
									</div>

								</figure>
							</article>

						<?php endwhile; ?>

				</div>
			</div>
		<?php endif; ?>

	</div>
</section>
