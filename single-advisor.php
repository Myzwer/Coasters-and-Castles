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
			?>

			<main id="primary">
				<?php
					get_template_part( 'template-parts/advisors/_header' );
					get_template_part( 'template-parts/advisors/_bio' );
					get_template_part( 'template-parts/advisors/_expertise' );
					get_template_part( 'template-parts/advisors/_reviews' );
					get_template_part( 'template-parts/advisors/_articles' );
					get_template_part( 'template-parts/advisors/_gallery' );
					get_template_part( 'template-parts/advisors/_cta' );
				?>
			</main>

		<?php
		endwhile;
	endif;

	get_footer();
