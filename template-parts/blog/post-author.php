<?php
	/**
	 * Linked advisor author panel.
	 *
	 * The panel remains hidden when the post author has no published Advisor
	 * profile connected through the advisor_linked_user ACF field.
	 *
	 * @var array $args Template-part arguments.
	 */

	$advisor_id = isset( $args['advisor_id'] ) ? (int) $args['advisor_id'] : 0;

	if ( ! $advisor_id ) {
		return;
	}

	$advisor_name     = get_the_title( $advisor_id );
	$advisor_url      = get_permalink( $advisor_id );
	$advisor_headshot = (int) get_field( 'advisor_professional_headshot', $advisor_id );
	$advisor_bio      = (string) get_field( 'advisor_bio', $advisor_id );
	$advisor_excerpt  = $advisor_bio
		? wp_trim_words( wp_strip_all_tags( $advisor_bio ), 55, '&hellip;' )
		: '';

	if ( ! $advisor_name || ! $advisor_url ) {
		return;
	}
?>

<section
	class="grid-12 items-center mt-10 p-6 md:p-8 bg-white border-3 border-secondary rounded-xl"
	aria-labelledby="post-author-heading"
>
	<?php if ( $advisor_headshot ) : ?>
		<div class="col-span-12 md:col-span-4 lg:col-span-3">
			<?php
				echo wp_get_attachment_image( $advisor_headshot, 'medium', false, [
					'class'   => 'w-full aspect-square object-cover object-[50%_10%] rounded-xl',
					'loading' => 'lazy',
					'alt'     => '',
				] );
			?>
		</div>
	<?php endif; ?>

	<div class="col-span-12 <?php echo $advisor_headshot ? 'md:col-span-8 lg:col-span-9' : ''; ?>">
		<p class="mb-1 text-sm font-semibold uppercase tracking-wide">
			<?php esc_html_e( 'Written By', 'prelaunch-wp' ); ?>
		</p>

		<h2 id="post-author-heading" class="mb-3 text-2xl font-semibold">
			<?php echo esc_html( $advisor_name ); ?>
		</h2>

		<?php if ( $advisor_excerpt ) : ?>
			<p class="mb-5">
				<?php echo esc_html( $advisor_excerpt ); ?>
			</p>
		<?php endif; ?>

		<div class="flex flex-wrap gap-3">
			<a class="btn_main" href="<?php echo esc_url( home_url( '/booking/' ) ); ?>">
				<?php esc_html_e( 'Book With Me', 'prelaunch-wp' ); ?>
			</a>

			<a class="btn_ghost_black" href="<?php echo esc_url( $advisor_url ); ?>">
				<?php esc_html_e( 'View My Profile', 'prelaunch-wp' ); ?>
			</a>
		</div>
	</div>
</section>
