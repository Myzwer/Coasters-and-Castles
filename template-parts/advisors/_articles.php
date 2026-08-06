<?php

	/**
	 * Advisor articles section.
	 *
	 * Displays the latest published blog posts written by the WordPress user
	 * linked to this Advisor profile.
	 *
	 * Background alternation and texture are handled by single-advisor.php.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id     = get_the_ID();
	$linked_user_id = absint( get_field( 'advisor_linked_user', $advisor_id ) );

	if ( ! $linked_user_id ) {
		return;
	}

	$advisor_articles = new WP_Query(
		[
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'author'              => $linked_user_id,
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		]
	);

	if ( ! $advisor_articles->have_posts() ) {
		wp_reset_postdata();

		return;
	}
?>

<section class="py-10 md:py-16 wrap">
	<div class="grid-12">

		<div class="col-span-12 text-center">
			<h2 class="heading-2">
				<?php esc_html_e( 'Articles', 'prelaunch-wp' ); ?>
			</h2>

			<p class="mx-auto mt-3 max-w-2xl text-lg">
				<?php
					esc_html_e(
						'Explore a few of my articles and see the experience, perspective, and travel knowledge I bring to every trip.',
						'prelaunch-wp'
					);
				?>
			</p>
		</div>

		<?php while ( $advisor_articles->have_posts() ) : ?>
			<?php $advisor_articles->the_post(); ?>

			<div class="col-span-12 mt-8 md:col-span-6 lg:col-span-4">
				<?php
					get_template_part(
						'template-parts/blog/card',
						null,
						[
							'variant' => 'advisor',
						]
					);
				?>
			</div>

		<?php endwhile; ?>

	</div>
</section>

<?php wp_reset_postdata(); ?>
