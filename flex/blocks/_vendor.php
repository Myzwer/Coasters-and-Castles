<?php
	/**
	 * Vendor content block.
	 *
	 * Renders introductory WYSIWYG content beside a list of vendor entries,
	 * with an optional CTA after the vendor list.
	 *
	 * Used in:
	 * - preferred vendor lists
	 * - partner/supplier summaries
	 * - destination or service-provider highlights
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Expected fields:
	 * - intro: WYSIWYG Editor
	 * - vendor: Repeater
	 *   - vendor_name: Text
	 *   - vendor_description: Text Area
	 * - link: Link (array)
	 *
	 * Notes:
	 * - Intro content renders in the left column on desktop.
	 * - Vendor entries render in the wider right column on desktop.
	 * - The optional CTA renders after the vendor list rather than in the intro.
	 * - All content stacks naturally on mobile.
	 * - Vendor descriptions are plain textarea values, so line breaks are preserved
	 *   with nl2br() instead of rendered as WYSIWYG content.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$intro = get_sub_field( 'intro' );
	$link  = get_sub_field( 'link' );

	if ( ! $intro && ! have_rows( 'vendor' ) && ! $link ) {
		return;
	}
?>

<section class="py-16 wrap">
	<div class="grid grid-cols-12 gap-y-10 md:gap-x-12 lg:gap-x-16">

		<div class="col-span-12 md:col-span-4">
			<div class="md:sticky md:top-24">
				<?php if ( $intro ) : ?>
					<div class="prose-theme">
						<?php echo wp_kses_post( $intro ); ?>
					</div>

					<div class="mt-5 h-[3px] w-16 rounded-full bg-secondary" aria-hidden="true"></div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( have_rows( 'vendor' ) || $link ) : ?>
			<div class="col-span-12 md:col-span-8">

				<?php if ( have_rows( 'vendor' ) ) : ?>
					<div class="grid gap-6 md:gap-8">

						<?php while ( have_rows( 'vendor' ) ) : the_row(); ?>
							<?php
							$vendor_name        = get_sub_field( 'vendor_name' );
							$vendor_description = get_sub_field( 'vendor_description' );
							?>

							<?php if ( $vendor_name || $vendor_description ) : ?>
								<article
									class="rounded-xl border-[3px] border-secondary bg-white/90 p-6 shadow-lg md:p-7">
									<div class="prose-theme prose-compact">
										<?php if ( $vendor_name ) : ?>
											<h3 class="heading-4">
												<?php echo esc_html( $vendor_name ); ?>
											</h3>
										<?php endif; ?>

										<?php if ( $vendor_description ) : ?>
											<p>
												<?php
													echo wp_kses(
														$vendor_description,
														array(
															'br' => array(),
														)
													);
												?>
											</p>
										<?php endif; ?>
									</div>
								</article>
							<?php endif; ?>
						<?php endwhile; ?>

					</div>
				<?php endif; ?>

				<?php if ( $link && ! empty( $link['url'] ) && ! empty( $link['title'] ) ) : ?>
					<?php
					$link_url    = $link['url'];
					$link_title  = $link['title'];
					$link_target = ! empty( $link['target'] ) ? $link['target'] : '_self';
					?>

					<div class="mt-8 flex justify-start">
						<a
							class="btn_main inline-flex w-fit"
							href="<?php echo esc_url( $link_url ); ?>"
							target="<?php echo esc_attr( $link_target ); ?>"
							<?php echo '_blank' === $link_target ? 'rel="noopener noreferrer"' : ''; ?>
						>
							<?php echo esc_html( $link_title ); ?>
						</a>
					</div>
				<?php endif; ?>

			</div>
		<?php endif; ?>

	</div>
</section>
