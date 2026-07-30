<?php
	/**
	 * Single post template.
	 *
	 * Renders the primary single-post layout and delegates blog-specific
	 * sections to reusable template parts.
	 *
	 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
	 */

	get_header();
?>

<main id="primary" class="site-main bg-secondary-gradient">
	<section class="section py-10">
		<div class="wrap">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : ?>
					<?php
					the_post();

					// QUERY: Find the published Advisor profile linked to this post author.
					$advisor_ids = get_posts( [
						'post_type'      => 'advisor',
						'post_status'    => 'publish',
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'no_found_rows'  => true,
						'meta_query'     => [
							[
								'key'     => 'advisor_linked_user',
								'value'   => (int) get_the_author_meta( 'ID' ),
								'compare' => '=',
								'type'    => 'NUMERIC',
							],
						],
					] );

					$advisor_id = ! empty( $advisor_ids ) ? (int) $advisor_ids[0] : 0;
					?>

					<article <?php post_class(); ?>>
						<?php
							get_template_part( 'template-parts/blog/post-header', null, [
								'advisor_id' => $advisor_id,
							] );
						?>

						<div class="grid-12">
							<?php if ( has_post_thumbnail() ) : ?>
								<figure class="col-span-12 md:col-span-8 md:col-start-3 mb-8 blog-featured-image">
									<?php
										the_post_thumbnail( 'blog-featured', [
											'class'   => 'blog-featured-image__image',
											'loading' => 'eager',
										] );
									?>
								</figure>
							<?php endif; ?>

							<section
								class="col-span-12 md:col-span-8 md:col-start-3 prose-theme blog-content"
								aria-label="<?php esc_attr_e( 'Post content', 'prelaunch-wp' ); ?>"
							>
								<?php the_content(); ?>
							</section>

							<footer class="col-span-12">
								<?php
									get_template_part( 'template-parts/blog/post-author', null, [
										'advisor_id' => $advisor_id,
									] );

									get_template_part( 'template-parts/blog/related-posts' );
								?>
							</footer>
						</div>
					</article>
				<?php endwhile; ?>
			<?php else : ?>
				<div class="p-6 bg-white border-3 rounded-xl">
					<p class="text-base font-medium">
						<?php esc_html_e( 'Nothing found.', 'prelaunch-wp' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php get_footer(); ?>
