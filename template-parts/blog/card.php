<?php

	/**
	 * Blog post card component.
	 *
	 * Renders a single post preview card for use in:
	 * - blog index
	 * - archive views
	 * - search results
	 * - related posts
	 *
	 * Relies on the global $post context provided by the loop.
	 *
	 * Optional $args:
	 * - variant (string) Card usage variant (e.g. 'blog', 'related').
	 *   Defaults to 'blog' if not provided.
	 *
	 * Notes:
	 * - Styling is controlled via `.card` base styles and variant classes.
	 * - Markup is intentionally stable; visual changes should be handled in CSS.
	 */


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

?>

<article class="card card--blog">
	<?php if ( has_post_thumbnail() ) : ?>

		<a href="<?php the_permalink(); ?>" class="card__media">
			<?php
				the_post_thumbnail( 'medium_large', [
					'class'   => 'card__image',
					'loading' => 'lazy',
				] );
			?>
		</a>

	<?php else : ?>

		<a href="<?php the_permalink(); ?>" class="card__media card__media--fallback bg-white">
		<span class="card__media-icon" aria-hidden="true">
			<i class="fa-regular fa-file-lines"></i>
		</span>
			<span class="sr-only">
			<?php esc_html_e( 'View post', 'prelaunch-wp' ); ?>
		</span>
		</a>

	<?php endif; ?>


	<div class="card__body">
		<?php if ( $trip_type || $article_type ) : ?>
			<nav
				class="card__cat"
				aria-label="<?php esc_attr_e( 'Article classifications', 'prelaunch-wp' ); ?>"
			>
				<ul class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-medium tracking-wide uppercase text-black/70">
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

		<h2 class="card__title">
			<a href="<?php the_permalink(); ?>">
				<?php the_title(); ?>
			</a>
		</h2>

		<div class="card__meta">
			<?php
				echo prelaunch_display_date();

				echo ' - ';

				if ( function_exists( 'prelaunch_get_reading_time' ) ) {
					echo '<span class="post-reading-time">'
						 . esc_html( prelaunch_get_reading_time() )
						 . '</span>';
				}
			?>
		</div>

		<div class="card__content">
			<?php
				echo wp_kses_post(
					function_exists( 'prelaunch_get_excerpt' )
						? prelaunch_get_excerpt()
						: get_the_excerpt()
				);
			?>
		</div>
	</div>

	<a
		class="grid place-items-center bg-secondary px-5 py-3 text-center font-bold text-white transition hover:brightness-90 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-white focus-visible:brightness-90"
		href="<?php the_permalink(); ?>"
	>
		<span class="inline-grid grid-flow-col items-center gap-2">
			<span>
				<?php esc_html_e( 'Read More', 'prelaunch-wp' ); ?>
			</span>

			<i
				class="fa-solid fa-arrow-right"
				aria-hidden="true"
			></i>
		</span>
	</a>
</article>
