<?php
	/**
	 * Lead Capture Block
	 *
	 * Renders a resource/mockup image alongside descriptive copy and a Gravity Form.
	 *
	 * ACF fields:
	 * - resource_image: Image
	 * - description: WYSIWYG Editor
	 * - form_id: Number
	 *
	 * Notes:
	 * - The resource image does not enforce an aspect ratio because it may be
	 *   a book cover, PDF mockup, transparent PNG, device mockup, or other
	 *   non-standard asset.
	 * - If no resource image is provided, the global fallback_image option is used.
	 * - Gravity Forms renders from the provided Form ID.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$resource_image = get_sub_field( 'resource_image' );
	$description    = get_sub_field( 'description' );
	$form_id        = get_sub_field( 'form_id' );

	if ( empty( $resource_image ) && function_exists( 'get_field' ) ) {
		$resource_image = get_field( 'fallback_image', 'option' );
	}
?>

<section class="py-16 wrap">
	<div class="grid grid-cols-12 items-center gap-y-10 md:gap-x-16">

		<?php if ( ! empty( $resource_image ) ) : ?>
			<div class="col-span-12 md:col-span-5">
				<img
					class="mx-auto h-auto max-w-full rounded-xl"
					src="<?php echo esc_url( $resource_image['url'] ); ?>"
					alt="<?php echo esc_attr( ! empty( $resource_image['alt'] ) ? $resource_image['alt'] : 'Resource image' ); ?>"
					loading="lazy"
					decoding="async"
				/>
			</div>
		<?php endif; ?>

		<div
			class="col-span-12 <?php echo ! empty( $resource_image ) ? 'md:col-span-6 md:col-start-7' : 'md:col-span-8 md:col-start-3'; ?>">
			<div class="grid gap-6 rounded-2xl border-[3px] border-secondary bg-white/80 p-6 shadow-xl md:p-8">

				<?php if ( $description ) : ?>
					<div class="prose-theme">
						<?php echo wp_kses_post( $description ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $form_id ) && function_exists( 'gravity_form' ) ) : ?>
					<div class="lead-capture-form">
						<?php
							gravity_form(
								(int) $form_id,
								false,
								false,
								false,
								null,
								true,
								0
							);
						?>
					</div>
				<?php endif; ?>

			</div>
		</div>

	</div>
</section>
