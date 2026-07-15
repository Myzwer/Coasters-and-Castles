<?php
	/**
	 * Card grid content block.
	 *
	 * Renders a repeating set of image cards with title, text, and optional per-card CTA.
	 *
	 * Used in:
	 * - services grids
	 * - feature highlights
	 * - content summaries
	 *
	 * Content is sourced from ACF Flexible Content fields.
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
			<div class="col-span-12 mx-auto max-w-3xl text-center">
				<div class="prose-theme">
					<?php echo wp_kses_post( $intro ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( have_rows( 'cards' ) ) : ?>
			<div class="col-span-12">
				<div class="grid grid-cols-12 gap-y-10 md:gap-x-12 md:gap-y-12">

					<?php while ( have_rows( 'cards' ) ) : the_row(); ?>
						<?php
						$title     = get_sub_field( 'card_title' );
						$text      = get_sub_field( 'card_text' );
						$image     = get_sub_field( 'card_image' );
						$card_link = get_sub_field( 'card_cta' );
						?>

						<article
							class="col-span-12 grid h-full grid-rows-[auto_1fr] overflow-hidden rounded-xl bg-white shadow-lg md:col-span-6">

							<?php if ( ! empty( $image ) ) : ?>
								<img
									class="aspect-[2/1] w-full object-cover"
									src="<?php echo esc_url( $image['url'] ); ?>"
									alt="<?php echo esc_attr( $image['alt'] ); ?>"
									loading="lazy"
									decoding="async"
								/>
							<?php endif; ?>

							<div class="grid grid-rows-[1fr_auto]">
								<div class="p-5 md:p-6">
									<?php if ( $title ) : ?>
										<h3 class="heading-4 mb-2">
											<?php echo esc_html( $title ); ?>
										</h3>
									<?php endif; ?>

									<?php if ( $text ) : ?>
										<p class="text-base leading-snug">
											<?php echo nl2br( esc_html( $text ) ); ?>
										</p>
									<?php endif; ?>
								</div>

								<?php if ( ! empty( $card_link['url'] ) ) : ?>
									<?php
									$card_link_url    = $card_link['url'];
									$card_link_title  = $card_link['title'] ?: 'Learn More';
									$card_link_target = ! empty( $card_link['target'] ) ? $card_link['target'] : '_self';
									$card_link_rel    = '_blank' === $card_link_target ? 'noopener noreferrer' : '';
									?>

									<a
										class="grid place-items-center bg-secondary px-5 py-3 text-center font-bold text-white transition hover:brightness-90 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-white focus-visible:brightness-90"
										href="<?php echo esc_url( $card_link_url ); ?>"
										target="<?php echo esc_attr( $card_link_target ); ?>"
										<?php echo $card_link_rel ? 'rel="' . esc_attr( $card_link_rel ) . '"' : ''; ?>
									>
								<span class="inline-grid grid-flow-col items-center gap-2">
									<span><?php echo esc_html( $card_link_title ); ?></span>
									<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
								</span>
									</a>
								<?php endif; ?>
							</div>

						</article>
					<?php endwhile; ?>

				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $link['url'] ) ) : ?>
			<div class="col-span-12 text-center mt-5">
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
