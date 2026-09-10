<?php

	/**
	 * Advisor travel gallery.
	 *
	 * Displays between two and six advisor travel images in a controlled
	 * scrapbook-style arrangement. Each photo may include an optional
	 * polaroid caption (photo_title), matching the List Block pattern.
	 *
	 * Background alternation and texture are handled by single-advisor.php.
	 *
	 * Expected fields:
	 * - advisor_gallery: Repeater (max 6)
	 *   - photo: Image (ID)
	 *   - photo_title: Text (optional)
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id = get_the_ID();
	$gallery    = get_field( 'advisor_gallery', $advisor_id );

	$gallery = is_array( $gallery ) ? $gallery : [];

	/*
	 * Normalize repeater rows and legacy gallery ID lists into a consistent
	 * shape: [ 'photo' => int, 'photo_title' => string ].
	 */
	$gallery = array_values(
		array_filter(
			array_map(
				static function ( $row ) {
					if ( is_array( $row ) ) {
						$image = $row['photo'] ?? null;
						$title = isset( $row['photo_title'] ) ? (string) $row['photo_title'] : '';

						if ( is_array( $image ) ) {
							$image_id = absint( $image['ID'] ?? $image['id'] ?? 0 );
						} else {
							$image_id = absint( $image );
						}

						if ( ! $image_id ) {
							return null;
						}

						return [
							'photo'       => $image_id,
							'photo_title' => $title,
						];
					}

					/*
					 * Legacy gallery storage: bare attachment IDs.
					 */
					$image_id = absint( $row );

					if ( ! $image_id ) {
						return null;
					}

					return [
						'photo'       => $image_id,
						'photo_title' => '',
					];
				},
				$gallery
			)
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

				<?php foreach ( $gallery as $index => $item ) : ?>
					<?php
					$image_id    = (int) ( $item['photo'] ?? 0 );
					$photo_title = (string) ( $item['photo_title'] ?? '' );

					if ( ! $image_id ) {
						continue;
					}

					$full_image_url = wp_get_attachment_image_url( $image_id, 'full' );
					$image_alt      = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

					if ( ! $full_image_url ) {
						continue;
					}

					if ( '' === $image_alt && $photo_title ) {
						$image_alt = $photo_title;
					}

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
								class="block bg-white p-2 pb-8 shadow-xl transition-shadow hover:shadow-2xl md:p-3 md:pb-10">
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

								<?php if ( $photo_title ) : ?>
									<span class="mt-2 block px-1 text-center font-display text-sm italic text-black md:text-base">
										<?php echo esc_html( $photo_title ); ?>
									</span>
								<?php endif; ?>
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
