<?php
	/**
	 * Video content block.
	 *
	 * Renders an embedded video with supporting text above and below.
	 *
	 * Used in:
	 * - promotional videos
	 * - tutorials or walkthroughs
	 * - sermon or media embeds
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Notes:
	 * - Video is an ACF oEmbed field (stored as a URL, formatted as iframe HTML).
	 * - When oEmbed returns a fallback link or mangled URL instead of an iframe,
	 *   the block builds a YouTube/Vimeo iframe from the stored URL.
	 * - Supporting text fields use WYSIWYG editors.
	 * - Headers should not use H1 to preserve page SEO structure.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! function_exists( 'prelaunch_get_flex_video_embed_html' ) ) {
		/**
		 * Return iframe markup for an ACF oEmbed value.
		 *
		 * @param mixed $formatted Formatted oEmbed value (HTML, link, or leftover URL).
		 * @param mixed $raw       Unformatted stored URL.
		 */
		function prelaunch_get_flex_video_embed_html( mixed $formatted, mixed $raw ): string {
			$allowed_iframe = array(
				'iframe' => array(
					'src'             => true,
					'title'           => true,
					'width'           => true,
					'height'          => true,
					'frameborder'     => true,
					'allow'           => true,
					'allowfullscreen' => true,
					'referrerpolicy'  => true,
					'loading'         => true,
				),
			);

			if ( is_string( $formatted ) && str_contains( $formatted, '<iframe' ) ) {
				return wp_kses( $formatted, $allowed_iframe );
			}

			$candidates = array();

			if ( is_string( $raw ) && $raw !== '' ) {
				$candidates[] = $raw;
			}

			if ( is_string( $formatted ) && $formatted !== '' ) {
				$candidates[] = $formatted;
			}

			$src = '';

			foreach ( $candidates as $candidate ) {
				$decoded = html_entity_decode( urldecode( str_replace( '+', ' ', $candidate ) ), ENT_QUOTES, 'UTF-8' );

				if ( preg_match( '#(?:youtube\.com/watch\?v=|youtube\.com/embed/|youtube\.com/shorts/|youtu\.be/)([A-Za-z0-9_-]{11})#i', $decoded, $matches ) ) {
					$src = 'https://www.youtube.com/embed/' . rawurlencode( $matches[1] );
					break;
				}

				if ( preg_match( '#vimeo\.com/(?:video/)?([0-9]+)#i', $decoded, $matches ) ) {
					$src = 'https://player.vimeo.com/video/' . rawurlencode( $matches[1] );
					break;
				}
			}

			if ( $src === '' ) {
				return '';
			}

			$html = sprintf(
				'<iframe src="%s" title="%s" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>',
				esc_url( $src ),
				esc_attr__( 'Embedded video', 'prelaunch-wp' )
			);

			return wp_kses( $html, $allowed_iframe );
		}
	}

	$intro      = get_sub_field( 'intro' );
	$video_html = get_sub_field( 'video' );
	$video_url  = get_sub_field( 'video', false );
	$video      = prelaunch_get_flex_video_embed_html( $video_html, $video_url );
	$content    = get_sub_field( 'content' );
?>
<section class="py-10 wrap">
	<div class="grid-12">
		<div class="col-span-12">
			<?php if ( $intro ) : ?>
				<div class="prose-theme">
					<?php echo wp_kses_post( $intro ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="col-span-12">
			<?php if ( $video ) : ?>
				<div class="video-container">
					<?php echo $video; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="col-span-12">
			<?php if ( $content ) : ?>
				<div class="prose-theme">
					<?php echo wp_kses_post( $content ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
