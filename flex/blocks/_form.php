<?php
	/**
	 * Form block.
	 *
	 * Renders a Gravity Forms form alongside a branded content rail.
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Fields:
	 * - intro: Optional content displayed above the form.
	 * - form_id: Gravity Forms form ID.
	 * - title: Small brand / eyebrow text in the rail.
	 * - subtitle: Primary rail heading.
	 * - copy: Supporting rail content.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$intro    = get_sub_field( 'intro' );
	$form_id  = absint( get_sub_field( 'form_id' ) );
	$title    = get_sub_field( 'title' );
	$subtitle = get_sub_field( 'subtitle' );
	$copy     = get_sub_field( 'copy' );
?>

<section class="form-block">
	<div class="form-block__layout">

		<div class="form-block__main">
			<div class="form-block__form-wrap">

				<?php if ( $intro ) : ?>
					<div class="form-block__intro prose-theme">
						<?php echo wp_kses_post( $intro ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $form_id ) : ?>
					<div class="form-block__form">
						<?php
							echo do_shortcode(
								'[gravityform id="' . $form_id . '" title="false" description="false" ajax="true"]'
							);
						?>
					</div>
				<?php endif; ?>

			</div>
		</div>

		<aside class="form-block__rail">
			<div class="form-block__rail-inner">

				<?php if ( $title ) : ?>
					<p class="form-block__brand">
						<?php echo esc_html( $title ); ?>
					</p>
				<?php endif; ?>

				<?php if ( $subtitle ) : ?>
					<h2 class="form-block__title">
						<?php echo esc_html( $subtitle ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( $copy ) : ?>
					<div class="form-block__copy">
						<?php echo wp_kses_post( $copy ); ?>
					</div>
				<?php endif; ?>

			</div>
		</aside>

	</div>
</section>
