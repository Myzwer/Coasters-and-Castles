<?php
	/**
	 * Page Builder Template
	 *
	 * Dynamically renders page headers and body sections from ACF Flexible Content.
	 *
	 * Conventions:
	 * - ACF layout names must match the corresponding template partial.
	 * - Layout suffixes are removed and underscores become hyphens.
	 * - Example: `text_block` → `flex/blocks/_text.php`
	 *
	 * Body blocks are output-buffered so empty sections do not render wrappers.
	 * This prevents empty blocks from affecting background alternation.
	 *
	 * Related:
	 * README → Flexible Content Blocks
	 */

	get_header();

	/**
	 * Convert an ACF layout name into a template partial path.
	 *
	 * Removes the expected suffix and converts underscores to hyphens.
	 */
	function prelaunch_get_flex_template_path( string $layout_slug, string $suffix, string $base_path ): string {

		$template_slug = preg_replace( '/' . preg_quote( $suffix, '/' ) . '$/', '', $layout_slug );
		$template_slug = str_replace( '_', '-', $template_slug );

		return $base_path . '_' . $template_slug;

	}

?>

<?php
	if ( have_rows( 'header_select' ) ) :

		while ( have_rows( 'header_select' ) ) :
			the_row();

			$layout = get_row_layout();
			$path   = prelaunch_get_flex_template_path( $layout, '_header', 'flex/headers/' );

			// Only render if the template exists.
			if ( locate_template( $path . '.php', false, false ) ) {
				get_template_part( $path );
			} else {

				error_log( 'Missing header template: ' . $layout . ' → ' . $path . '.php' );

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					echo '<div style="padding:1rem;border:2px dashed red;margin:1rem 0;">';
					echo '<strong>Missing header template:</strong> ' . esc_html( $layout );
					echo '</div>';
				}

			}

		endwhile;

	endif;
?>

<?php
	if ( have_rows( 'body_sections' ) ) :

		$background_index = 0;

		$background_excluded_layouts = array(
			'announcement_block',
		);

		$textured_image     = function_exists( 'get_field' ) ? get_field( 'textured_image', 'option' ) : '';
		$textured_image_url = '';

		if ( is_array( $textured_image ) && ! empty( $textured_image['url'] ) ) {
			$textured_image_url = $textured_image['url'];
		} elseif ( is_string( $textured_image ) ) {
			$textured_image_url = $textured_image;
		}

		echo '<div class="alt-bg-wrap">';

		while ( have_rows( 'body_sections' ) ) :
			the_row();

			$layout = get_row_layout();
			$path   = prelaunch_get_flex_template_path( $layout, '_block', 'flex/blocks/' );

			if ( ! locate_template( $path . '.php', false, false ) ) {

				error_log( 'Missing content block template: ' . $layout . ' → ' . $path . '.php' );

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					echo '<div style="padding:1rem;border:2px dashed red;margin:1rem 0;">';
					echo '<strong>Missing block template:</strong> ' . esc_html( $layout );
					echo '</div>';
				}

				continue;
			}

			ob_start();
			get_template_part( $path );
			$markup = trim( ob_get_clean() );

			if ( '' === $markup ) {
				continue;
			}

			$skip_background_alternation = in_array( $layout, $background_excluded_layouts, true );

			if ( $skip_background_alternation ) {
				echo '<div class="bg-alternating-skip" data-layout="' . esc_attr( $layout ) . '">';
				echo $markup; // safe: rendered template markup
				echo '</div>';

				continue;
			}

			$background_index ++;

			$is_even_background = 0 === $background_index % 2;

			$background_class = $is_even_background
				? 'bg-alternating-gradient bg-alternating-even'
				: 'bg-alternating-gradient bg-alternating-odd';

			$background_style = '';

			/*
			 * Apply the global texture only to the first/blue alternating sections.
			 *
			 * Important:
			 * - This runs after empty blocks are skipped.
			 * - Excluded layouts do not increment $background_index.
			 * - That means texture application follows the same alternation logic
			 *   as the background colors.
			 */
			if ( ! $is_even_background && $textured_image_url ) {
				$background_class .= ' bg-texture';
				$background_style = ' style="--bg-texture: url(\'' . esc_url( $textured_image_url ) . '\');"';
			}

			echo '<div class="' . esc_attr( $background_class ) . '" data-layout="' . esc_attr( $layout ) . '"' . $background_style . '>';
			echo $markup; // safe: rendered template markup
			echo '</div>';

		endwhile;

		echo '</div>';

	endif;
?>

<?php get_footer(); ?>
