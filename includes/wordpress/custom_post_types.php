<?php

	/**
	 * Custom Post Types
	 *
	 * This file is reserved for registering custom post types (CPTs)
	 * used by the theme. It is intentionally empty in the starter
	 * template, as required post types will vary by project.
	 *
	 * Keeping CPT registration isolated here allows future projects
	 * to add or remove post types without modifying core theme files.
	 *
	 * @link https://developer.wordpress.org/plugins/post-types/registering-custom-post-types/
	 */

	declare( strict_types=1 );

	// Custom post types will be registered here using `register_post_type()`.
	// Example implementations should be added on the `init` hook when needed.


	/**
	 * Register the Advisor custom post type and related taxonomies.
	 */

	/**
	 * Register the Advisor custom post type.
	 */
	function prelaunch_register_advisor_post_type(): void {
		$labels = [
			'name'                     => __( 'Advisors', 'prelaunch-wp' ),
			'singular_name'            => __( 'Advisor', 'prelaunch-wp' ),
			'menu_name'                => __( 'Advisors', 'prelaunch-wp' ),
			'name_admin_bar'           => __( 'Advisor', 'prelaunch-wp' ),
			'add_new'                  => __( 'Add New', 'prelaunch-wp' ),
			'add_new_item'             => __( 'Add New Advisor', 'prelaunch-wp' ),
			'new_item'                 => __( 'New Advisor', 'prelaunch-wp' ),
			'edit_item'                => __( 'Edit Advisor', 'prelaunch-wp' ),
			'view_item'                => __( 'View Advisor', 'prelaunch-wp' ),
			'view_items'               => __( 'View Advisors', 'prelaunch-wp' ),
			'all_items'                => __( 'All Advisors', 'prelaunch-wp' ),
			'search_items'             => __( 'Search Advisors', 'prelaunch-wp' ),
			'not_found'                => __( 'No advisors found.', 'prelaunch-wp' ),
			'not_found_in_trash'       => __( 'No advisors found in Trash.', 'prelaunch-wp' ),
			'archives'                 => __( 'Advisor Archives', 'prelaunch-wp' ),
			'attributes'               => __( 'Advisor Attributes', 'prelaunch-wp' ),
			'filter_items_list'        => __( 'Filter advisors list', 'prelaunch-wp' ),
			'items_list_navigation'    => __( 'Advisors list navigation', 'prelaunch-wp' ),
			'items_list'               => __( 'Advisors list', 'prelaunch-wp' ),
			'item_published'           => __( 'Advisor published.', 'prelaunch-wp' ),
			'item_published_privately' => __( 'Advisor published privately.', 'prelaunch-wp' ),
			'item_reverted_to_draft'   => __( 'Advisor reverted to draft.', 'prelaunch-wp' ),
			'item_scheduled'           => __( 'Advisor scheduled.', 'prelaunch-wp' ),
			'item_updated'             => __( 'Advisor updated.', 'prelaunch-wp' ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Travel advisor profiles.', 'prelaunch-wp' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'show_in_rest'        => true,
			'exclude_from_search' => false,

			'menu_position' => 5,
			'menu_icon'     => 'dashicons-groups',

			'hierarchical' => false,
			'has_archive'  => true,

			'rewrite' => [
				'slug'       => 'advisors',
				'with_front' => false,
			],

			'query_var'        => true,
			'can_export'       => true,
			'delete_with_user' => false,

			'capability_type' => 'post',
			'map_meta_cap'    => true,

			'supports' => [
				'title',
				'page-attributes',
			],
		];

		register_post_type( 'advisor', $args );
	}

	/**
	 * Register the Vacation Type taxonomy.
	 */
	function prelaunch_register_vacation_type_taxonomy(): void {
		$labels = [
			'name'              => __( 'Vacation Types', 'prelaunch-wp' ),
			'singular_name'     => __( 'Vacation Type', 'prelaunch-wp' ),
			'menu_name'         => __( 'Vacation Types', 'prelaunch-wp' ),
			'all_items'         => __( 'All Vacation Types', 'prelaunch-wp' ),
			'edit_item'         => __( 'Edit Vacation Type', 'prelaunch-wp' ),
			'view_item'         => __( 'View Vacation Type', 'prelaunch-wp' ),
			'update_item'       => __( 'Update Vacation Type', 'prelaunch-wp' ),
			'add_new_item'      => __( 'Add New Vacation Type', 'prelaunch-wp' ),
			'new_item_name'     => __( 'New Vacation Type Name', 'prelaunch-wp' ),
			'parent_item'       => __( 'Parent Vacation Type', 'prelaunch-wp' ),
			'parent_item_colon' => __( 'Parent Vacation Type:', 'prelaunch-wp' ),
			'search_items'      => __( 'Search Vacation Types', 'prelaunch-wp' ),
			'not_found'         => __( 'No vacation types found.', 'prelaunch-wp' ),
			'back_to_items'     => __( '← Back to Vacation Types', 'prelaunch-wp' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => false,
			'show_in_rest'       => true,

			'hierarchical' => true,

			'rewrite' => [
				'slug'         => 'vacation-type',
				'with_front'   => false,
				'hierarchical' => true,
			],

			'query_var' => true,
		];

		register_taxonomy(
			'vacation_type',
			[ 'advisor' ],
			$args
		);
	}

	/**
	 * Register the Group Type taxonomy.
	 */
	function prelaunch_register_group_type_taxonomy(): void {
		$labels = [
			'name'              => __( 'Group Types', 'prelaunch-wp' ),
			'singular_name'     => __( 'Group Type', 'prelaunch-wp' ),
			'menu_name'         => __( 'Group Types', 'prelaunch-wp' ),
			'all_items'         => __( 'All Group Types', 'prelaunch-wp' ),
			'edit_item'         => __( 'Edit Group Type', 'prelaunch-wp' ),
			'view_item'         => __( 'View Group Type', 'prelaunch-wp' ),
			'update_item'       => __( 'Update Group Type', 'prelaunch-wp' ),
			'add_new_item'      => __( 'Add New Group Type', 'prelaunch-wp' ),
			'new_item_name'     => __( 'New Group Type Name', 'prelaunch-wp' ),
			'parent_item'       => __( 'Parent Group Type', 'prelaunch-wp' ),
			'parent_item_colon' => __( 'Parent Group Type:', 'prelaunch-wp' ),
			'search_items'      => __( 'Search Group Types', 'prelaunch-wp' ),
			'not_found'         => __( 'No group types found.', 'prelaunch-wp' ),
			'back_to_items'     => __( '← Back to Group Types', 'prelaunch-wp' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => false,
			'show_in_rest'       => true,

			'hierarchical' => true,

			'rewrite' => [
				'slug'         => 'group-type',
				'with_front'   => false,
				'hierarchical' => true,
			],

			'query_var' => true,
		];

		register_taxonomy(
			'group_type',
			[ 'advisor' ],
			$args
		);
	}

	/**
	 * Register all Advisor content types.
	 */
	function prelaunch_register_advisor_content(): void {
		prelaunch_register_advisor_post_type();
		prelaunch_register_vacation_type_taxonomy();
		prelaunch_register_group_type_taxonomy();
	}

	add_action( 'init', 'prelaunch_register_advisor_content' );
