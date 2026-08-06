<?php

	/**
	 * Blog Taxonomies
	 *
	 * Registers the controlled taxonomies used to organize blog posts and removes
	 * WordPress's native Categories and Tags from the Post editing experience.
	 *
	 * Taxonomy terms are intentionally managed separately and are not seeded here.
	 *
	 * @link https://developer.wordpress.org/reference/functions/register_taxonomy/
	 * @link https://developer.wordpress.org/reference/functions/unregister_taxonomy_for_object_type/
	 */

	declare( strict_types=1 );

	/**
	 * Register the Trip Type taxonomy.
	 *
	 * The internal taxonomy key is `trip_type`, while editors and visitors see
	 * "Vacation Type." This intentionally avoids colliding with the separate
	 * `vacation_type` taxonomy already registered for Advisor profiles.
	 */
	function prelaunch_register_trip_type_taxonomy(): void {
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

			/*
			 * ACF is the sole Post-editor interface for this taxonomy.
			 * Disabling REST exposure prevents Gutenberg from adding a duplicate
			 * taxonomy panel in the editor sidebar.
			 */
			'show_in_rest'       => false,
			'meta_box_cb'        => false,

			'hierarchical' => true,

			/*
			 * The Advisor taxonomy already uses /vacation-type/, so blog taxonomy
			 * archives use a distinct rewrite base while retaining the same
			 * visitor-facing "Vacation Type" language.
			 */
			'rewrite'      => [
				'slug'         => 'vacation-type-articles',
				'with_front'   => false,
				'hierarchical' => true,
			],

			'query_var' => true,
		];

		register_taxonomy(
			'trip_type',
			[ 'post' ],
			$args
		);
	}

	/**
	 * Register the Article Type taxonomy.
	 */
	function prelaunch_register_article_type_taxonomy(): void {
		$labels = [
			'name'              => __( 'Article Types', 'prelaunch-wp' ),
			'singular_name'     => __( 'Article Type', 'prelaunch-wp' ),
			'menu_name'         => __( 'Article Types', 'prelaunch-wp' ),
			'all_items'         => __( 'All Article Types', 'prelaunch-wp' ),
			'edit_item'         => __( 'Edit Article Type', 'prelaunch-wp' ),
			'view_item'         => __( 'View Article Type', 'prelaunch-wp' ),
			'update_item'       => __( 'Update Article Type', 'prelaunch-wp' ),
			'add_new_item'      => __( 'Add New Article Type', 'prelaunch-wp' ),
			'new_item_name'     => __( 'New Article Type Name', 'prelaunch-wp' ),
			'parent_item'       => __( 'Parent Article Type', 'prelaunch-wp' ),
			'parent_item_colon' => __( 'Parent Article Type:', 'prelaunch-wp' ),
			'search_items'      => __( 'Search Article Types', 'prelaunch-wp' ),
			'not_found'         => __( 'No article types found.', 'prelaunch-wp' ),
			'back_to_items'     => __( '← Back to Article Types', 'prelaunch-wp' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => false,

			/*
			 * ACF is the sole Post-editor interface for this taxonomy.
			 * Disabling REST exposure prevents Gutenberg from adding a duplicate
			 * taxonomy panel in the editor sidebar.
			 */
			'show_in_rest'       => false,
			'meta_box_cb'        => false,

			'hierarchical' => true,

			'rewrite' => [
				'slug'         => 'article-type',
				'with_front'   => false,
				'hierarchical' => true,
			],

			'query_var' => true,
		];

		register_taxonomy(
			'article_type',
			[ 'post' ],
			$args
		);
	}

	/**
	 * Register all blog taxonomies.
	 */
	function prelaunch_register_blog_taxonomies(): void {
		prelaunch_register_trip_type_taxonomy();
		prelaunch_register_article_type_taxonomy();
	}

	add_action( 'init', 'prelaunch_register_blog_taxonomies' );

	/**
	 * Remove native Categories and Tags from blog posts.
	 *
	 * Existing term records remain in the database, but Posts no longer use or
	 * expose the native taxonomies in the editor, REST schema, or admin menus.
	 */
	function prelaunch_unregister_native_post_taxonomies(): void {
		unregister_taxonomy_for_object_type( 'category', 'post' );
		unregister_taxonomy_for_object_type( 'post_tag', 'post' );
	}

	add_action( 'init', 'prelaunch_unregister_native_post_taxonomies', 20 );
