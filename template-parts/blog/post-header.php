<?php
	/**
	 * Single post header.
	 *
	 * Displays article classifications, post title, publication date,
	 * reading time, and the linked advisor byline when one exists.
	 *
	 * @var array $args Template-part arguments.
	 */

	$advisor_id = isset( $args['advisor_id'] )
		? (int) $args['advisor_id']
		: 0;

	$is_advisor_author = $advisor_id > 0;

	$author_name = $is_advisor_author
		? get_the_title( $advisor_id )
		: prelaunch_get_travel_team_name();

	$author_url = $is_advisor_author
		? get_permalink( $advisor_id )
		: '';

	$trip_types    = get_the_terms( get_the_ID(), 'trip_type' );
	$article_types = get_the_terms( get_the_ID(), 'article_type' );

	$trip_type = (
		! is_wp_error( $trip_types )
		&& ! empty( $trip_types )
	)
		? $trip_types[0]
		: null;

	$article_type = (
		! is_wp_error( $article_types )
		&& ! empty( $article_types )
	)
		? $article_types[0]
		: null;

	$reading_time = function_exists( 'prelaunch_get_reading_time' )
		? prelaunch_get_reading_time( get_the_ID() )
		: '';
?>

<header class="mb-8">
	<?php if ( $trip_type || $article_type ) : ?>
		<nav
			class="mb-4 text-center"
			aria-label="<?php esc_attr_e( 'Article classifications', 'prelaunch-wp' ); ?>"
		>
			<ul class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm font-medium tracking-wide uppercase text-black/70">
				<?php if ( $trip_type ) : ?>
					<li>
						<a
							class="hover:text-black hover:underline underline-offset-4"
							href="<?php echo esc_url( get_term_link( $trip_type ) ); ?>"
						>
							<?php echo esc_html( $trip_type->name ); ?>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( $trip_type && $article_type ) : ?>
					<li class="text-black/40" aria-hidden="true">/</li>
				<?php endif; ?>

				<?php if ( $article_type ) : ?>
					<li>
						<a
							class="hover:text-black hover:underline underline-offset-4"
							href="<?php echo esc_url( get_term_link( $article_type ) ); ?>"
						>
							<?php echo esc_html( $article_type->name ); ?>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		</nav>
	<?php endif; ?>

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

		<?php if ( $author_name ) : ?>
			<span aria-hidden="true"> · </span>

			<span>
		<?php esc_html_e( 'Written by', 'prelaunch-wp' ); ?>

				<?php if ( $author_url ) : ?>
					<a
						class="font-semibold underline underline-offset-2"
						href="<?php echo esc_url( $author_url ); ?>"
					>
				<?php echo esc_html( $author_name ); ?>
			</a>
				<?php else : ?>
					<span class="font-semibold">
				<?php echo esc_html( $author_name ); ?>
			</span>
				<?php endif; ?>
	</span>
		<?php endif; ?>
	</p>
</header>
