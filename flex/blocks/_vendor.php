<?php
	/**
	 * Vendor content block.
	 *
	 * Renders introductory WYSIWYG content beside a list of vendor entries.
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
	 *
	 * Notes:
	 * - Intro content renders in the left column on desktop.
	 * - Vendor entries render in the right column on desktop.
	 * - All content stacks naturally on mobile.
	 * - Vendor descriptions are plain textarea values, so line breaks are preserved
	 *   with nl2br() instead of rendered as WYSIWYG content.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$intro = get_sub_field( 'intro' );

	if ( ! $intro && ! have_rows( 'vendor' ) ) {
		return;
	}
?>

<section class="py-16 wrap">
	<div class="grid grid-cols-12 gap-y-10 md:gap-x-12 lg:gap-x-16">

		<div class="col-span-12 md:col-span-5">
			<?php if ( $intro ) : ?>
				<div class="prose-theme">
					<?php echo wp_kses_post( $intro ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( have_rows( 'vendor' ) ) : ?>
			<div class="col-span-12 md:col-span-7">
				<div class="grid gap-6 md:gap-8">

					<?php while ( have_rows( 'vendor' ) ) : the_row(); ?>
						<?php
						$vendor_name        = get_sub_field( 'vendor_name' );
						$vendor_description = get_sub_field( 'vendor_description' );
						?>

						<?php if ( $vendor_name || $vendor_description ) : ?>
							<article class="rounded-xl border-[3px] border-secondary bg-white/90 p-6 shadow-lg md:p-7">
								<div class="prose-theme prose-compact">
									<?php if ( $vendor_name ) : ?>
										<h3 class="heading-4">
											<?php echo esc_html( $vendor_name ); ?>
										</h3>
									<?php endif; ?>

									<?php if ( $vendor_description ) : ?>
										<p>
											<?php echo nl2br( esc_html( $vendor_description ) ); ?>
										</p>
									<?php endif; ?>
								</div>
							</article>
						<?php endif; ?>
					<?php endwhile; ?>

				</div>
			</div>
		<?php endif; ?>

	</div>
</section>
