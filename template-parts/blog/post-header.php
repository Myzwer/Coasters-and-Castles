<?php
	/**
	 * Single post header.
	 *
	 * Displays taxonomy links, post title, publication date, reading time,
	 * and the linked advisor byline when one exists.
	 *
	 * @var array $args Template-part arguments.
	 */

	$advisor_id   = isset( $args['advisor_id'] ) ? (int) $args['advisor_id'] : 0;
	$advisor_name = $advisor_id ? get_the_title( $advisor_id ) : '';
	$advisor_url  = $advisor_id ? get_permalink( $advisor_id ) : '';
	$categories   = get_the_category();
	$tags         = get_the_tags();
	$reading_time = function_exists( 'prelaunch_get_reading_time' )
		? prelaunch_get_reading_time( get_the_ID() )
		: '';
?>

<header class="mb-8">
	<div class="mx-auto mb-3 text-center">
		<?php if ( ! empty( $categories ) ) : ?>
			<nav aria-label="<?php esc_attr_e( 'Post categories', 'prelaunch-wp' ); ?>">
				<ul class="text-sm">
					<?php foreach ( $categories as $category ) : ?>
						<li class="inline-block mr-2 mb-2">
							<a
								class="inline-block py-1 px-3 text-black bg-soft-1 rounded-lg hover:shadow-md text-md"
								href="<?php echo esc_url( get_category_link( (int) $category->term_id ) ); ?>"
							>
								<?php echo esc_html( $category->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<?php if ( ! empty( $tags ) ) : ?>
			<nav class="pt-3 text-sm" aria-label="<?php esc_attr_e( 'Post tags', 'prelaunch-wp' ); ?>">
				<ul>
					<?php foreach ( $tags as $index => $tag ) : ?>
						<li class="inline-block">
							<?php if ( $index > 0 ) : ?>
								<span aria-hidden="true">, </span>
							<?php endif; ?>

							<a href="<?php echo esc_url( get_tag_link( (int) $tag->term_id ) ); ?>">
								<?php echo esc_html( $tag->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</div>

	<h1 class="text-3xl font-semibold text-center">
		<?php the_title(); ?>
	</h1>

	<p class="mx-auto mt-2 text-center">
		<?php
			if ( function_exists( 'prelaunch_display_date' ) ) {
				echo wp_kses_post( prelaunch_display_date() );
			} else {
				echo esc_html( get_the_date() );
			}
		?>

		<?php if ( $reading_time ) : ?>
			<span aria-hidden="true"> · </span>
			<span><?php echo esc_html( $reading_time ); ?></span>
		<?php endif; ?>

		<?php if ( $advisor_name && $advisor_url ) : ?>
			<span aria-hidden="true"> · </span>
			<span>
				<?php esc_html_e( 'Written by', 'prelaunch-wp' ); ?>
				<a class="font-semibold underline underline-offset-2" href="<?php echo esc_url( $advisor_url ); ?>">
					<?php echo esc_html( $advisor_name ); ?>
				</a>
			</span>
		<?php endif; ?>
	</p>
</header>
