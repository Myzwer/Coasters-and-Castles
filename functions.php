<?php
	/**
	 * Theme bootstrap
	 *
	 * Loads modular theme files from /includes. This file will stay lean and act as an
	 * include map + load-order reference for theme functionality.
	 *
	 * @link https://developer.wordpress.org/themes/basics/theme-functions/
	 * @link https://developer.wordpress.org/reference/functions/get_theme_file_path/
	 */

	/* Theme Tokens */
	require_once get_theme_file_path( 'includes/theme/tokens.php' );
	require_once get_theme_file_path( 'includes/theme/fonts.php' );

	/* Posts / content system */
	require_once get_theme_file_path( 'includes/posts/setup.php' );
	require_once get_theme_file_path( 'includes/posts/content.php' );
	require_once get_theme_file_path( 'includes/posts/queries.php' );
	require_once get_theme_file_path( 'includes/posts/template-tags.php' );
	require_once get_theme_file_path( 'includes/posts/editor.php' );
	require_once get_theme_file_path( 'includes/posts/blog-taxonomies.php' );
	require_once get_theme_file_path( 'includes/posts/travel-team-bio.php' );

	/* WordPress theme features (menus, assets, shortcodes, etc.) */
	require_once get_theme_file_path( 'includes/wordpress/enqueue.php' );
	require_once get_theme_file_path( 'includes/wordpress/menus.php' );
	require_once get_theme_file_path( 'includes/wordpress/shortcodes.php' );
	require_once get_theme_file_path( 'includes/wordpress/custom_post_types.php' );

	/* Plugins / integrations */
	require_once get_theme_file_path( 'includes/plugins/acf.php' );
	require_once get_theme_file_path( 'includes/plugins/seo.php' );
	require_once get_theme_file_path( 'includes/plugins/gravity-forms-options.php' );
	require_once get_theme_file_path( 'includes/plugins/vacationcrm.php' );
	require_once get_theme_file_path( 'includes/plugins/booking.php' );

	/* Utility functions */
	require_once get_theme_file_path( 'includes/utility/quick_functions.php' );

	/* Admin */
	require_once get_theme_file_path( 'includes/admin/editor_tools.php' );
	require_once get_theme_file_path( 'includes/admin/admin_editor_cleanup.php' );
	require_once get_theme_file_path( 'includes/admin/admin_dashboard.php' );
	require_once get_theme_file_path( 'includes/admin/admin_tokens_widget.php' );
	require_once get_theme_file_path( 'includes/admin/login.php' );

	/* Register User Roles */
	require_once get_theme_file_path( 'includes/users/users.php' );
