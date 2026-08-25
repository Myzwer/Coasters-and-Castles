<?php
	/**
	 * Image + text content block.
	 *
	 * Renders a section combining an image with accompanying text content.
	 *
	 * Used in:
	 * - feature explanations
	 * - service highlights
	 * - visual storytelling sections
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Notes:
	 * - Image should be optimized and ideally landscape orientation.
	 * - Text content is rendered from a WYSIWYG field.
	 * - Block should return early if no meaningful content exists.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}


	$content  = get_sub_field( 'content' );
	$image    = get_sub_field( 'image' );
	$image_id = ! empty( $image['ID'] ) ? absint( $image['ID'] ) : 0;

	if ( ! $content && ! $image_id ) {
		return;
	}

	$has_image   = (bool) $image_id;
	$image_class = 'col-span-12 md:col-span-6';
	$text_class  = $has_image
		? 'col-span-12 md:col-span-6 relative'
		: 'col-span-12 relative';
?>
<section class="py-10 wrap">
	<div class="grid grid-cols-12 gap-4 md:gap-10">
		<?php if ( $has_image ) : ?>
			<div class="<?php echo esc_attr( $image_class ); ?>">
				<?php
					echo wp_get_attachment_image(
						$image_id,
						'large',
						false,
						[
							'class' => 'rounded-lg shadow-lg mb-0 aspect-[1/1] object-cover',
						]
					);
				?>
			</div>
		<?php endif; ?>

		<?php if ( $content ) : ?>
			<div class="<?php echo esc_attr( $text_class ); ?>">
				<div class="content-middle-medium">
					<div class="prose-theme"><?php echo wp_kses_post( $content ); ?></div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
