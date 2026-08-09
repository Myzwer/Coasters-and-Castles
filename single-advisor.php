<?php

	/**
	 * Single Advisor Template
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	get_header();

	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();

			/*
			 * Global texture used on primary-gradient sections.
			 */
			$textured_image     = get_field( 'textured_image', 'option' );
			$textured_image_url = '';

			if ( is_array( $textured_image ) && ! empty( $textured_image['url'] ) ) {
				$textured_image_url = $textured_image['url'];
			} elseif ( is_string( $textured_image ) ) {
				$textured_image_url = $textured_image;
			}

			/*
			 * Advisor body sections in their intended display order.
			 *
			 * Empty partials are skipped and do not affect background alternation.
			 */
			$advisor_sections = [
				'template-parts/advisors/_bio',
				'template-parts/advisors/_expertise',
				'template-parts/advisors/_specialized-planning',
				'template-parts/advisors/_reviews',
				'template-parts/advisors/_articles',
				'template-parts/advisors/_gallery',
				'template-parts/advisors/_cta',
			];

			$background_index = 0;
			?>

			<main id="primary">

				<?php get_template_part( 'template-parts/advisors/_header' ); ?>

				<div class="alt-bg-wrap">

					<?php
						foreach ( $advisor_sections as $section_path ) {
							ob_start();

							get_template_part( $section_path );

							$markup = trim( ob_get_clean() );

							/*
							 * The partial returned no meaningful markup.
							 * Skip it without incrementing the background counter.
							 */
							if ( '' === $markup ) {
								continue;
							}

							$background_index ++;

							$is_even_background = 0 === $background_index % 2;

							$background_class = $is_even_background
								? 'bg-alternating-gradient bg-alternating-even'
								: 'bg-alternating-gradient bg-alternating-odd';

							$background_style = '';

							/*
							 * Add texture only to primary-gradient sections.
							 */
							if ( ! $is_even_background && $textured_image_url ) {
								$background_class .= ' bg-texture';
								$background_style = ' style="--bg-texture: url(\''
													. esc_url( $textured_image_url )
													. '\');"';
							}

							echo '<div class="' . esc_attr( $background_class ) . '"' . $background_style . '>';
							echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '</div>';
						}
					?>

				</div>

			</main>

		<?php
		endwhile;
	endif;

	get_footer();
