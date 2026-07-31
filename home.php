<?php
	/**
	 * Blog index (Posts page)
	 *
	 * Renders:
	 * - ACF-managed blog header
	 * - Horizontal Filter Everything controls
	 * - Active filter chips
	 * - Three-column post grid
	 * - Pagination
	 *
	 * ACF fields are loaded from the page assigned as the WordPress Posts page:
	 * - title
	 * - page_description
	 *
	 * Filter Everything AJAX replaces only the #blog-results container.
	 *
	 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#home-php
	 * @link https://developer.wordpress.org/themes/basics/the-loop/
	 */

	get_header();

	$posts_page_id = (int) get_option( 'page_for_posts' );

	$default_title = $posts_page_id
		? get_the_title( $posts_page_id )
		: __( 'Blog', 'prelaunch-wp' );

	$header_title       = $posts_page_id ? get_field( 'title', $posts_page_id ) : '';
	$page_description   = $posts_page_id ? get_field( 'page_description', $posts_page_id ) : '';
	$header_title       = $header_title ?: $default_title;
	$background_texture = get_template_directory_uri() . '/assets/public/img/waves.png';
?>

	<main>

		<header class="bg-impact-gradient">
			<div
				class="relative bg-no-repeat bg-cover bg-texture min-h-[22rem] md:min-h-[26rem]"
				style="--bg-texture: url('<?php echo esc_url( $background_texture ); ?>');"
			>
				<div class="px-5 text-center content-middle text-pretty">
					<div class="mx-auto max-w-4xl">

						<h1 class="text-3xl font-bold text-white uppercase md:text-5xl">
							<?php echo esc_html( $header_title ); ?>
						</h1>

						<?php if ( $page_description ) : ?>
							<div class="mx-auto mt-5 max-w-3xl text-lg leading-relaxed text-white md:text-xl">
								<?php echo wp_kses_post( wpautop( $page_description ) ); ?>
							</div>
						<?php endif; ?>

					</div>
				</div>
			</div>
		</header>

		<section class="py-12 bg-primary-gradient md:py-16">
			<div class="wrap">

				<div class="grid-12">

					<div class="col-span-12">
						<div class="p-6 bg-white rounded-xl border-3 border-secondary md:p-8">

							<header class="mb-6">
								<h2 class="text-2xl font-bold">
									<?php esc_html_e( 'Filter Options', 'prelaunch-wp' ); ?>
								</h2>

								<p class="mt-2">
									<?php
										esc_html_e(
											'Search for an article or narrow the articles by vacation and article type.',
											'prelaunch-wp'
										);
									?>
								</p>
							</header>

							<?php
								if ( shortcode_exists( 'fe_widget' ) ) {
									echo do_shortcode(
										'[fe_widget horizontal="yes" columns="3"]'
									);
								}
							?>

							<?php if ( shortcode_exists( 'fe_chips' ) ) : ?>
								<div class="mt-5">
									<?php echo do_shortcode( '[fe_chips]' ); ?>
								</div>
							<?php endif; ?>

						</div>
					</div>

					<div
						id="blog-results"
						class="col-span-12 mt-4"
					>

						<?php if ( have_posts() ) : ?>

							<div class="grid-12">
								<?php
									while ( have_posts() ) :
										the_post();
										?>
										<div class="col-span-12 md:col-span-6 lg:col-span-4">
											<?php get_template_part( 'template-parts/blog/card' ); ?>
										</div>
									<?php
									endwhile;
								?>
							</div>

							<div class="mt-10">
								<?php
									if ( function_exists( 'prelaunch_pagination' ) ) {
										prelaunch_pagination();
									} else {
										the_posts_pagination();
									}
								?>
							</div>

						<?php else : ?>

							<div class="p-8 text-center bg-white rounded-xl border-3 border-secondary">
								<h2 class="text-2xl font-semibold">
									<?php esc_html_e( 'No articles found', 'prelaunch-wp' ); ?>
								</h2>

								<p class="mt-3">
									<?php
										esc_html_e(
											'No articles matched those filters. Try removing a filter or searching for something broader.',
											'prelaunch-wp'
										);
									?>
								</p>
							</div>

						<?php endif; ?>

					</div>

				</div>
			</div>
		</section>

	</main>

<?php
	get_footer();
