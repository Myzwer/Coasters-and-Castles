<?php
	/**
	 * Gallery CTA content block.
	 *
	 * Renders a 5-image visual gallery with one CTA tile.
	 *
	 * Used in:
	 * - visual landing page sections
	 * - service or experience highlights
	 * - fun/photo-driven promotional sections
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Expected fields:
	 * - intro: WYSIWYG Editor
	 * - image_1: Image
	 * - image_2: Image
	 * - image_3: Image
	 * - image_4: Image
	 * - image_5: Image
	 * - cta_heading: Text
	 * - cta_link: Link
	 *
	 * Notes:
	 * - Images are decorative/supporting gallery images.
	 * - Images are locked to a 1:1 aspect ratio.
	 * - Images use object-cover so they crop instead of stretching.
	 * - The outer grid handles rounded corners so only the outside corners round.
	 * - On mobile, image_5 is intentionally hidden so the CTA can become a full-width tile.
	 * - The CTA tile is a full clickable link, with an internal visual label/icon.
	 *
	 * Reference:
	 * - Follows the same general ACF/block documentation style as the Image + Text block.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$intro       = get_sub_field( 'intro' );
	$image_1     = get_sub_field( 'image_1' );
	$image_2     = get_sub_field( 'image_2' );
	$image_3     = get_sub_field( 'image_3' );
	$image_4     = get_sub_field( 'image_4' );
	$image_5     = get_sub_field( 'image_5' );
	$cta_heading = get_sub_field( 'cta_heading' );
	$cta_link    = get_sub_field( 'cta_link' );

	$images = [
		[
			'image' => $image_1,
			'class' => 'order-1 col-span-1 md:order-1',
		],
		[
			'image' => $image_2,
			'class' => 'order-2 col-span-1 md:order-2',
		],
		[
			'image' => $image_3,
			'class' => 'order-3 col-span-1 md:order-3',
		],
		[
			'image' => $image_4,
			'class' => 'order-4 col-span-1 md:order-5',
		],
		[
			'image' => $image_5,
			'class' => 'hidden md:block md:order-6 md:col-span-1',
		],
	];

	$render_gallery_image = static function ( array $image, string $classes = '' ): void {
		$image_id = ! empty( $image['ID'] ) ? absint( $image['ID'] ) : 0;

		if ( $image_id ) {
			echo wp_get_attachment_image(
				$image_id,
				'large',
				false,
				[
					'class'    => trim( 'aspect-square h-full w-full object-cover ' . $classes ),
					'loading'  => 'lazy',
					'decoding' => 'async',
				]
			);

			return;
		}

		if ( ! empty( $image['url'] ) ) {
			?>
			<img
				class="<?php echo esc_attr( trim( 'aspect-square h-full w-full object-cover ' . $classes ) ); ?>"
				src="<?php echo esc_url( $image['url'] ); ?>"
				alt="<?php echo esc_attr( $image['alt'] ?? '' ); ?>"
				loading="lazy"
				decoding="async"
			/>
			<?php
		}
	};
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

		<div class="col-span-12 md:col-span-10 md:col-start-2">
			<div class="grid grid-cols-2 gap-1 overflow-hidden rounded-xl p-1 md:grid-cols-3">

				<?php foreach ( $images as $image_item ) : ?>
					<?php if ( ! empty( $image_item['image'] ) ) : ?>
						<div class="<?php echo esc_attr( $image_item['class'] ); ?>">
							<?php $render_gallery_image( $image_item['image'] ); ?>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>

				<?php if ( ! empty( $cta_link['url'] ) ) : ?>
					<a
						class="order-5 col-span-2 grid bg-secondary p-6 text-white transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary focus-visible:ring-offset-4 md:order-4 md:col-span-1 md:aspect-square md:p-8"
						href="<?php echo esc_url( $cta_link['url'] ); ?>"
						<?php echo ! empty( $cta_link['target'] ) ? ' target="' . esc_attr( $cta_link['target'] ) . '" rel="noopener noreferrer"' : ''; ?>
					>
						<div class="grid content-center gap-5">
							<?php if ( $cta_heading ) : ?>
								<h2 class="heading-2 text-white">
									<?php echo esc_html( $cta_heading ); ?>
								</h2>
							<?php endif; ?>

							<span
								class="inline-grid w-fit grid-flow-col items-center gap-3 rounded-xl border-2 border-white px-5 py-3 font-bold text-white">
								<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
								<span><?php echo esc_html( $cta_link['title'] ?: 'Learn More' ); ?></span>
							</span>
						</div>
					</a>
				<?php endif; ?>

			</div>
		</div>

	</div>
</section>
