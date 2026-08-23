<?php
	/**
	 * Icon List content block.
	 *
	 * Renders a vertical list of icon-backed content cards.
	 *
	 * Used in:
	 * - trust/value sections
	 * - why choose us sections
	 * - feature explanations
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Expected fields:
	 * - intro: WYSIWYG
	 * - items: Repeater
	 *   - icon: Font Awesome Icon
	 *   - title: Text
	 *   - content: WYSIWYG
	 * - link: Link, optional section-level CTA
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$intro = get_sub_field( 'intro' );
	$link  = get_sub_field( 'link' );
?>

<section class="py-16 wrap">
	<div class="grid-12 gap-y-10">

		<?php if ( $intro ) : ?>
			<div class="col-span-12 mx-auto max-w-4xl text-center">
				<div class="prose-theme">
					<?php echo wp_kses_post( $intro ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( have_rows( 'items' ) ) : ?>
			<div class="col-span-12">
				<div class="grid grid-cols-12 gap-y-6 md:gap-y-8">

					<?php while ( have_rows( 'items' ) ) : the_row(); ?>
						<?php
						$icon    = get_sub_field( 'icon' );
						$title   = get_sub_field( 'title' );
						$content = get_sub_field( 'content' );

						/*
						 * ACF Font Awesome fields can vary by return format.
						 * This keeps the template safe if the field returns:
						 * - a plain class string
						 * - an array with `class`
						 * - an array with `value`
						 */
						$icon_class = '';

						if ( is_string( $icon ) ) {
							$icon_class = $icon;
						} elseif ( is_array( $icon ) ) {
							$icon_class = $icon['class'] ?? $icon['value'] ?? '';
						}
						?>

						<article
							class="col-span-12 overflow-hidden rounded-xl border-[3px] border-secondary bg-white shadow-xl">
							<div
								class="grid grid-cols-1 items-center gap-y-5 px-6 py-8 md:grid-cols-[8rem_1fr] md:gap-x-8 md:px-10 md:py-10">

								<?php if ( $icon_class ) : ?>
									<div class="grid place-items-center">
										<i
											class="<?php echo esc_attr( $icon_class ); ?> text-6xl text-secondary md:text-7xl"
											aria-hidden="true"
										></i>
									</div>
								<?php endif; ?>

								<div class="<?php echo $icon_class ? '' : 'md:col-span-2'; ?>">
									<?php if ( $title ) : ?>
										<h3 class="heading-3 mb-2">
											<?php echo esc_html( $title ); ?>
										</h3>
									<?php endif; ?>

									<?php if ( $content ) : ?>
										<div class="prose-theme prose-compact">
											<?php echo wp_kses_post( $content ); ?>
										</div>
									<?php endif; ?>
								</div>

							</div>
						</article>
					<?php endwhile; ?>

				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $link['url'] ) ) : ?>
			<div class="col-span-12 text-center">
				<a
					class="btn_main"
					href="<?php echo esc_url( $link['url'] ); ?>"
					<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener noreferrer"' : ''; ?>
				>
					<span><?php echo esc_html( $link['title'] ?: 'Learn More' ); ?></span>
				</a>
			</div>
		<?php endif; ?>

	</div>
</section>
