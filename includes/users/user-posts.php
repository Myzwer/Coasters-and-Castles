<?php
	/**
	 * WordPress post-editor access rules for Prelaunch-managed roles.
	 *
	 * Supported policy levels:
	 * - full: normal Posts access, including publish
	 * - edit: edit anyone's posts, but cannot publish or schedule
	 * - submit: create and edit own drafts, submit for review
	 * - credit: appear as a post author only; no Posts admin access
	 * - off: no Posts access and not listed as an author
	 */

	defined( 'ABSPATH' ) || exit;

	/**
	 * Get the Posts access level for a managed role.
	 *
	 * Legacy boolean policies are normalized:
	 * - true  → full
	 * - false → off
	 *
	 * @param string $role_slug Role slug.
	 *
	 * @return string
	 */
	function prelaunch_get_posts_access_level( string $role_slug ): string {
		$level = prelaunch_get_role_policy_value( $role_slug, 'posts', 'off' );

		if ( true === $level ) {
			return 'full';
		}

		if ( false === $level || null === $level ) {
			return 'off';
		}

		if ( ! is_string( $level ) ) {
			return 'off';
		}

		$allowed_levels = array(
			'full',
			'edit',
			'submit',
			'credit',
			'off',
		);

		return in_array( $level, $allowed_levels, true ) ? $level : 'off';
	}

	/**
	 * Determine whether a managed role should have Posts admin access.
	 *
	 * Credit is intentionally excluded: those users are author-eligible only.
	 *
	 * @param string $role_slug Role slug.
	 *
	 * @return bool
	 */
	function prelaunch_role_has_posts_access( string $role_slug ): bool {
		return in_array(
			prelaunch_get_posts_access_level( $role_slug ),
			array( 'full', 'edit', 'submit' ),
			true
		);
	}

	/**
	 * Get the post-related capabilities controlled by this module.
	 *
	 * These map to the built-in "post" post type and related taxonomy terms.
	 *
	 * @return array<int, string>
	 */
	function prelaunch_get_posts_module_caps(): array {
		return array(
			'edit_posts',
			'edit_others_posts',
			'edit_published_posts',
			'edit_private_posts',
			'publish_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_published_posts',
			'delete_private_posts',
			'read_private_posts',
			'manage_categories',
		);
	}

	/**
	 * Sync post capabilities for all Prelaunch-managed roles.
	 *
	 * Because managed non-advisor roles are cloned from Administrator, this
	 * module removes every Posts capability it owns before re-applying the
	 * correct policy level. Advisor-family roles start from a minimal whitelist
	 * and receive only the caps their Posts level requires.
	 *
	 * @return void
	 */
	function prelaunch_sync_managed_role_posts_caps(): void {
		$post_caps = prelaunch_get_posts_module_caps();

		foreach ( prelaunch_get_managed_user_roles() as $role_slug ) {
			$role = get_role( $role_slug );

			if ( ! $role ) {
				continue;
			}

			foreach ( $post_caps as $cap ) {
				$role->remove_cap( $cap );
			}

			switch ( prelaunch_get_posts_access_level( $role_slug ) ) {
				case 'full':
					foreach ( $post_caps as $cap ) {
						$role->add_cap( $cap );
					}
					break;

				case 'edit':
					$role->add_cap( 'edit_posts' );
					$role->add_cap( 'edit_others_posts' );
					$role->add_cap( 'edit_published_posts' );
					$role->add_cap( 'edit_private_posts' );
					$role->add_cap( 'delete_posts' );
					$role->add_cap( 'delete_others_posts' );
					$role->add_cap( 'delete_private_posts' );
					$role->add_cap( 'read_private_posts' );
					$role->add_cap( 'manage_categories' );
					/*
					 * Intentionally not granted:
					 * - publish_posts
					 * - delete_published_posts
					 */
					break;

				case 'submit':
					$role->add_cap( 'edit_posts' );
					$role->add_cap( 'delete_posts' );
					break;

				case 'credit':
					/*
					 * edit_posts is required for WordPress author dropdowns and the
					 * REST authors list. Posts admin UI is blocked separately.
					 */
					$role->add_cap( 'edit_posts' );
					break;

				case 'off':
				default:
					break;
			}
		}
	}

	add_action( 'init', 'prelaunch_sync_managed_role_posts_caps', 30 );

	/**
	 * Remove the Posts admin menu for managed users without Posts admin access.
	 *
	 * @return void
	 */
	function prelaunch_maybe_hide_posts_admin_menu(): void {
		if ( ! is_admin() || ! prelaunch_current_user_has_managed_role() ) {
			return;
		}

		$current_role = prelaunch_get_current_managed_role();

		if ( ! $current_role || prelaunch_role_has_posts_access( $current_role ) ) {
			return;
		}

		remove_menu_page( 'edit.php' );
	}

	add_action( 'admin_menu', 'prelaunch_maybe_hide_posts_admin_menu', 999 );

	/**
	 * Block direct wp-admin access to the Posts area when disabled or credit-only.
	 *
	 * @return void
	 */
	function prelaunch_maybe_block_posts_admin_screens(): void {
		if ( ! is_admin() || ! prelaunch_current_user_has_managed_role() ) {
			return;
		}

		$current_role = prelaunch_get_current_managed_role();

		if ( ! $current_role || prelaunch_role_has_posts_access( $current_role ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$post_screens = array(
			'edit-post',
			'post',
		);

		$post_taxonomy_screens = array(
			'edit-category',
			'edit-post_tag',
		);

		if (
			in_array( $screen->id, $post_screens, true ) ||
			in_array( $screen->id, $post_taxonomy_screens, true )
		) {
			wp_die(
				esc_html__( 'You do not have access to the Posts area on this site.', 'prelaunch-wp' ),
				esc_html__( 'Access denied', 'prelaunch-wp' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
	}

	add_action( 'current_screen', 'prelaunch_maybe_block_posts_admin_screens' );

	/**
	 * Keep credit-only users out of Posts at runtime while leaving them author-eligible.
	 *
	 * WordPress author dropdowns and the REST authors list read role capabilities
	 * from the database, so edit_posts stays on the role. Runtime capability checks
	 * for the credit user themselves are stripped so they cannot open or create posts.
	 *
	 * @param array<string, bool> $allcaps All capabilities for the user.
	 * @param array<int, string> $caps Primitive caps being checked.
	 * @param array<int, mixed> $args Capability check arguments.
	 * @param WP_User $user User object.
	 *
	 * @return array<string, bool>
	 */
	function prelaunch_filter_credit_role_runtime_caps( array $allcaps, array $caps, array $args, WP_User $user ): array {
		unset( $caps, $args );

		$actor_role = null;

		foreach ( prelaunch_get_managed_user_roles() as $role_slug ) {
			if ( prelaunch_user_has_role( $user, $role_slug ) ) {
				$actor_role = $role_slug;
				break;
			}
		}

		if ( ! $actor_role || 'credit' !== prelaunch_get_posts_access_level( $actor_role ) ) {
			return $allcaps;
		}

		unset( $allcaps['edit_posts'] );
		unset( $allcaps['publish_posts'] );
		unset( $allcaps['delete_posts'] );

		return $allcaps;
	}

	add_filter( 'user_has_cap', 'prelaunch_filter_credit_role_runtime_caps', 10, 4 );

	/**
	 * Make Gutenberg's author selector include capability-based authors.
	 *
	 * The block editor still requests `/wp/v2/users?who=authors`. That deprecated
	 * query matches `user_level != 0`, which excludes custom roles like Byline
	 * Advisor that never receive a legacy user level. Translate those requests to
	 * a capability query so anyone with edit_posts on their role can be credited.
	 *
	 * @param array<string, mixed> $prepared_args WP_User_Query arguments.
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return array<string, mixed>
	 */
	function prelaunch_rest_authors_query_by_capability( array $prepared_args, $request ): array {
		unset( $request );

		if ( empty( $prepared_args['who'] ) || 'authors' !== $prepared_args['who'] ) {
			return $prepared_args;
		}

		unset( $prepared_args['who'] );
		$prepared_args['capability'] = array( 'edit_posts' );

		return $prepared_args;
	}

	add_filter( 'rest_user_query', 'prelaunch_rest_authors_query_by_capability', 10, 2 );

	/**
	 * Prevent edit-level roles from publishing or scheduling posts.
	 *
	 * Content edits to already-published posts remain published. New or draft
	 * posts that attempt to publish are forced to pending review instead.
	 *
	 * @param array<string, mixed> $data Sanitized post data.
	 * @param array<string, mixed> $postarr Raw post array.
	 *
	 * @return array<string, mixed>
	 */
	function prelaunch_prevent_post_publishing_for_edit_level( array $data, array $postarr ): array {
		if ( ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $data;
		}

		if ( ! prelaunch_current_user_has_managed_role() ) {
			return $data;
		}

		$current_role = prelaunch_get_current_managed_role();

		if ( ! $current_role || 'edit' !== prelaunch_get_posts_access_level( $current_role ) ) {
			return $data;
		}

		if ( 'post' !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}

		$requested_status = $data['post_status'] ?? '';

		if ( ! in_array( $requested_status, array( 'publish', 'future' ), true ) ) {
			return $data;
		}

		$existing_id     = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$existing_status = $existing_id > 0 ? get_post_status( $existing_id ) : '';

		if ( 'publish' === $existing_status ) {
			$data['post_status'] = 'publish';
			return $data;
		}

		$data['post_status'] = 'pending';

		return $data;
	}

	add_filter( 'wp_insert_post_data', 'prelaunch_prevent_post_publishing_for_edit_level', 10, 2 );

	/**
	 * Prevent submit-level roles from publishing their own posts.
	 *
	 * @param array<string, mixed> $data Sanitized post data.
	 * @param array<string, mixed> $postarr Raw post array.
	 *
	 * @return array<string, mixed>
	 */
	function prelaunch_prevent_post_publishing_for_submit_level( array $data, array $postarr ): array {
		unset( $postarr );

		if ( ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $data;
		}

		if ( ! prelaunch_current_user_has_managed_role() ) {
			return $data;
		}

		$current_role = prelaunch_get_current_managed_role();

		if ( ! $current_role || 'submit' !== prelaunch_get_posts_access_level( $current_role ) ) {
			return $data;
		}

		if ( 'post' !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}

		$requested_status = $data['post_status'] ?? '';

		if ( in_array( $requested_status, array( 'publish', 'future', 'private' ), true ) ) {
			$data['post_status'] = 'pending';
		}

		return $data;
	}

	add_filter( 'wp_insert_post_data', 'prelaunch_prevent_post_publishing_for_submit_level', 10, 2 );

	/**
	 * Determine whether the current admin request is for the Post post type.
	 *
	 * @return bool
	 */
	function prelaunch_is_current_admin_screen_for_posts(): bool {
		global $typenow, $pagenow;

		if ( 'post' === $typenow ) {
			return true;
		}

		if ( 'post-new.php' === $pagenow ) {
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';

			return 'post' === $post_type;
		}

		if ( 'post.php' === $pagenow && isset( $_GET['post'] ) ) {
			$post_id = absint( wp_unslash( $_GET['post'] ) );

			return $post_id > 0 && 'post' === get_post_type( $post_id );
		}

		if ( 'edit.php' === $pagenow ) {
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';

			return 'post' === $post_type;
		}

		return false;
	}
