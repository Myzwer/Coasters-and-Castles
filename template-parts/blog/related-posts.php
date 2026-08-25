<?php
	/**
	 * Related posts section for a single blog post.
	 *
	 * Uses the theme's related-posts query helper and existing blog card
	 * template part.
	 */

	$related_query = function_exists( 'prelaunch_get_related_posts_query' )
		? prelaunch_get_related_posts_query( get_the_ID(), [
			'posts_per_page' => 4,
		] )
		: null;

	if ( ! $related_query || ! $related_query->have_posts() ) {
		return;
	}
?>

<section class="mt-10 mb-10" aria-label="<?php esc_attr_e( 'Related articles', 'prelaunch-wp' ); ?>">
	<h2 class="mb-6 text-xl font-semibold">
		<?php esc_html_e( 'Related Articles', 'prelaunch-wp' ); ?>
	</h2>

	<div class="grid-12">
		<?php while ( $related_query->have_posts() ) : ?>
			<?php $related_query->the_post(); ?>

			<div class="col-span-12 md:col-span-6">
				<?php get_template_part( 'template-parts/blog/card' ); ?>
			</div>
		<?php endwhile; ?>
	</div>
</section>

<?php wp_reset_postdata(); ?>
