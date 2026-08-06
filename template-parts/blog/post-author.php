<?php

	/**
	 * Blog post author panel.
	 *
	 * Displays either:
	 *
	 * - Content from the Advisor profile linked to the post author.
	 * - Global Coasters & Castles Travel Team content when no Advisor profile
	 *   is linked to the post author.
	 *
	 * Both author types use the same visual structure.
	 *
	 * @var array $args Template-part arguments.
	 */

	$advisor_id = isset( $args['advisor_id'] )
		? (int) $args['advisor_id']
		: 0;

	$author_data = prelaunch_get_post_author_panel_data(
		get_the_ID(),
		$advisor_id
	);

	$author_name     = $author_data['name'];
	$author_image_id = $author_data['image_id'];
	$author_bio      = $author_data['bio'];
	$primary_link    = $author_data['primary_link'];
	$secondary_link  = $author_data['secondary_link'];

	if ( ! $author_name ) {
		return;
	}
?>

<section
	class="grid-12 items-center mt-10 p-6 md:p-8 bg-white border-3 border-secondary rounded-xl"
	aria-labelledby="post-author-heading"
>
	<?php if ( $author_image_id ) : ?>
		<div class="col-span-12 md:col-span-4 lg:col-span-3">
			<?php
				echo wp_get_attachment_image(
					$author_image_id,
					'medium',
					false,
					[
						'class'   => 'w-full aspect-square object-cover object-[50%_10%] rounded-xl',
						'loading' => 'lazy',
						'alt'     => '',
					]
				);
			?>
		</div>
	<?php endif; ?>

	<div
		class="col-span-12 <?php echo $author_image_id
			? 'md:col-span-8 lg:col-span-9'
			: ''; ?>"
	>
		<p class="mb-1 text-sm font-semibold uppercase tracking-wide">
			<?php esc_html_e( 'Written By', 'prelaunch-wp' ); ?>
		</p>

		<h2
			id="post-author-heading"
			class="mb-3 text-2xl font-semibold"
		>
			<?php echo esc_html( $author_name ); ?>
		</h2>

		<?php if ( $author_bio ) : ?>
			<p class="mb-5">
				<?php echo esc_html( $author_bio ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $primary_link['url'] || $secondary_link['url'] ) : ?>
			<div class="flex flex-wrap gap-3">
				<?php if ( $primary_link['url'] ) : ?>
					<a
						class="btn_main"
						href="<?php echo esc_url(
							$primary_link['url']
						); ?>"
						<?php if ( $primary_link['target'] ) : ?>
							target="<?php echo esc_attr(
								$primary_link['target']
							); ?>"
						<?php endif; ?>
						<?php if ( '_blank' === $primary_link['target'] ) : ?>
							rel="noopener noreferrer"
						<?php endif; ?>
					>
						<?php
							echo esc_html(
								$primary_link['title']
									?: __( 'Learn More', 'prelaunch-wp' )
							);
						?>
					</a>
				<?php endif; ?>

				<?php if ( $secondary_link['url'] ) : ?>
					<a
						class="btn_ghost_black"
						href="<?php echo esc_url(
							$secondary_link['url']
						); ?>"
						<?php if ( $secondary_link['target'] ) : ?>
							target="<?php echo esc_attr(
								$secondary_link['target']
							); ?>"
						<?php endif; ?>
						<?php if ( '_blank' === $secondary_link['target'] ) : ?>
							rel="noopener noreferrer"
						<?php endif; ?>
					>
						<?php
							echo esc_html(
								$secondary_link['title']
									?: __( 'About Our Team', 'prelaunch-wp' )
							);
						?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
