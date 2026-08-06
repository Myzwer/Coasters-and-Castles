<?php

	/**
	 * Advisor travel gallery.
	 *
	 * Displays between two and six advisor travel images in a controlled
	 * scrapbook-style arrangement.
	 *
	 * Background alternation and texture are handled by single-advisor.php.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id = get_the_ID();
	$gallery    = get_field( 'advisor_gallery', $advisor_id );

	$gallery = is_array( $gallery ) ? $gallery : [];

	/*
	 * The ACF field returns attachment IDs, but filter the array defensively
	 * in case old or imported data contains empty values.
	 */
	$gallery = array_values(
		array_filter(
			array_map( 'absint', $gallery )
		)
	);

	/*
	 * A single scrapbook image would look accidental.
	 */
	if ( count( $gallery ) < 2 ) {
		return;
	}

	/*
	 * The intended field maximum is six, but cap it here as well in case
	 * legacy or imported data contains more.
	 */
	$gallery     = array_slice( $gallery, 0, 6 );
	$image_count = count( $gallery );

	/*
	 * Desktop scrapbook positions.
	 *
	 * Mobile remains a straightforward two-column grid.
	 * Desktop uses a twelve-column grid with small rotations and overlaps.
	 */
	$desktop_layouts = [
		2 => [
			'md:col-span-5 md:col-start-2 md:-rotate-3',
			'md:col-span-5 md:col-start-7 md:rotate-2',
		],
		3 => [
			'md:col-span-4 md:-rotate-3',
			'md:col-span-4 md:rotate-2',
			'md:col-span-4 md:-rotate-1',
		],
		4 => [
			'md:col-span-6 md:-rotate-3',
			'md:col-span-6 md:rotate-2',
			'md:col-span-6 md:rotate-1',
			'md:col-span-6 md:-rotate-2',
		],
		5 => [
			'md:col-span-4 md:-rotate-3',
			'md:col-span-4 md:rotate-2',
			'md:col-span-4 md:-rotate-1',
			'md:col-span-5 md:col-start-2 md:rotate-2',
			'md:col-span-5 md:col-start-7 md:-rotate-3',
		],
		6 => [
			'md:col-span-4 md:-rotate-3',
			'md:col-span-4 md:rotate-2',
			'md:col-span-4 md:-rotate-1',
			'md:col-span-4 md:rotate-1',
			'md:col-span-4 md:-rotate-2',
			'md:col-span-4 md:rotate-3',
		],
	];

	$desktop_classes = $desktop_layouts[ $image_count ] ?? [];
?>

<section class="py-10 md:py-16 wrap">
	<div class="grid-12 gap-y-8">

		<div class="col-span-12 text-center">
			<h2 class="heading-2">
				<?php esc_html_e( 'Travel Gallery', 'prelaunch-wp' ); ?>
			</h2>
		</div>

		<div class="col-span-12">
			<div class="grid grid-cols-2 gap-4 md:grid-cols-12 md:gap-x-5 md:gap-y-10 md:py-8">

				<?php foreach ( $gallery as $index => $image_id ) : ?>
					<?php
					$mobile_classes = 'col-span-1';

					/*
					 * Center the final image when mobile has an odd image count.
					 */
					if (
						0 !== $image_count % 2 &&
						$index === $image_count - 1
					) {
						$mobile_classes = 'col-span-2 mx-auto w-1/2 md:mx-0 md:w-auto';
					}

					$image_classes = trim(
						$mobile_classes . ' ' . ( $desktop_classes[ $index ] ?? '' )
					);
					?>

					<?php
					$full_image_url = wp_get_attachment_image_url( $image_id, 'full' );
					$image_alt      = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

					if ( ! $full_image_url ) {
						continue;
					}
					?>

					<figure
						class="<?php echo esc_attr( $image_classes ); ?> relative"
					>
						<button
							class="advisor-lightbox-trigger block w-full cursor-zoom-in text-left focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-secondary focus-visible:ring-offset-4"
							type="button"
							data-lightbox-image="<?php echo esc_url( $full_image_url ); ?>"
							data-lightbox-alt="<?php echo esc_attr( $image_alt ); ?>"
							aria-label="<?php esc_attr_e( 'View larger image', 'prelaunch-wp' ); ?>"
						>
							<span
								class="block bg-white p-2 pb-6 shadow-xl transition-shadow hover:shadow-2xl md:p-3 md:pb-9">
								<?php
									echo wp_get_attachment_image(
										$image_id,
										'large',
										false,
										[
											'class'    => 'aspect-square h-full w-full object-cover',
											'loading'  => 'lazy',
											'decoding' => 'async',
											'sizes'    => '(min-width: 768px) 33vw, 50vw',
										]
									);
								?>
							</span>
						</button>
					</figure>

				<?php endforeach; ?>

			</div>
		</div>

		<dialog
			class="advisor-lightbox"
			aria-label="<?php esc_attr_e( 'Expanded travel gallery image', 'prelaunch-wp' ); ?>"
		>
			<div class="advisor-lightbox__inner">

				<button
					class="advisor-lightbox__close"
					type="button"
					aria-label="<?php esc_attr_e( 'Close image', 'prelaunch-wp' ); ?>"
				>
					<i class="fa-solid fa-xmark" aria-hidden="true"></i>
				</button>

				<img
					class="advisor-lightbox__image"
					src=""
					alt=""
				/>

			</div>
		</dialog>

	</div>
</section>
