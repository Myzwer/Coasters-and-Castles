<?php
	/**
	 * Public /getpassword route for first-time advisor password setup.
	 */

	defined( 'ABSPATH' ) || exit;

	/**
	 * Query var used by the getpassword rewrite rule.
	 */
	const PRELAUNCH_GETPASSWORD_QUERY_VAR = 'prelaunch_getpassword';

	add_action( 'init', 'prelaunch_register_getpassword_rewrite_rule', 20 );

	/**
	 * Register the pretty /getpassword URL.
	 */
	function prelaunch_register_getpassword_rewrite_rule(): void {
		add_rewrite_rule(
			'^getpassword/?$',
			'index.php?' . PRELAUNCH_GETPASSWORD_QUERY_VAR . '=1',
			'top'
		);
	}

	add_filter( 'query_vars', 'prelaunch_register_getpassword_query_var' );

	/**
	 * Expose the getpassword query var to WordPress.
	 *
	 * @param array<int, string> $query_vars Registered query vars.
	 *
	 * @return array<int, string>
	 */
	function prelaunch_register_getpassword_query_var( array $query_vars ): array {
		$query_vars[] = PRELAUNCH_GETPASSWORD_QUERY_VAR;

		return $query_vars;
	}

	add_action( 'init', 'prelaunch_maybe_flush_getpassword_rewrite_rules', 99 );

	/**
	 * Flush rewrite rules once after the route is introduced.
	 */
	function prelaunch_maybe_flush_getpassword_rewrite_rules(): void {
		if ( get_option( 'prelaunch_getpassword_rewrite_flushed' ) ) {
			return;
		}

		prelaunch_register_getpassword_rewrite_rule();
		flush_rewrite_rules( false );
		update_option( 'prelaunch_getpassword_rewrite_flushed', true, false );
	}

	add_action( 'after_switch_theme', 'prelaunch_flush_getpassword_rewrite_rules' );

	/**
	 * Refresh rewrite rules after theme activation.
	 */
	function prelaunch_flush_getpassword_rewrite_rules(): void {
		prelaunch_register_getpassword_rewrite_rule();
		flush_rewrite_rules();
		delete_option( 'prelaunch_getpassword_rewrite_flushed' );
	}

	add_action( 'template_redirect', 'prelaunch_render_getpassword_page', 0 );

	/**
	 * Render the standalone getpassword screen.
	 */
	function prelaunch_render_getpassword_page(): void {
		if ( ! get_query_var( PRELAUNCH_GETPASSWORD_QUERY_VAR ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		status_header( 200 );
		nocache_headers();

		prelaunch_print_getpassword_page();
		exit;
	}

	/**
	 * Output the getpassword page markup.
	 */
	function prelaunch_print_getpassword_page(): void {
		$logo         = prelaunch_get_login_logo();
		$login_url    = wp_login_url();
		$home_url     = home_url( '/' );
		$privacy_url  = get_privacy_policy_url();
		$site_name    = get_bloginfo( 'name' );
		$form_action  = network_site_url( 'wp-login.php?action=lostpassword', 'login_post' );
		$redirect_to  = add_query_arg(
			array(
				'checkemail'                   => 'confirm',
				PRELAUNCH_PASSWORD_SETUP_QUERY => '1',
			),
			wp_login_url()
		);
		$styles_url   = get_theme_file_uri( '/assets/admin/login.css' );
		$styles_path  = get_theme_file_path( '/assets/admin/login.css' );
		$styles_ver   = file_exists( $styles_path ) ? (string) filemtime( $styles_path ) : wp_get_theme()->get( 'Version' );
		$colors       = prelaunch_get_brand_colors();
		$color_tokens = prelaunch_get_login_color_tokens_css( $colors );
		$logo_styles  = '';

		if ( ! empty( $logo['url'] ) ) {
			$logo_styles = sprintf(
				'body.login.prelaunch-getpassword-screen h1 a { background-image: url("%s"); }',
				esc_url( $logo['url'] )
			);
		}

		$document_title = sprintf(
			/* translators: %s: Site name. */
			__( 'Get Your Password &lsaquo; %s', 'prelaunch-wp' ),
			$site_name
		);
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<title><?php echo esc_html( wp_strip_all_tags( $document_title ) ); ?></title>
			<link rel="stylesheet" href="<?php echo esc_url( $styles_url ); ?>?ver=<?php echo esc_attr( $styles_ver ); ?>" />
			<style><?php echo $color_tokens . $logo_styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
		</head>
		<body class="login prelaunch-getpassword-screen js">
			<div id="login">
				<h1 role="presentation" class="wp-login-logo">
					<a href="<?php echo esc_url( $home_url ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( $logo['alt'] ); ?></span>
					</a>
				</h1>

				<div class="prelaunch-getpassword-panel">
					<div class="prelaunch-getpassword-intro">
						<p class="prelaunch-getpassword-intro__eyebrow">
							<?php esc_html_e( 'Advisor account', 'prelaunch-wp' ); ?>
						</p>
						<h2 class="prelaunch-getpassword-intro__title">
							<?php esc_html_e( 'Need a password?', 'prelaunch-wp' ); ?>
						</h2>
						<p class="prelaunch-getpassword-intro__text">
							<?php
								echo wp_kses_post(
									__(
										'Enter your <strong>Travelcnc</strong> email and we&rsquo;ll send a link to create your password.',
										'prelaunch-wp'
									)
								);
							?>
						</p>
						<p class="prelaunch-getpassword-intro__followup">
							<?php
								echo wp_kses_post(
									sprintf(
										/* translators: %s: login page URL. */
										__(
											'After you set your password, <a href="%s">return to login</a>.',
											'prelaunch-wp'
										),
										esc_url( $login_url )
									)
								);
							?>
						</p>
					</div>

					<form
						name="getpasswordform"
						id="getpasswordform"
						class="prelaunch-getpassword-form"
						action="<?php echo esc_url( $form_action ); ?>"
						method="post"
						accept-charset="<?php bloginfo( 'charset' ); ?>"
					>
						<p class="prelaunch-getpassword-field">
							<label for="user_login">
								<?php esc_html_e( 'Travelcnc Email Address:', 'prelaunch-wp' ); ?>
							</label>
							<input
								type="email"
								name="user_login"
								id="user_login"
								class="input ltr"
								value=""
								autocapitalize="off"
								autocomplete="email"
								required="required"
							/>
						</p>

						<input type="hidden" name="<?php echo esc_attr( PRELAUNCH_PASSWORD_SETUP_FIELD ); ?>" value="1" />
						<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />

						<p class="submit">
							<input
								type="submit"
								name="wp-submit"
								id="wp-submit"
								class="button button-primary button-large"
								value="<?php esc_attr_e( 'Get my password', 'prelaunch-wp' ); ?>"
							/>
						</p>
					</form>
				</div>

				<p id="nav">
					<a href="<?php echo esc_url( $login_url ); ?>">
						<?php esc_html_e( 'Back to login', 'prelaunch-wp' ); ?>
					</a>
				</p>

				<?php if ( $privacy_url ) : ?>
					<p class="privacy-policy-page-link">
						<a class="privacy-policy-link" href="<?php echo esc_url( $privacy_url ); ?>" rel="privacy-policy">
							<?php esc_html_e( 'Privacy Policy', 'prelaunch-wp' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</body>
		</html>
		<?php
	}
