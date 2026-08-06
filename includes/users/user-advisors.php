<?php
	/**
	 * Advisor role access and profile-editing workflow.
	 *
	 * Advisors are linked to exactly one Advisor post through the ACF field
	 * `advisor_linked_user`. This module limits each advisor account to that
	 * profile, isolates their Media Library, protects agency-controlled ACF
	 * fields, and keeps administrators in full control of every Advisor profile.
	 */

	defined( 'ABSPATH' ) || exit;

	/**
	 * Advisor custom post type slug.
	 */
	const PRELAUNCH_ADVISOR_POST_TYPE = 'advisor';

	/**
	 * ACF field name used to link an Advisor post to a WordPress user.
	 */
	const PRELAUNCH_ADVISOR_LINKED_USER_FIELD = 'advisor_linked_user';

	/**
	 * Return all primitive capabilities generated for the Advisor post type.
	 *
	 * @return array<int, string>
	 */
	function prelaunch_get_advisor_post_type_caps(): array {
		return array(
			'edit_advisor',
			'read_advisor',
			'delete_advisor',
			'edit_advisors',
			'edit_others_advisors',
			'publish_advisors',
			'read_private_advisors',
			'delete_advisors',
			'delete_private_advisors',
			'delete_published_advisors',
			'delete_others_advisors',
			'edit_private_advisors',
			'edit_published_advisors',
			'create_advisors',
		);
	}

	/**
	 * Synchronize Advisor post type capabilities.
	 *
	 * Administrators and Site Administrators receive full control of Advisor
	 * profiles. Advisor accounts receive only the capabilities required to open,
	 * edit, and upload media for their own linked profile. Object-level filters
	 * below prevent access to any other Advisor profile and block creation or
	 * deletion.
	 *
	 * @return void
	 */
	function prelaunch_sync_advisor_role_caps(): void {
		$advisor_caps = prelaunch_get_advisor_post_type_caps();
		$legacy_revision_caps = array(
			'copy_advisors',
			'copy_others_advisors',
			'revise_advisors',
			'revise_others_advisors',
			'approve_advisors',
			'approve_others_advisors',
			'manage_revision_queue',
			'manage_unsubmitted_revisions',
			'edit_others_revisions',
			'list_others_revisions',
			'restore_revisions',
		);

		foreach ( array( PRELAUNCH_OWNER_ROLE, PRELAUNCH_CLIENT_ADMIN_ROLE ) as $role_slug ) {
			$role = get_role( $role_slug );

			if ( ! $role ) {
				continue;
			}

			foreach ( $legacy_revision_caps as $capability ) {
				$role->remove_cap( $capability );
			}

			foreach ( $advisor_caps as $capability ) {
				$role->add_cap( $capability );
			}
		}

		$advisor_role = get_role( PRELAUNCH_ADVISOR_ROLE );

		if ( ! $advisor_role ) {
			return;
		}

		foreach ( array_merge( $advisor_caps, $legacy_revision_caps ) as $capability ) {
			$advisor_role->remove_cap( $capability );
		}

		$advisor_role->remove_cap( 'unfiltered_upload' );
		$advisor_role->add_cap( 'read' );
		$advisor_role->add_cap( 'upload_files' );
		$advisor_role->add_cap( 'edit_advisors' );
		$advisor_role->add_cap( 'edit_published_advisors' );
		$advisor_role->add_cap( 'edit_private_advisors' );
		$advisor_role->add_cap( 'read_advisor' );
	}

	add_action( 'init', 'prelaunch_sync_advisor_role_caps', 40 );

	/**
	 * Get the Advisor post linked to a user.
	 *
	 * The ACF User field stores the selected user ID in post meta. Only the first
	 * match is returned because each account should be linked to one profile.
	 *
	 * @param int $user_id WordPress user ID.
	 *
	 * @return int Advisor post ID, or 0 when no profile is linked.
	 */
	function prelaunch_get_linked_advisor_id( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		$advisor_ids = get_posts(
			array(
				'post_type'              => PRELAUNCH_ADVISOR_POST_TYPE,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'meta_key'               => PRELAUNCH_ADVISOR_LINKED_USER_FIELD,
				'meta_value'             => $user_id,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return isset( $advisor_ids[0] ) ? (int) $advisor_ids[0] : 0;
	}

	/**
	 * Determine whether a user is linked to a specific Advisor post.
	 *
	 * @param int $user_id WordPress user ID.
	 * @param int $advisor_id Advisor post ID.
	 *
	 * @return bool
	 */
	function prelaunch_user_owns_advisor_profile( int $user_id, int $advisor_id ): bool {
		return $advisor_id > 0 && $advisor_id === prelaunch_get_linked_advisor_id( $user_id );
	}

	/**
	 * Enforce object-level access to Advisor posts.
	 *
	 * Advisors may edit and read only the Advisor profile linked to their account.
	 * They may never delete an Advisor profile. This is enforced server-side even
	 * when an Advisor manually changes a post ID in the URL.
	 *
	 * @param array<int, string> $caps Primitive capabilities WordPress requires.
	 * @param string $cap Requested meta capability.
	 * @param int $user_id User ID.
	 * @param array<int, mixed> $args Capability arguments.
	 *
	 * @return array<int, string>
	 */
	function prelaunch_map_advisor_meta_caps(
		array $caps,
		string $cap,
		int $user_id,
		array $args
	): array {
		$user = get_userdata( $user_id );

		if (
			! $user instanceof WP_User
			|| ! prelaunch_user_has_role( $user, PRELAUNCH_ADVISOR_ROLE )
			|| ! in_array( $cap, array( 'edit_post', 'read_post', 'delete_post' ), true )
		) {
			return $caps;
		}

		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post || PRELAUNCH_ADVISOR_POST_TYPE !== $post->post_type ) {
			return $caps;
		}

		if ( ! prelaunch_user_owns_advisor_profile( $user_id, $post_id ) ) {
			return array( 'do_not_allow' );
		}

		if ( 'delete_post' === $cap ) {
			return array( 'do_not_allow' );
		}

		if ( 'read_post' === $cap ) {
			return array( 'read' );
		}

		return array( 'edit_advisors' );
	}

	add_filter( 'map_meta_cap', 'prelaunch_map_advisor_meta_caps', 20, 4 );


	/**
	 * Keep the Advisor post author synchronized with its linked WordPress user.
	 *
	 * WordPress performs an additional author check when an existing post is
	 * saved. Advisor profiles are usually created by an administrator, so the
	 * internal post author must mirror `advisor_linked_user` or WordPress will
	 * reject the save before ACF can run.
	 *
	 * @param int $advisor_id Advisor post ID.
	 * @param int $user_id Linked WordPress user ID.
	 *
	 * @return void
	 */
	function prelaunch_sync_advisor_post_author( int $advisor_id, int $user_id ): void {
		if ( $advisor_id <= 0 || $user_id <= 0 ) {
			return;
		}

		$advisor = get_post( $advisor_id );
		$user    = get_userdata( $user_id );

		if (
			! $advisor instanceof WP_Post
			|| PRELAUNCH_ADVISOR_POST_TYPE !== $advisor->post_type
			|| ! $user instanceof WP_User
			|| (int) $advisor->post_author === $user_id
		) {
			return;
		}

		wp_update_post(
			array(
				'ID'          => $advisor_id,
				'post_author' => $user_id,
			)
		);
	}

	/**
	 * Repair the linked Advisor's post author when the Advisor enters wp-admin.
	 *
	 * This immediately repairs existing profiles that were originally created
	 * by an administrator, without requiring a manual resave first.
	 *
	 * @return void
	 */
	function prelaunch_sync_current_advisor_post_author(): void {
		if ( ! is_admin() || ! prelaunch_is_advisor() || wp_doing_ajax() ) {
			return;
		}

		$user_id    = get_current_user_id();
		$advisor_id = prelaunch_get_linked_advisor_id( $user_id );

		prelaunch_sync_advisor_post_author( $advisor_id, $user_id );
	}

	add_action( 'admin_init', 'prelaunch_sync_current_advisor_post_author', 1 );

	/**
	 * Synchronize the internal post author after an administrator changes the
	 * Linked WordPress User field on an Advisor profile.
	 *
	 * @param int|string $post_id ACF post ID.
	 *
	 * @return void
	 */
	function prelaunch_sync_advisor_author_after_acf_save( $post_id ): void {
		$advisor_id = is_numeric( $post_id ) ? (int) $post_id : 0;

		if ( $advisor_id <= 0 || PRELAUNCH_ADVISOR_POST_TYPE !== get_post_type( $advisor_id ) ) {
			return;
		}

		$linked_user_id = (int) get_field(
			PRELAUNCH_ADVISOR_LINKED_USER_FIELD,
			$advisor_id,
			false
		);

		prelaunch_sync_advisor_post_author( $advisor_id, $linked_user_id );
	}

	add_action( 'acf/save_post', 'prelaunch_sync_advisor_author_after_acf_save', 30 );

	/**
	 * Limit the Advisor list table to the current user's linked profile.
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 */
	function prelaunch_limit_advisor_admin_query( WP_Query $query ): void {
		if ( ! is_admin() || ! prelaunch_is_advisor() || ! $query->is_main_query() ) {
			return;
		}

		if ( PRELAUNCH_ADVISOR_POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$advisor_id = prelaunch_get_linked_advisor_id( get_current_user_id() );
		$query->set( 'post__in', $advisor_id ? array( $advisor_id ) : array( 0 ) );
	}

	add_action( 'pre_get_posts', 'prelaunch_limit_advisor_admin_query' );

	/**
	 * Keep Advisor users out of screens and actions they do not need.
	 *
	 * @return void
	 */
	function prelaunch_guard_advisor_admin_requests(): void {
		if ( ! is_admin() || ! prelaunch_is_advisor() || wp_doing_ajax() ) {
			return;
		}

		global $pagenow;

		if ( ! is_string( $pagenow ) ) {
			return;
		}

		$linked_advisor_id = prelaunch_get_linked_advisor_id( get_current_user_id() );

		if ( 'post-new.php' === $pagenow ) {
			$post_type = sanitize_key( (string) filter_input( INPUT_GET, 'post_type' ) );

			if ( PRELAUNCH_ADVISOR_POST_TYPE === $post_type ) {
				wp_die(
					esc_html__( 'Advisor accounts cannot create additional advisor profiles.', 'prelaunch-wp' ),
					esc_html__( 'Access denied', 'prelaunch-wp' ),
					array( 'response' => 403 )
				);
			}
		}

		if ( 'post.php' === $pagenow ) {
			$post_id             = (int) filter_input( INPUT_GET, 'post', FILTER_VALIDATE_INT );
			$post = get_post( $post_id );

			if (
				$post instanceof WP_Post
				&& PRELAUNCH_ADVISOR_POST_TYPE === $post->post_type
				&& $post_id !== $linked_advisor_id
			) {
				wp_die(
					esc_html__( 'You can only edit your own advisor profile.', 'prelaunch-wp' ),
					esc_html__( 'Access denied', 'prelaunch-wp' ),
					array( 'response' => 403 )
				);
			}
		}

		if ( in_array( $pagenow, array( 'edit-tags.php', 'term.php' ), true ) ) {
			$taxonomy = sanitize_key( (string) filter_input( INPUT_GET, 'taxonomy' ) );

			if ( in_array( $taxonomy, array( 'vacation_type', 'group_type' ), true ) ) {
				wp_die(
					esc_html__( 'Advisor accounts cannot manage profile taxonomy terms.', 'prelaunch-wp' ),
					esc_html__( 'Access denied', 'prelaunch-wp' ),
					array( 'response' => 403 )
				);
			}
		}
	}

	add_action( 'admin_init', 'prelaunch_guard_advisor_admin_requests', 20 );

	/**
	 * Simplify the Advisor admin menu.
	 *
	 * Advisors do not need a post list because each account can edit only one
	 * linked profile. Replace the normal Advisors menu with a direct link to that
	 * profile and rename WordPress's Profile screen to Account Settings.
	 *
	 * @return void
	 */
	function prelaunch_customize_advisor_admin_menu(): void {
		if ( ! is_admin() || ! prelaunch_is_advisor() ) {
			return;
		}

		$advisor_id = prelaunch_get_linked_advisor_id( get_current_user_id() );

		remove_menu_page( 'edit.php?post_type=advisor' );

		if ( $advisor_id ) {
			add_menu_page(
				esc_html__( 'Advisor Profile', 'prelaunch-wp' ),
				esc_html__( 'Advisor Profile', 'prelaunch-wp' ),
				'edit_advisors',
				'post.php?post=' . $advisor_id . '&action=edit',
				'',
				'dashicons-id-alt',
				5
			);
		}

		global $menu;

		if ( ! is_array( $menu ) ) {
			return;
		}

		foreach ( $menu as &$menu_item ) {
			if ( isset( $menu_item[2] ) && 'profile.php' === $menu_item[2] ) {
				$menu_item[0] = esc_html__( 'Account Settings', 'prelaunch-wp' );
				break;
			}
		}
		unset( $menu_item );
	}

	add_action( 'admin_menu', 'prelaunch_customize_advisor_admin_menu', 1000 );

	/**
	 * Redirect the unusable Advisor list screen to the linked profile.
	 *
	 * @return void
	 */
	function prelaunch_redirect_advisor_list_screen(): void {
		if ( ! is_admin() || ! prelaunch_is_advisor() || wp_doing_ajax() ) {
			return;
		}

		global $pagenow;

		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		$post_type = sanitize_key( (string) filter_input( INPUT_GET, 'post_type' ) );

		if ( PRELAUNCH_ADVISOR_POST_TYPE !== $post_type ) {
			return;
		}

		$advisor_id  = prelaunch_get_linked_advisor_id( get_current_user_id() );
		$destination = $advisor_id
			? admin_url( 'post.php?post=' . $advisor_id . '&action=edit' )
			: admin_url( 'profile.php' );

		wp_safe_redirect( $destination );
		exit;
	}

	add_action( 'admin_init', 'prelaunch_redirect_advisor_list_screen', 5 );

	/**
	 * Explain the limited purpose of Account Settings to Advisor users.
	 *
	 * @return void
	 */
	function prelaunch_advisor_account_settings_notice(): void {
		if ( ! prelaunch_is_advisor() ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'profile' !== $screen->id ) {
			return;
		}
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Account settings only:', 'prelaunch-wp' ); ?></strong>
				<?php
					esc_html_e(
						'Changes made here do not update your public advisor profile. Use Advisor Profile to edit the information travelers see. This page is only for managing your login email, password, and WordPress account settings.',
						'prelaunch-wp'
					);
				?>
			</p>
		</div>
		<?php
	}

	add_action( 'admin_notices', 'prelaunch_advisor_account_settings_notice' );

	/**
	 * Hide WordPress profile fields that do not affect the public Advisor page.
	 *
	 * Email, password, sessions, and the read-only username remain available.
	 *
	 * @return void
	 */
	function prelaunch_simplify_advisor_profile_screen(): void {
		if ( ! prelaunch_is_advisor() ) {
			return;
		}
		?>
		<style>
			.profile-php .user-rich-editing-wrap,
			.profile-php .user-syntax-highlighting-wrap,
			.profile-php .user-admin-color-wrap,
			.profile-php .user-comment-shortcuts-wrap,
			.profile-php .show-admin-bar,
			.profile-php .user-language-wrap,
			.profile-php .user-first-name-wrap,
			.profile-php .user-last-name-wrap,
			.profile-php .user-nickname-wrap,
			.profile-php .user-display-name-wrap,
			.profile-php .user-url-wrap,
			.profile-php .user-description-wrap,
			.profile-php .user-profile-picture {
				display: none !important;
			}
		</style>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				document.querySelectorAll('.profile-php h2').forEach(function (heading) {
					var sibling = heading.nextElementSibling;

					if (sibling && sibling.matches('table.form-table')) {
						var visibleRows = Array.from(sibling.querySelectorAll('tr')).some(function (row) {
							return window.getComputedStyle(row).display !== 'none';
						});

						if (!visibleRows) {
							heading.style.display = 'none';
							sibling.style.display = 'none';
						}
					}
				});
			});
		</script>
		<?php
	}

	add_action( 'admin_head-profile.php', 'prelaunch_simplify_advisor_profile_screen' );

	/**
	 * Redirect Advisor users to their linked profile after login.
	 *
	 * @param string $redirect_to Redirect destination.
	 * @param string $requested_redirect_to Requested redirect destination.
	 * @param WP_User|WP_Error $user Authenticated user.
	 *
	 * @return string
	 */
	function prelaunch_redirect_advisor_after_login( string $redirect_to, string $requested_redirect_to, $user ): string {
		if ( ! $user instanceof WP_User || ! prelaunch_user_has_role( $user, PRELAUNCH_ADVISOR_ROLE ) ) {
			return $redirect_to;
		}

		$advisor_id = prelaunch_get_linked_advisor_id( (int) $user->ID );

		if ( ! $advisor_id ) {
			return admin_url( 'profile.php' );
		}

		return admin_url( 'post.php?post=' . $advisor_id . '&action=edit' );
	}

	add_filter( 'login_redirect', 'prelaunch_redirect_advisor_after_login', 50, 3 );

	/**
	 * Redirect the disabled Dashboard to the linked Advisor profile.
	 *
	 * @return void
	 */
	function prelaunch_redirect_advisor_dashboard(): void {
		if ( ! is_admin() || ! prelaunch_is_advisor() ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}

		$advisor_id  = prelaunch_get_linked_advisor_id( get_current_user_id() );
		$destination = $advisor_id
			? admin_url( 'post.php?post=' . $advisor_id . '&action=edit' )
			: admin_url( 'profile.php' );

		wp_safe_redirect( $destination );
		exit;
	}

	add_action( 'current_screen', 'prelaunch_redirect_advisor_dashboard', 5 );

	/**
	 * Protected ACF fields controlled by the agency.
	 *
	 * @return array<string, string> Field key => field name.
	 */
	function prelaunch_get_advisor_protected_acf_fields(): array {
		return array(
			'field_advisor_tab_agency'            => '',
			'field_advisor_professional_headshot' => 'advisor_professional_headshot',
			'field_advisor_title'                 => 'advisor_title',
			'field_6a6fe1054a41a'                 => 'email',
			'field_advisor_linked_user'           => 'advisor_linked_user',
			'field_6a6fe28e2433f'                 => 'tln_profile_link',
			'field_advisor_external_url'          => 'advisor_external_url',
			'field_advisor_crm_id'                => 'advisor_crm_id',
		);
	}

	/**
	 * Hide agency-controlled fields from Advisor users.
	 *
	 * @param array<string, mixed>|false $field ACF field configuration.
	 *
	 * @return array<string, mixed>|false
	 */
	function prelaunch_hide_advisor_protected_acf_field( $field ) {
		if ( ! prelaunch_is_advisor() || ! is_array( $field ) ) {
			return $field;
		}

		$field_key = isset( $field['key'] ) ? (string) $field['key'] : '';

		if ( array_key_exists( $field_key, prelaunch_get_advisor_protected_acf_fields() ) ) {
			return false;
		}

		return $field;
	}

	foreach ( array_keys( prelaunch_get_advisor_protected_acf_fields() ) as $protected_field_key ) {
		add_filter( 'acf/prepare_field/key=' . $protected_field_key, 'prelaunch_hide_advisor_protected_acf_field' );
	}

	/**
	 * Prevent forged requests from changing agency-controlled ACF values.
	 *
	 * Hidden fields are not sufficient protection. If an Advisor submits a
	 * protected field key manually, preserve the value currently stored on the
	 * linked live profile.
	 *
	 * @param mixed $value New ACF value.
	 * @param int|string $post_id ACF post ID.
	 * @param array<string, mixed> $field ACF field configuration.
	 *
	 * @return mixed
	 */
	function prelaunch_preserve_advisor_protected_acf_value( $value, $post_id, array $field ) {
		if ( ! prelaunch_is_advisor() ) {
			return $value;
		}

		$field_key        = isset( $field['key'] ) ? (string) $field['key'] : '';
		$protected_fields = prelaunch_get_advisor_protected_acf_fields();

		if ( ! isset( $protected_fields[ $field_key ] ) || '' === $protected_fields[ $field_key ] ) {
			return $value;
		}

		$submitted_acf = isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ? wp_unslash( $_POST['acf'] ) : array();

		if ( ! array_key_exists( $field_key, $submitted_acf ) ) {
			return $value;
		}

		$advisor_id = prelaunch_get_linked_advisor_id( get_current_user_id() );

		if ( ! $advisor_id ) {
			return null;
		}

		return get_field( $protected_fields[ $field_key ], $advisor_id, false );
	}

	foreach ( prelaunch_get_advisor_protected_acf_fields() as $protected_field_key => $protected_field_name ) {
		if ( '' === $protected_field_name ) {
			continue;
		}

		add_filter(
			'acf/update_value/key=' . $protected_field_key,
			'prelaunch_preserve_advisor_protected_acf_value',
			20,
			3
		);
	}

	/**
	 * Limit Media Library queries to files uploaded by the current Advisor.
	 *
	 * @param array<string, mixed> $query Attachment query arguments.
	 *
	 * @return array<string, mixed>
	 */
	function prelaunch_limit_advisor_media_modal( array $query ): array {
		if ( prelaunch_is_advisor() ) {
			$query['author'] = get_current_user_id();
		}

		return $query;
	}

	add_filter( 'ajax_query_attachments_args', 'prelaunch_limit_advisor_media_modal' );

	/**
	 * Limit the main Media Library list to the current Advisor's uploads.
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 */
	function prelaunch_limit_advisor_media_list( WP_Query $query ): void {
		if ( ! is_admin() || ! prelaunch_is_advisor() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'attachment' !== $query->get( 'post_type' ) ) {
			return;
		}

		$query->set( 'author', get_current_user_id() );
	}

	add_action( 'pre_get_posts', 'prelaunch_limit_advisor_media_list', 20 );

	/**
	 * Prevent Advisors from editing or deleting another user's attachment.
	 *
	 * @param array<int, string> $caps Primitive capabilities.
	 * @param string $cap Requested capability.
	 * @param int $user_id User ID.
	 * @param array<int, mixed> $args Capability arguments.
	 *
	 * @return array<int, string>
	 */
	function prelaunch_map_advisor_attachment_caps( array $caps, string $cap, int $user_id, array $args ): array {
		$user = get_userdata( $user_id );

		if ( ! $user instanceof WP_User || ! prelaunch_user_has_role( $user, PRELAUNCH_ADVISOR_ROLE ) ) {
			return $caps;
		}

		if ( ! in_array( $cap, array( 'edit_post', 'delete_post' ), true ) ) {
			return $caps;
		}

		$post_id    = isset( $args[0] ) ? (int) $args[0] : 0;
		$attachment = get_post( $post_id );

		if ( ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type ) {
			return $caps;
		}

		if ( (int) $attachment->post_author !== $user_id ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	}

	add_filter( 'map_meta_cap', 'prelaunch_map_advisor_attachment_caps', 25, 4 );




	/**
	 * Hide the Advisor Attributes metabox from Advisor users.
	 *
	 * The Advisor CPT still supports page attributes so administrators can use
	 * menu order internally, but advisors do not need to see or modify it.
	 *
	 * @return void
	 */
	function prelaunch_hide_advisor_attributes_metabox(): void {
		if ( ! prelaunch_is_advisor() ) {
			return;
		}

		remove_meta_box(
			'pageparentdiv',
			PRELAUNCH_ADVISOR_POST_TYPE,
			'side'
		);
	}

	add_action(
		'add_meta_boxes_' . PRELAUNCH_ADVISOR_POST_TYPE,
		'prelaunch_hide_advisor_attributes_metabox',
		100
	);

	/**
	 * Hide WordPress revision history controls from Advisor users.
	 *
	 * Revision comparison remains an administrator tool. Advisors only need the
	 * current profile editing form during this baseline phase.
	 *
	 * @return void
	 */
	function prelaunch_simplify_advisor_publish_metabox(): void {
		if ( ! prelaunch_is_advisor() ) {
			return;
		}
		?>
		<style>
			.post-type-advisor #misc-publishing-actions .misc-pub-revisions,
			.post-type-advisor #revisionsdiv {
				display: none !important;
			}
		</style>
		<?php
	}

	add_action(
		'admin_head-post.php',
		'prelaunch_simplify_advisor_publish_metabox'
	);
