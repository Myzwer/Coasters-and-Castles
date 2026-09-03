<?php

	/**
	 * Quick functions
	 *
	 * Small, optional quality-of-life helpers that don’t belong in a larger feature module.
	 * Keep this file lean—if a helper grows beyond a few lines or becomes site-specific,
	 * move it into a dedicated file.
	 *
	 * Current:
	 * - Register core theme supports (e.g., title-tag for SEO plugin compatibility).
	 * - Alphabetize page templates in the Page Attributes template dropdown.
	 * - Display a non-production environment badge in the admin bar.
	 * - Disable comments site-wide (UI + front end + admin cleanup).
	 * - Limit advisor Group Types to two and hide the booking-form "I'm Not Sure" term.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_theme_support/
	 * @link https://developer.wordpress.org/reference/hooks/theme_page_templates/
	 * @link https://developer.wordpress.org/reference/functions/wp_get_environment_type/
	 * @link https://developer.wordpress.org/reference/functions/remove_post_type_support/
	 * @link https://developer.wordpress.org/reference/hooks/comments_open/
	 * @link https://developer.wordpress.org/reference/hooks/pings_open/
	 */

	declare( strict_types=1 );

	/**
	 * Register core theme supports.
	 *
	 * Adds foundational WordPress features required for predictable behavior
	 * across the theme and plugin ecosystem.
	 *
	 * Currently registers:
	 * - title-tag: Allows WordPress (and SEO plugins like The SEO Framework)
	 *   to control the <title> element dynamically. Without this support,
	 *   SEO plugins cannot reliably filter or modify document titles.
	 */
	function windpeak_theme_setup(): void {
		add_theme_support( 'title-tag' );
	}

	add_action( 'after_setup_theme', 'windpeak_theme_setup' );

	/**
	 * Alphabetize page templates in the editor dropdown.
	 *
	 * @param array<string,string> $templates Template file => label.
	 *
	 * @return array<string,string>
	 */
	function windpeak_alphabetize_page_templates( array $templates ): array {
		asort( $templates );

		return $templates;
	}

	add_filter( 'theme_page_templates', 'windpeak_alphabetize_page_templates' );

	/**
	 * Add an environment indicator to the admin bar on non-production environments.
	 *
	 * Helps prevent accidental edits on the wrong site when working across
	 * development, staging, and production.
	 */
	function windpeak_admin_bar_environment_badge(): void {
		// Only show to admins and only outside of production.
		if (
			! is_admin_bar_showing() ||
			! current_user_can( 'manage_options' ) ||
			wp_get_environment_type() === 'production'
		) {
			return;
		}

		$env = strtoupper( wp_get_environment_type() );

		global $wp_admin_bar;

		$wp_admin_bar->add_node( [
			'id'    => 'windpeak-env-badge',
			'title' => esc_html( $env ),
			'meta'  => [
				// Inline styles keep this self-contained and avoid extra CSS.
				'style' => 'background:#d63638;color:#fff;padding:2px 8px;border-radius:3px;font-weight:600;',
			],
		] );
	}

	add_action( 'admin_bar_menu', 'windpeak_admin_bar_environment_badge', 100 );

	/**
	 * Disable comments site-wide.
	 *
	 * This theme defaults comments off (common for brochure/ACF-driven sites).
	 * If a project needs comments later, remove this block or gate it behind a constant.
	 *
	 * What this does:
	 * - Removes comment + trackback support from post types (editor/admin UI)
	 * - Forces comments/pings closed on the front end
	 * - Hides comment admin UI (menus, metabox, admin bar)
	 * - Redirects comment management screens to the dashboard
	 *
	 * @link https://developer.wordpress.org/reference/functions/remove_post_type_support/
	 * @link https://developer.wordpress.org/reference/hooks/comments_open/
	 * @link https://developer.wordpress.org/reference/hooks/pings_open/
	 * @link https://developer.wordpress.org/reference/hooks/admin_menu/
	 */
	function windpeak_disable_comments_setup(): void {
		// Remove comment support from all registered post types that expose it.
		foreach ( get_post_types( [], 'names' ) as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	add_action( 'init', 'windpeak_disable_comments_setup', 100 );

	/**
	 * Always close comments and pings on the front end.
	 *
	 * This prevents bots from submitting comments even if something re-enables UI.
	 */
	function windpeak_disable_comments_status(): bool {
		return false;
	}

	add_filter( 'comments_open', 'windpeak_disable_comments_status', 20 );
	add_filter( 'pings_open', 'windpeak_disable_comments_status', 20 );

	/**
	 * Hide existing comments from appearing in templates that call comments_template().
	 */
	add_filter( 'comments_array', static fn( array $comments ): array => [], 10, 2 );

	/**
	 * Remove comment UI entry points in the WordPress admin.
	 */
	function windpeak_disable_comments_admin_ui(): void {
		// Remove the Comments menu item.
		remove_menu_page( 'edit-comments.php' );

		// Remove the Discussion metabox from post/page edit screens.
		remove_meta_box( 'commentstatusdiv', 'post', 'normal' );
		remove_meta_box( 'commentsdiv', 'post', 'normal' );
		remove_meta_box( 'commentstatusdiv', 'page', 'normal' );
		remove_meta_box( 'commentsdiv', 'page', 'normal' );

		// If you have custom post types that add these metaboxes, the init() removal above
		// generally prevents them, but plugins can re-add. Keeping these removals is cheap.
	}

	add_action( 'admin_menu', 'windpeak_disable_comments_admin_ui' );

	/**
	 * Remove the Comments item from the admin bar.
	 */
	function windpeak_disable_comments_admin_bar(): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		global $wp_admin_bar;
		$wp_admin_bar->remove_node( 'comments' );
	}

	add_action( 'admin_bar_menu', 'windpeak_disable_comments_admin_bar', 999 );

	/**
	 * Redirect any direct access to comment management screens back to the dashboard.
	 */
	function windpeak_disable_comments_admin_redirect(): void {
		global $pagenow;

		if ( $pagenow === 'edit-comments.php' || $pagenow === 'comment.php' ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	add_action( 'admin_init', 'windpeak_disable_comments_admin_redirect' );

	/**
	 * Term ID for the client-form "I'm Not Sure" group type escape hatch.
	 *
	 * That term must remain in the taxonomy so booking forms can offer it, but
	 * advisors should never select it as a profile specialty.
	 *
	 * @return int Term ID, or 0 when the term is missing.
	 */
	function windpeak_get_group_type_im_not_sure_term_id(): int {
		static $term_id = null;

		if ( null !== $term_id ) {
			return $term_id;
		}

		$term_id = 0;

		if ( ! taxonomy_exists( 'group_type' ) ) {
			return $term_id;
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'group_type',
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $term_id;
		}

		$normalize = static function ( string $label ): string {
			if ( function_exists( 'prelaunch_normalize_gravity_forms_choice_label' ) ) {
				return prelaunch_normalize_gravity_forms_choice_label( $label );
			}

			$label = str_replace( [ '’', '‘', '`' ], "'", wp_strip_all_tags( $label ) );

			return strtolower( trim( $label ) );
		};

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			if ( "i'm not sure" === $normalize( $term->name ) ) {
				$term_id = (int) $term->term_id;
				break;
			}
		}

		return $term_id;
	}

	/**
	 * Hide "I'm Not Sure" from the advisor Group Types checkbox list.
	 *
	 * @param array<string, mixed> $args wp_list_categories args.
	 * @param array<string, mixed> $field ACF field settings.
	 *
	 * @return array<string, mixed>
	 */
	function windpeak_exclude_im_not_sure_from_advisor_group_types_list(
		array $args,
		array $field
	): array {
		$exclude_id = windpeak_get_group_type_im_not_sure_term_id();

		if ( ! $exclude_id ) {
			return $args;
		}

		$existing = [];

		if ( ! empty( $args['exclude'] ) ) {
			$existing = array_filter(
				array_map(
					'absint',
					is_array( $args['exclude'] )
						? $args['exclude']
						: explode( ',', (string) $args['exclude'] )
				)
			);
		}

		$existing[]      = $exclude_id;
		$args['exclude'] = implode( ',', array_unique( $existing ) );

		return $args;
	}

	add_filter(
		'acf/fields/taxonomy/wp_list_categories/name=advisor_group_types',
		'windpeak_exclude_im_not_sure_from_advisor_group_types_list',
		10,
		2
	);

	/**
	 * Exclude "I'm Not Sure" from advisor Group Types AJAX / query-driven UIs.
	 *
	 * @param array<string, mixed> $args get_terms / WP_Term_Query args.
	 * @param array<string, mixed> $field ACF field settings.
	 *
	 * @return array<string, mixed>
	 */
	function windpeak_exclude_im_not_sure_from_advisor_group_types_query(
		array $args,
		array $field
	): array {
		$exclude_id = windpeak_get_group_type_im_not_sure_term_id();

		if ( ! $exclude_id ) {
			return $args;
		}

		$existing = [];

		if ( ! empty( $args['exclude'] ) ) {
			$existing = array_filter(
				array_map(
					'absint',
					is_array( $args['exclude'] )
						? $args['exclude']
						: explode( ',', (string) $args['exclude'] )
				)
			);
		}

		$existing[]      = $exclude_id;
		$args['exclude'] = array_values( array_unique( $existing ) );

		return $args;
	}

	add_filter(
		'acf/fields/taxonomy/query/name=advisor_group_types',
		'windpeak_exclude_im_not_sure_from_advisor_group_types_query',
		10,
		3
	);

	/**
	 * Limit Advisor Group Type selections to two and block "I'm Not Sure".
	 *
	 * ACF taxonomy checkbox fields do not include a native maximum-selection
	 * setting, so this validates the submitted value before the post is saved.
	 * "I'm Not Sure" is a booking-form escape hatch and is not a valid advisor
	 * specialty, even if submitted outside the visible checkbox list.
	 *
	 * @param bool|string $valid Current validation result.
	 * @param mixed $value Submitted field value.
	 * @param array $field ACF field settings.
	 * @param string $input_name Field input name.
	 *
	 * @return bool|string
	 */
	function windpeak_validate_advisor_group_types(
		bool|string $valid,
		mixed $value,
		array $field,
		string $input_name
	): bool|string {
		// Preserve any validation error ACF has already generated.
		if ( $valid !== true ) {
			return $valid;
		}

		// An empty optional field is valid.
		if ( empty( $value ) ) {
			return true;
		}

		$selected_terms = is_array( $value ) ? $value : [ $value ];
		$selected_terms = array_map( 'absint', $selected_terms );

		$im_not_sure_id = windpeak_get_group_type_im_not_sure_term_id();

		if (
			$im_not_sure_id
			&& in_array( $im_not_sure_id, $selected_terms, true )
		) {
			return __(
				'“I’m Not Sure” is not available as an advisor group type.',
				'prelaunch-wp'
			);
		}

		if ( count( $selected_terms ) > 2 ) {
			return __(
				'Please select no more than two Group Types.',
				'prelaunch-wp'
			);
		}

		return true;
	}

	add_filter(
		'acf/validate_value/name=advisor_group_types',
		'windpeak_validate_advisor_group_types',
		10,
		4
	);
