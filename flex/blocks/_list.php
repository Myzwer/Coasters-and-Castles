<?php
	/**
	 * List content block.
	 *
	 * Renders intro copy, an optional polaroid photo row, titled list pills,
	 * and optional primary/secondary CTAs.
	 *
	 * Used in:
	 * - service lists
	 * - feature highlights
	 * - informational bullet sections
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Expected fields:
	 * - intro: WYSIWYG
	 * - photos: Repeater (max 3)
	 *   - photo: Image
	 *   - photo_title: Text
	 * - list_items: Repeater
	 *   - list_item_title: Text
	 * - link: Link, optional primary CTA
	 * - secondary_link: Link, optional secondary CTA
	 *
	 * Notes:
	 * - Intro alignment is left to the WYSIWYG editor, not forced in code.
	 * - Photos cap at three, hide the third on mobile, and skip the gallery
	 *   entirely when none are selected.
	 * - Photos open in the same native lightbox used by advisor galleries.
	 * - List items are title-only pills.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$intro          = get_sub_field( 'intro' );
	$link           = get_sub_field( 'link' );
	$secondary_link = get_sub_field( 'secondary_link' );

	$photos = get_sub_field( 'photos' );
	$photos = is_array( $photos ) ? $photos : [];

	$photos = array_values(
		array_filter(
			$photos,
			static function ( $row ) {
				$image = $row['photo'] ?? null;

				if ( is_array( $image ) ) {
					return ! empty( $image['ID'] ) || ! empty( $image['url'] );
				}

				return ! empty( $image );
			}
		)
	);

	$photos      = array_slice( $photos, 0, 3 );
	$photo_count = count( $photos );

	$desktop_layouts = [
		1 => [
			'md:col-span-4 md:col-start-5 md:-rotate-2',
		],
		2 => [
			'md:col-span-5 md:col-start-2 md:-rotate-3',
			'md:col-span-5 md:col-start-7 md:rotate-2',
		],
		3 => [
			'md:col-span-4 md:-rotate-3',
			'md:col-span-4 md:rotate-2',
			'md:col-span-4 md:-rotate-1',
		],
	];

	$desktop_classes = $desktop_layouts[ $photo_count ] ?? [];

	$get_image_id = static function ( $image ): int {
		if ( is_array( $image ) ) {
			return absint( $image['ID'] ?? 0 );
		}

		return absint( $image );
	};
?>

<section class="py-10 wrap">
	<div class="grid-12 gap-y-10">

		<?php if ( $intro ) : ?>
			<div class="col-span-12">
				<div class="prose-theme">
					<?php echo wp_kses_post( $intro ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $photo_count ) : ?>
			<div class="col-span-12">
				<div class="grid grid-cols-2 gap-4 md:grid-cols-12 md:gap-x-5 md:gap-y-10 md:py-4">

					<?php foreach ( $photos as $index => $photo ) : ?>
						<?php
						$image_id    = $get_image_id( $photo['photo'] ?? null );
						$photo_title = isset( $photo['photo_title'] ) ? (string) $photo['photo_title'] : '';

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

						if ( 2 === $index ) {
							$mobile_classes = 'hidden md:block';
						} elseif ( 1 === $photo_count ) {
							$mobile_classes = 'col-span-2 mx-auto w-1/2 md:mx-0 md:w-auto';
						}

						$image_classes = trim(
							$mobile_classes . ' ' . ( $desktop_classes[ $index ] ?? '' )
						);
						?>

						<figure class="<?php echo esc_attr( $image_classes ); ?> relative">
							<button
								class="advisor-lightbox-trigger block w-full cursor-zoom-in text-left focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-secondary focus-visible:ring-offset-4"
								type="button"
								data-lightbox-image="<?php echo esc_url( $full_image_url ); ?>"
								data-lightbox-alt="<?php echo esc_attr( $image_alt ); ?>"
								aria-label="<?php esc_attr_e( 'View larger image', 'prelaunch-wp' ); ?>"
							>
								<span class="block bg-white p-2 pb-8 shadow-xl transition-shadow hover:shadow-2xl md:p-3 md:pb-10">
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
				aria-label="<?php esc_attr_e( 'Expanded image', 'prelaunch-wp' ); ?>"
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
		<?php endif; ?>

		<?php
		$list_items = get_sub_field( 'list_items' );
		$list_items = is_array( $list_items ) ? $list_items : [];
		$list_items = array_values(
			array_filter(
				$list_items,
				static function ( $row ) {
					return ! empty( $row['list_item_title'] );
				}
			)
		);
		$list_count = count( $list_items );
		?>

		<?php if ( $list_count ) : ?>
			<div class="col-span-12">
				<div class="grid grid-cols-12 gap-3 md:gap-4">

					<?php foreach ( $list_items as $index => $item ) : ?>
						<?php
						$is_last_odd = ( 1 === $list_count % 2 && $index === $list_count - 1 );
						$item_classes = 'col-span-12 md:col-span-6 grid min-h-14 place-items-center rounded-xl border-3 border-secondary bg-white px-5 py-3 text-center shadow-lg';

						if ( $is_last_odd ) {
							$item_classes .= ' md:col-start-4';
						}
						?>

						<article class="<?php echo esc_attr( $item_classes ); ?>">
							<p class="m-0 text-base font-semibold leading-snug md:text-lg">
								<?php echo esc_html( $item['list_item_title'] ); ?>
							</p>
						</article>
					<?php endforeach; ?>

				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $link['url'] ) || ! empty( $secondary_link['url'] ) ) : ?>
			<div class="col-span-12 mt-2 flex flex-wrap items-center justify-center gap-4">
				<?php if ( ! empty( $link['url'] ) ) : ?>
					<a
						class="btn_main"
						href="<?php echo esc_url( $link['url'] ); ?>"
						<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener noreferrer"' : ''; ?>
					>
						<span><?php echo esc_html( $link['title'] ?: 'Learn More' ); ?></span>
					</a>
				<?php endif; ?>

				<?php if ( ! empty( $secondary_link['url'] ) ) : ?>
					<a
						class="btn_ghost_black"
						href="<?php echo esc_url( $secondary_link['url'] ); ?>"
						<?php echo ! empty( $secondary_link['target'] ) ? ' target="' . esc_attr( $secondary_link['target'] ) . '" rel="noopener noreferrer"' : ''; ?>
					>
						<span><?php echo esc_html( $secondary_link['title'] ?: 'Learn More' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>
</section>
