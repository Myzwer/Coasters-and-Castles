<?php
	/**
	 * FileBird folders for Advisor profiles.
	 *
	 * Keeps a shared FileBird tree of Advisors / {Profile Title} so agency
	 * staff can find advisor uploads in one place. Advisor-role uploads are
	 * always assigned to that profile folder, including ACF media-modal
	 * uploads that would otherwise land in Uncategorized.
	 */

	declare( strict_types=1 );

	defined( 'ABSPATH' ) || exit;

	/**
	 * Shared FileBird parent folder name.
	 */
	const PRELAUNCH_FILEBIRD_ADVISORS_PARENT_NAME = 'Advisors';

	/**
	 * Post meta storing the FileBird folder ID for an Advisor profile.
	 */
	const PRELAUNCH_FILEBIRD_ADVISOR_FOLDER_META = 'prelaunch_filebird_folder_id';

	/**
	 * Option flag set after existing Advisor profiles have been seeded.
	 */
	const PRELAUNCH_FILEBIRD_ADVISOR_FOLDERS_SEEDED = 'prelaunch_filebird_advisor_folders_seeded';

	/**
	 * Determine whether FileBird Pro is available.
	 */
	function prelaunch_filebird_is_available(): bool {
		return class_exists( '\FileBird\Model\Folder' );
	}

	/**
	 * Force FileBird folder writes onto the shared tree (created_by = 0).
	 *
	 * @return int
	 */
	function prelaunch_filebird_shared_created_by(): int {
		return 0;
	}

	/**
	 * Sanitize a folder name the same way FileBird does.
	 */
	function prelaunch_filebird_sanitize_folder_name( string $name ): string {
		$name = sanitize_text_field( wp_kses_post( $name ) );

		if ( class_exists( '\FileBird\Classes\Helpers' ) ) {
			$name = \FileBird\Classes\Helpers::sanitize_for_excel( $name );
		}

		return trim( $name );
	}

	/**
	 * Get the shared Advisors parent folder ID, creating it only if missing.
	 */
	function prelaunch_get_filebird_advisors_parent_id(): int {
		if ( ! prelaunch_filebird_is_available() ) {
			return 0;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'fbv';

		$parent_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE name = %s AND parent = 0 AND created_by = 0 ORDER BY id ASC LIMIT 1",
				PRELAUNCH_FILEBIRD_ADVISORS_PARENT_NAME
			)
		);

		if ( $parent_id > 0 ) {
			return $parent_id;
		}

		$parent_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE name = %s AND created_by = 0 ORDER BY parent ASC, id ASC LIMIT 1",
				PRELAUNCH_FILEBIRD_ADVISORS_PARENT_NAME
			)
		);

		if ( $parent_id > 0 ) {
			return $parent_id;
		}

		add_filter( 'fbv_folder_created_by', 'prelaunch_filebird_shared_created_by', 1000 );
		$created = \FileBird\Model\Folder::newFolder( PRELAUNCH_FILEBIRD_ADVISORS_PARENT_NAME, 0 );
		remove_filter( 'fbv_folder_created_by', 'prelaunch_filebird_shared_created_by', 1000 );

		return isset( $created['id'] ) ? (int) $created['id'] : 0;
	}

	/**
	 * Determine whether a FileBird folder still exists.
	 */
	function prelaunch_filebird_folder_exists( int $folder_id ): bool {
		if ( $folder_id <= 0 || ! prelaunch_filebird_is_available() ) {
			return false;
		}

		return null !== \FileBird\Model\Folder::findById( $folder_id );
	}

	/**
	 * Get FileBird folder IDs already linked to Advisor profiles.
	 *
	 * @return array<int, int> Folder ID => Advisor post ID.
	 */
	function prelaunch_get_claimed_filebird_folder_ids(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				PRELAUNCH_FILEBIRD_ADVISOR_FOLDER_META
			)
		);

		$claimed = array();

		foreach ( (array) $rows as $row ) {
			$folder_id  = (int) $row->meta_value;
			$advisor_id = (int) $row->post_id;

			if ( $folder_id > 0 && $advisor_id > 0 ) {
				$claimed[ $folder_id ] = $advisor_id;
			}
		}

		return $claimed;
	}

	/**
	 * Find a shared FileBird folder by name under a parent.
	 */
	function prelaunch_find_shared_filebird_folder( string $name, int $parent_id ): int {
		if ( '' === $name || $parent_id <= 0 ) {
			return 0;
		}

		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}fbv WHERE name = %s AND parent = %d AND created_by = 0 ORDER BY id ASC LIMIT 1",
				$name,
				$parent_id
			)
		);
	}

	/**
	 * Create a shared FileBird folder under a parent.
	 */
	function prelaunch_create_shared_filebird_folder( string $name, int $parent_id ): int {
		if ( '' === $name || $parent_id <= 0 || ! prelaunch_filebird_is_available() ) {
			return 0;
		}

		add_filter( 'fbv_folder_created_by', 'prelaunch_filebird_shared_created_by', 1000 );
		$created = \FileBird\Model\Folder::newFolder( $name, $parent_id );
		remove_filter( 'fbv_folder_created_by', 'prelaunch_filebird_shared_created_by', 1000 );

		return isset( $created['id'] ) ? (int) $created['id'] : 0;
	}

	/**
	 * Pick a unique shared folder name under a parent.
	 */
	function prelaunch_get_unique_filebird_folder_name( string $name, int $parent_id ): string {
		if ( ! prelaunch_find_shared_filebird_folder( $name, $parent_id ) ) {
			return $name;
		}

		if ( prelaunch_filebird_is_available() ) {
			$unique = \FileBird\Model\Folder::findUniqueFolderName( $name, $parent_id );

			if ( is_string( $unique ) && '' !== $unique ) {
				return $unique;
			}
		}

		return $name . ' (' . wp_generate_password( 4, false, false ) . ')';
	}

	/**
	 * Ensure an Advisor profile has a FileBird folder under Advisors.
	 *
	 * Existing folders are reused. Duplicate profile titles get a unique suffix.
	 *
	 * @return int FileBird folder ID, or 0 when none could be created.
	 */
	function prelaunch_ensure_advisor_filebird_folder( int $advisor_id ): int {
		if ( $advisor_id <= 0 || ! prelaunch_filebird_is_available() ) {
			return 0;
		}

		$advisor = get_post( $advisor_id );

		if (
			! $advisor instanceof WP_Post
			|| PRELAUNCH_ADVISOR_POST_TYPE !== $advisor->post_type
			|| 'auto-draft' === $advisor->post_status
		) {
			return 0;
		}

		$name = prelaunch_filebird_sanitize_folder_name( $advisor->post_title );

		if ( '' === $name ) {
			return 0;
		}

		$parent_id = prelaunch_get_filebird_advisors_parent_id();

		if ( $parent_id <= 0 ) {
			return 0;
		}

		$stored_id = (int) get_post_meta( $advisor_id, PRELAUNCH_FILEBIRD_ADVISOR_FOLDER_META, true );

		if ( $stored_id > 0 && prelaunch_filebird_folder_exists( $stored_id ) ) {
			$folder = \FileBird\Model\Folder::findById( $stored_id, 'id, name, parent' );

			if ( $folder && isset( $folder->name ) && $folder->name !== $name ) {
				\FileBird\Model\Folder::updateFolderName(
					$name,
					isset( $folder->parent ) ? (int) $folder->parent : $parent_id,
					$stored_id,
					true
				);
			}

			prelaunch_sync_advisor_filebird_user_default( $advisor_id, $stored_id );

			return $stored_id;
		}

		$claimed   = prelaunch_get_claimed_filebird_folder_ids();
		$existing  = prelaunch_find_shared_filebird_folder( $name, $parent_id );
		$folder_id = 0;

		if ( $existing > 0 && ( ! isset( $claimed[ $existing ] ) || $advisor_id === $claimed[ $existing ] ) ) {
			$folder_id = $existing;
		} else {
			$create_name = $existing > 0
				? prelaunch_get_unique_filebird_folder_name( $name, $parent_id )
				: $name;
			$folder_id   = prelaunch_create_shared_filebird_folder( $create_name, $parent_id );
		}

		if ( $folder_id <= 0 ) {
			return 0;
		}

		update_post_meta( $advisor_id, PRELAUNCH_FILEBIRD_ADVISOR_FOLDER_META, $folder_id );
		prelaunch_sync_advisor_filebird_user_default( $advisor_id, $folder_id );

		return $folder_id;
	}

	/**
	 * Point a linked Advisor user at their FileBird folder as the default.
	 */
	function prelaunch_sync_advisor_filebird_user_default( int $advisor_id, int $folder_id = 0 ): void {
		if ( $folder_id <= 0 ) {
			$folder_id = (int) get_post_meta( $advisor_id, PRELAUNCH_FILEBIRD_ADVISOR_FOLDER_META, true );
		}

		if ( $folder_id <= 0 ) {
			return;
		}

		$linked_user_id = 0;

		if ( function_exists( 'get_field' ) ) {
			$linked_user_id = (int) get_field(
				PRELAUNCH_ADVISOR_LINKED_USER_FIELD,
				$advisor_id,
				false
			);
		}

		if ( $linked_user_id <= 0 ) {
			$linked_user_id = (int) get_post_meta( $advisor_id, PRELAUNCH_ADVISOR_LINKED_USER_FIELD, true );
		}

		if ( $linked_user_id <= 0 ) {
			return;
		}

		$meta_key = class_exists( '\FileBird\Model\UserSettingModel' )
			? \FileBird\Model\UserSettingModel::DEFAULT_FOLDER
			: '_njt_fbv_default_folder';

		update_user_meta( $linked_user_id, $meta_key, $folder_id );
	}

	/**
	 * Create FileBird folders for every existing Advisor profile.
	 */
	function prelaunch_seed_advisor_filebird_folders(): void {
		if ( ! is_admin() || ! prelaunch_filebird_is_available() ) {
			return;
		}

		if ( get_option( PRELAUNCH_FILEBIRD_ADVISOR_FOLDERS_SEEDED ) ) {
			return;
		}

		if ( prelaunch_get_filebird_advisors_parent_id() <= 0 ) {
			return;
		}

		$advisor_ids = get_posts(
			array(
				'post_type'              => PRELAUNCH_ADVISOR_POST_TYPE,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $advisor_ids as $advisor_id ) {
			prelaunch_ensure_advisor_filebird_folder( (int) $advisor_id );
		}

		update_option( PRELAUNCH_FILEBIRD_ADVISOR_FOLDERS_SEEDED, '1', false );
	}

	add_action( 'admin_init', 'prelaunch_seed_advisor_filebird_folders', 30 );

	/**
	 * Keep the current Advisor's default FileBird folder in sync on admin load.
	 */
	function prelaunch_sync_current_advisor_filebird_folder(): void {
		if ( ! is_admin() || ! prelaunch_advisor_media_is_isolated() || wp_doing_ajax() ) {
			return;
		}

		$advisor_id = prelaunch_get_linked_advisor_id( get_current_user_id() );

		if ( $advisor_id > 0 ) {
			prelaunch_ensure_advisor_filebird_folder( $advisor_id );
		}
	}

	add_action( 'admin_init', 'prelaunch_sync_current_advisor_filebird_folder', 31 );

	/**
	 * Create or rename the FileBird folder when an Advisor profile is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post.
	 */
	function prelaunch_ensure_advisor_filebird_folder_on_save( int $post_id, WP_Post $post, bool $update ): void {
		unset( $update );

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		prelaunch_ensure_advisor_filebird_folder( $post_id );
	}

	add_action( 'save_post_advisor', 'prelaunch_ensure_advisor_filebird_folder_on_save', 20, 3 );

	/**
	 * Refresh the linked user's default folder after ACF saves the user field.
	 *
	 * @param int|string $post_id ACF post ID.
	 */
	function prelaunch_sync_advisor_filebird_folder_after_acf_save( $post_id ): void {
		$advisor_id = is_numeric( $post_id ) ? (int) $post_id : 0;

		if ( $advisor_id <= 0 || PRELAUNCH_ADVISOR_POST_TYPE !== get_post_type( $advisor_id ) ) {
			return;
		}

		prelaunch_ensure_advisor_filebird_folder( $advisor_id );
	}

	add_action( 'acf/save_post', 'prelaunch_sync_advisor_filebird_folder_after_acf_save', 40 );

	/**
	 * Always place Advisor-role uploads into that profile's FileBird folder.
	 *
	 * Runs after FileBird's own add_attachment handler so ACF modal uploads
	 * cannot remain Uncategorized or land in a folder the advisor clicked.
	 */
	function prelaunch_assign_advisor_upload_to_filebird_folder( int $attachment_id ): void {
		if ( ! prelaunch_filebird_is_available() || ! prelaunch_advisor_media_is_isolated() ) {
			return;
		}

		$attachment = get_post( $attachment_id );

		if ( ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type ) {
			return;
		}

		$advisor_id = prelaunch_get_linked_advisor_id( get_current_user_id() );

		if ( $advisor_id <= 0 ) {
			return;
		}

		$folder_id = prelaunch_ensure_advisor_filebird_folder( $advisor_id );

		if ( $folder_id <= 0 ) {
			return;
		}

		\FileBird\Model\Folder::setFoldersForPosts( array( $attachment_id ), $folder_id );
	}

	add_action( 'add_attachment', 'prelaunch_assign_advisor_upload_to_filebird_folder', 20 );

	/**
	 * Open FileBird on the Advisor's own folder when they use the media library.
	 *
	 * @param int $folder_id FileBird folder ID.
	 * @param int $user_id   User ID.
	 *
	 * @return int
	 */
	function prelaunch_filter_advisor_filebird_default_folder( $folder_id, $user_id = 0 ): int {
		$folder_id = (int) $folder_id;
		$user_id   = (int) $user_id;

		if ( $user_id <= 0 ) {
			return $folder_id;
		}

		$user = get_userdata( $user_id );

		if ( ! $user instanceof WP_User || ! prelaunch_user_has_advisor_family_role( $user ) ) {
			return $folder_id;
		}

		$advisor_id = prelaunch_get_linked_advisor_id( $user_id );

		if ( $advisor_id <= 0 ) {
			return $folder_id;
		}

		$ensured = prelaunch_ensure_advisor_filebird_folder( $advisor_id );

		return $ensured > 0 ? $ensured : $folder_id;
	}

	add_filter( 'fbv_user_default_folder', 'prelaunch_filter_advisor_filebird_default_folder', 10, 2 );
