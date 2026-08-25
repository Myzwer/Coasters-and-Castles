<?php
	/**
	 * ACF options page registration and JSON sync.
	 *
	 * Registers Prelaunch ACF options pages used for site-wide configuration,
	 * and points ACF Local JSON at the theme's `acf-json/` directory so field
	 * groups can be versioned with the theme.
	 *
	 * Capability rules:
	 * - Client-facing options pages should use `read` so they remain accessible
	 *   even when post editing is disabled by the managed-role system.
	 * - Developer-only options pages should use a dedicated custom capability so
	 *   they stay private even when managed roles retain broader admin access.
	 *
	 * Access to the core ACF admin UI (Field Groups, Tools, etc.) is controlled
	 * separately by the user-access modules.
	 */

	declare( strict_types=1 );

	defined( 'ABSPATH' ) || exit;

	/**
	 * Absolute path to the theme ACF Local JSON directory.
	 *
	 * @return string
	 */
	function prelaunch_get_acf_json_path(): string {
		return get_stylesheet_directory() . '/acf-json';
	}

	/**
	 * Tell ACF where to save Local JSON field group files.
	 *
	 * @return string
	 */
	function prelaunch_acf_json_save_point(): string {
		return prelaunch_get_acf_json_path();
	}

	add_filter( 'acf/settings/save_json', 'prelaunch_acf_json_save_point' );

	/**
	 * Tell ACF where to load Local JSON field group files.
	 *
	 * Replaces the default load path so groups resolve from this theme only.
	 *
	 * @param array<int, string> $paths Existing load paths.
	 *
	 * @return array<int, string>
	 */
	function prelaunch_acf_json_load_point( array $paths ): array {
		unset( $paths[0] );
		$paths[] = prelaunch_get_acf_json_path();

		return $paths;
	}

	add_filter( 'acf/settings/load_json', 'prelaunch_acf_json_load_point' );

	/**
	 * Register a Prelaunch ACF options page.
	 *
	 * This helper keeps options-page registration consistent and prevents client-
	 * facing settings pages from accidentally depending on unrelated caps like
	 * edit_posts.
	 *
	 * Supported visibility types:
	 * - client: uses `read`
	 * - developer: uses PRELAUNCH_MANAGE_TOKENS_CAP
	 *
	 * @param array{
	 *     page_title: string,
	 *     menu_title: string,
	 *     menu_slug: string,
	 *     icon_url?: string,
	 *     redirect?: bool,
	 *     visibility?: string
	 * } $args Options page arguments.
	 *
	 * @return void
	 */
	function prelaunch_register_acf_options_page( array $args ): void {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		$visibility = isset( $args['visibility'] ) ? (string) $args['visibility'] : 'client';

		$capability = 'read';

		if ( 'developer' === $visibility ) {
			$capability = PRELAUNCH_MANAGE_TOKENS_CAP;
		}

		unset( $args['visibility'] );

		acf_add_options_page(
			array_merge(
				array(
					'icon_url'   => 'dashicons-admin-generic',
					'redirect'   => false,
					'capability' => $capability,
				),
				$args
			)
		);
	}

	/**
	 * Register Prelaunch ACF options pages.
	 *
	 * Hooked into `acf/init` to ensure ACF is fully loaded before use.
	 *
	 * @return void
	 */
	function prelaunch_register_acf_options_pages(): void {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		prelaunch_register_acf_options_page(
			array(
				'page_title' => 'Globals',
				'menu_title' => 'Globals',
				'menu_slug'  => 'acf-globals',
				'icon_url'   => 'dashicons-admin-site',
				'visibility' => 'client',
			)
		);

		prelaunch_register_acf_options_page(
			array(
				'page_title' => 'Tokens',
				'menu_title' => 'Tokens',
				'menu_slug'  => 'tokens',
				'icon_url'   => 'dashicons-tickets-alt',
				'visibility' => 'developer',
			)
		);
	}

	add_action( 'acf/init', 'prelaunch_register_acf_options_pages' );
