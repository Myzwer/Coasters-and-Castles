<?php
	/**
	 * Blog Callout Block
	 *
	 * Renders a highlighted blog post callout with an optional intro.
	 *
	 * ACF fields:
	 * - intro: WYSIWYG Editor
	 * - blog_select: Radio Button
	 *   - latest = Show the latest blog post
	 *   - select = Choose a specific post
	 * - blog_post_selection: Post Object
	 *
	 * Behavior:
	 * - If "latest" is selected, this block pulls the latest published post.
	 * - If "select" is selected, this block uses the selected post object.
	 * - The rendered layout is the same either way.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$intro               = get_sub_field( 'intro' );
	$blog_select         = get_sub_field( 'blog_select' ) ?: 'latest';
	$blog_post_selection = get_sub_field( 'blog_post_selection' );
	$selected_post       = null;

	if ( 'select' === $blog_select && $blog_post_selection ) {
		$selected_post = $blog_post_selection instanceof WP_Post
			? $blog_post_selection
			: get_post( $blog_post_selection );
	}

	if ( ! $selected_post ) {
		$latest_posts = get_posts(
			[
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 1,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			]
		);

		$selected_post = ! empty( $latest_posts ) ? $latest_posts[0] : null;
	}

	if ( ! $selected_post instanceof WP_Post ) {
		return;
	}

	$post_id        = $selected_post->ID;
	$post_title     = get_the_title( $post_id );
	$post_url       = get_permalink( $post_id );
	$posts_url      = get_permalink( (int) get_option( 'page_for_posts' ) )
		?: get_post_type_archive_link( 'post' )
		?: home_url( '/articles/' );
	$featured_image = get_post_thumbnail_id( $post_id );

	$post_excerpt = get_the_excerpt( $post_id );

	if ( empty( $post_excerpt ) ) {
		$post_excerpt = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
	}

	$post_excerpt = wp_trim_words( $post_excerpt, 38, '...' );
?>

<section class="py-16 wrap">
	<div class="grid-12 gap-y-10">

		<?php if ( $intro ) : ?>
			<div class="col-span-12 mx-auto max-w-4xl text-center mb-8">
				<div class="prose-theme">
					<?php echo wp_kses_post( $intro ); ?>
				</div>
			</div>
		<?php endif; ?>

		<article class="col-span-12">
			<div class="grid grid-cols-12 items-center gap-y-8 md:gap-x-12">

				<div class="col-span-12 md:col-span-6">
					<a
						class="block overflow-hidden rounded-xl shadow-lg focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-secondary focus-visible:ring-offset-4 focus-visible:ring-offset-soft-1"
						href="<?php echo esc_url( $post_url ); ?>"
						aria-label="<?php echo esc_attr( 'Read article: ' . $post_title ); ?>"
					>
						<?php if ( $featured_image ) : ?>
							<?php
							echo wp_get_attachment_image(
								$featured_image,
								'large',
								false,
								[
									'class'    => 'aspect-[16/9] w-full object-cover',
									'loading'  => 'lazy',
									'decoding' => 'async',
								]
							);
							?>
						<?php else : ?>
							<?php
							$image = get_field( 'fallback_image', 'option' );
							if ( ! empty( $image ) ): ?>
								<img src="<?php echo esc_url( $image['url'] ); ?>"
									 class="aspect-[16/9] w-full object-cover" alt="Blog Featured Image" />
							<?php endif; ?>

						<?php endif; ?>
					</a>
				</div>

				<div class="col-span-12 md:col-span-6">
					<div class="grid justify-items-start gap-5">
						<h3 class="heading-3">
							<a
								class="no-underline hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-4"
								href="<?php echo esc_url( $post_url ); ?>"
							>
								<?php echo esc_html( $post_title ); ?>
							</a>
						</h3>

						<?php if ( $post_excerpt ) : ?>
							<p class="text-xl leading-snug">
								<?php echo esc_html( $post_excerpt ); ?>
							</p>
						<?php endif; ?>

						<div class="grid grid-cols-1 gap-4 sm:inline-grid sm:grid-cols-2">
							<a
								class="btn_main"
								href="<?php echo esc_url( $post_url ); ?>"
							>
								<span>Read this article</span>
								<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
							</a>

							<a
								class="btn_ghost_black"
								href="<?php echo esc_url( $posts_url ); ?>"
							>
								<span>See all articles</span>
							</a>
						</div>
					</div>
				</div>

			</div>
		</article>

	</div>
</section>
