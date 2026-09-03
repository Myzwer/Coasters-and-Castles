<?php
	/**
	 * Legacy Wix migration redirects — September 2026
	 *
	 * Launch-migration 301s from the old Wix URL structure to the rebuilt WordPress site.
	 * Keep the Redirection plugin free for post-launch one-offs, campaigns, and typos.
	 *
	 * Order of resolution:
	 * 1. Exact / special page + renamed-content mappings
	 * 2. Exact surviving article mappings (same or renamed slug)
	 * 3. Exact surviving advisor mappings
	 * 4. /post/* → matching post permalink, else /articles/
	 * 5. /team/* → matching advisor permalink, else /advisors/
	 *
	 * /blank is intentionally omitted (Wix test page).
	 */

	declare( strict_types=1 );

	defined( 'ABSPATH' ) || exit;

	/**
	 * Exact legacy path → new path mappings (no trailing slash on keys).
	 *
	 * @return array<string, string>
	 */
	function prelaunch_legacy_wix_exact_redirects(): array {
		return [
			// Priority standard / landing pages.
			'/how-to-become-a-travel-advisor'     => '/careers/',
			'/blue-diamond-resorts'               => '/all-inclusives/',
			'/ourteam'                           => '/advisors/',
			'/aboutus'                           => '/',
			'/msttwblog'                         => '/articles/',
			'/services'                          => '/',
			'/contactus'                         => '/booking/',
			'/termsconditions'                   => '/terms/',
			'/disney-destination-promotions'     => '/theme-parks/',
			'/ocean-cruises'                     => '/cruises/',
			'/theme-park'                        => '/theme-parks/',
			'/all-inclusive-resorts-recommended' => '/all-inclusives/',

			// Surviving articles with unchanged slugs.
			'/post/safe-sound-the-2026-guide-to-european-travel-safety'         => '/articles/safe-sound-the-2026-guide-to-european-travel-safety/',
			'/post/5-simple-tips-to-avoid-and-manage-theme-park-meltdowns'      => '/articles/5-simple-tips-to-avoid-and-manage-theme-park-meltdowns/',
			'/post/the-disney-sweet-spot-what-is-the-perfect-age-to-take-your-kids' => '/articles/the-disney-sweet-spot-what-is-the-perfect-age-to-take-your-kids/',

			// Surviving article with a renamed slug.
			'/post/disney-s-lightning-lane-premier-pass' => '/articles/is-disneys-lightning-lane-premier-pass-worth-the-cost/',
		];
	}

	/**
	 * Normalize the request path for legacy redirect matching.
	 */
	function prelaunch_legacy_wix_request_path(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

		if ( ! is_string( $path ) || '' === $path ) {
			return '';
		}

		$path = rawurldecode( $path );
		$path = '/' . ltrim( $path, '/' );
		$path = untrailingslashit( $path );

		return ( '' === $path ) ? '/' : $path;
	}

	/**
	 * Whether this request should be ignored by the legacy redirect layer.
	 */
	function prelaunch_legacy_wix_should_skip_request( string $path ): bool {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( is_preview() || is_feed() || is_trackback() || is_robots() || is_favicon() ) {
			return true;
		}

		if ( preg_match( '#^/(wp-admin|wp-content|wp-includes|wp-json)(/|$)#', $path ) ) {
			return true;
		}

		if ( preg_match( '#^/(index\.php|xmlrpc\.php|wp-login\.php|wp-cron\.php)$#', $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Issue a 301 to an internal path or absolute same-host URL.
	 */
	function prelaunch_legacy_wix_redirect_to( string $destination ): void {
		if ( '' === $destination ) {
			return;
		}

		if ( preg_match( '#^https?://#i', $destination ) ) {
			$target = $destination;
		} else {
			$target = home_url( $destination );
		}

		// Avoid no-op / loop if somehow pointed at the current request.
		$current = home_url( add_query_arg( [] ) );
		if ( untrailingslashit( $target ) === untrailingslashit( $current ) ) {
			return;
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * Resolve a published post/advisor by slug, if one exists.
	 *
	 * @param string               $slug      Post name.
	 * @param string|array<string> $post_type Post type(s).
	 */
	function prelaunch_legacy_wix_find_published_by_slug( string $slug, $post_type ): ?WP_Post {
		$slug = trim( $slug, '/' );
		if ( '' === $slug ) {
			return null;
		}

		$post = get_page_by_path( $slug, OBJECT, $post_type );
		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		if ( 'publish' !== $post->post_status ) {
			return null;
		}

		return $post;
	}

	/**
	 * Run legacy Wix → WordPress redirects.
	 */
	function prelaunch_legacy_wix_redirects(): void {
		$path = prelaunch_legacy_wix_request_path();
		if ( '' === $path || '/' === $path ) {
			return;
		}

		if ( prelaunch_legacy_wix_should_skip_request( $path ) ) {
			return;
		}

		$exact = prelaunch_legacy_wix_exact_redirects();
		if ( isset( $exact[ $path ] ) ) {
			prelaunch_legacy_wix_redirect_to( $exact[ $path ] );
		}

		if ( preg_match( '#^/post/(.+)$#', $path, $matches ) ) {
			$post = prelaunch_legacy_wix_find_published_by_slug( $matches[1], 'post' );
			if ( $post instanceof WP_Post ) {
				$permalink = get_permalink( $post );
				if ( is_string( $permalink ) && '' !== $permalink ) {
					prelaunch_legacy_wix_redirect_to( $permalink );
				}
			}

			prelaunch_legacy_wix_redirect_to( '/articles/' );
		}

		if ( preg_match( '#^/team/(.+)$#', $path, $matches ) ) {
			$advisor = prelaunch_legacy_wix_find_published_by_slug( $matches[1], 'advisor' );
			if ( $advisor instanceof WP_Post ) {
				$permalink = get_permalink( $advisor );
				if ( is_string( $permalink ) && '' !== $permalink ) {
					prelaunch_legacy_wix_redirect_to( $permalink );
				}
			}

			prelaunch_legacy_wix_redirect_to( '/advisors/' );
		}
	}

	add_action( 'template_redirect', 'prelaunch_legacy_wix_redirects', 1 );
