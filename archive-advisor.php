<?php
	/**
	 * Advisor archive.
	 *
	 * Renders:
	 * - ACF-managed archive header
	 * - Filter Everything controls
	 * - Active filter chips
	 * - Three-column advisor grid
	 * - Pagination
	 *
	 * ACF archive fields:
	 * - title
	 * - page_description
	 *
	 * Filter Everything AJAX replaces only the #advisor-results container.
	 *
	 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#custom-post-type
	 * @link https://developer.wordpress.org/themes/basics/the-loop/
	 */

	get_header();

	$post_type_object = get_post_type_object( 'advisor' );

	$default_title = $post_type_object
		? $post_type_object->labels->name
		: __( 'Advisors', 'prelaunch-wp' );

	/*
	 * Advisor archive header content.
	 *
	 * These fields are stored globally through ACF:
	 * - title
	 * - page_description
	 */
	$header_title = get_field( 'title', 'option' );

	$page_description = get_field(
		'page_description',
		'option'
	);

	$header_title = $header_title ?: $default_title;

	$background_texture = get_template_directory_uri()
						  . '/assets/public/img/waves.png';
?>

	<main id="main-content">

		<header class="bg-impact-gradient">
			<div
				class="relative min-h-[22rem] bg-cover bg-no-repeat bg-texture md:min-h-[26rem]"
				style="--bg-texture: url('<?php echo esc_url( $background_texture ); ?>');"
			>
				<div class="px-5 text-center content-middle text-pretty">
					<div class="mx-auto max-w-4xl">

						<h1 class="text-3xl font-bold text-white uppercase md:text-5xl">
							<?php echo esc_html( $header_title ); ?>
						</h1>

						<?php if ( $page_description ) : ?>
							<div class="mx-auto mt-5 max-w-3xl text-lg leading-relaxed text-white md:text-xl">
								<?php
									echo wp_kses_post(
										wpautop( $page_description )
									);
								?>
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
									<?php
										esc_html_e(
											'Find Your Advisor',
											'prelaunch-wp'
										);
									?>
								</h2>

								<p class="mt-2 max-w-2xl">
									<?php
										esc_html_e(
											'Search by name or narrow the directory by vacation and group type.',
											'prelaunch-wp'
										);
									?>
								</p>
							</header>

							<div>
								<?php
									if ( shortcode_exists( 'fe_widget' ) ) {
										echo do_shortcode( '[fe_widget]' );
									}
								?>
							</div>

							<?php if ( shortcode_exists( 'fe_chips' ) ) : ?>
								<div class="mt-5">
									<?php echo do_shortcode( '[fe_chips]' ); ?>
								</div>
							<?php endif; ?>

						</div>
					</div>

					<div
						id="advisor-results"
						class="col-span-12 filter-results"
					>

						<?php if ( have_posts() ) : ?>

							<div class="grid-12">
								<?php
									while ( have_posts() ) :
										the_post();
										?>
										<div class="col-span-12 md:col-span-6 lg:col-span-4">
											<?php
												get_template_part(
													'template-parts/advisors/card'
												);
											?>
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
									<?php
										esc_html_e(
											'No advisors found',
											'prelaunch-wp'
										);
									?>
								</h2>

								<p class="mt-3">
									<?php
										esc_html_e(
											'No advisors matched those filters. Try removing a filter or broadening your search.',
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
