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

	/**
	 * Migrate legacy advisor_gallery gallery values into repeater rows.
	 *
	 * The Travel Gallery field used to be an ACF gallery (array of attachment
	 * IDs). It is now a repeater with photo + photo_title so captions can sit
	 * under each polaroid. Existing post meta is still stored in the old
	 * format until an advisor is re-saved — this filter bridges that gap for
	 * both the admin UI and the front end.
	 *
	 * @param mixed                $pre     Short-circuit value, or null to continue.
	 * @param integer|string       $post_id Post ID being loaded.
	 * @param array<string, mixed> $field   ACF field array.
	 *
	 * @return mixed
	 */
	function prelaunch_acf_pre_load_advisor_gallery( $pre, $post_id, array $field ) {
		if ( ( $field['key'] ?? '' ) !== 'field_advisor_gallery' ) {
			return $pre;
		}

		$raw = acf_get_metadata( (string) $post_id, 'advisor_gallery' );

		/*
		 * Proper repeater storage uses a numeric row count. Let ACF load it.
		 */
		if ( is_numeric( $raw ) ) {
			return $pre;
		}

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return $pre;
		}

		$sub_fields = is_array( $field['sub_fields'] ?? null ) ? $field['sub_fields'] : [];

		if ( empty( $sub_fields ) ) {
			return $pre;
		}

		$photo_key = '';
		$title_key = '';

		foreach ( $sub_fields as $sub_field ) {
			$name = (string) ( $sub_field['name'] ?? '' );

			if ( 'photo' === $name ) {
				$photo_key = (string) ( $sub_field['key'] ?? '' );
			} elseif ( 'photo_title' === $name ) {
				$title_key = (string) ( $sub_field['key'] ?? '' );
			}
		}

		if ( '' === $photo_key ) {
			return $pre;
		}

		$rows = [];

		foreach ( array_values( $raw ) as $item ) {
			$image_id = 0;

			if ( is_array( $item ) ) {
				$image_id = absint( $item['ID'] ?? $item['id'] ?? 0 );
			} else {
				$image_id = absint( $item );
			}

			if ( ! $image_id ) {
				continue;
			}

			$row = [
				$photo_key => $image_id,
			];

			if ( '' !== $title_key ) {
				$row[ $title_key ] = '';
			}

			$rows[] = $row;
		}

		return $rows ?: $pre;
	}

	add_filter( 'acf/pre_load_value', 'prelaunch_acf_pre_load_advisor_gallery', 10, 3 );
