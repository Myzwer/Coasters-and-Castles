<?php
	/**
	 * Login screen assets, branding, and advisor password setup flow.
	 */

	defined( 'ABSPATH' ) || exit;

	/**
	 * Query argument that marks the advisor-first-time password setup flow.
	 */
	const PRELAUNCH_PASSWORD_SETUP_QUERY = 'setup';

	/**
	 * POST flag submitted with the setup-password form.
	 */
	const PRELAUNCH_PASSWORD_SETUP_FIELD = 'prelaunch_password_setup';

	add_action( 'login_enqueue_scripts', 'prelaunch_enqueue_login_styles' );

	/**
	 * Enqueue custom login stylesheet and shared color tokens.
	 */
	function prelaunch_enqueue_login_styles(): void {
		$theme_version = wp_get_theme()->get( 'Version' );
		$colors        = prelaunch_get_brand_colors();

		$rel_path = '/assets/admin/login.css';
		$file     = get_theme_file_path( $rel_path );
		$ver      = file_exists( $file ) ? (string) filemtime( $file ) : $theme_version;

		wp_enqueue_style(
			'prelaunch-login',
			get_theme_file_uri( $rel_path ),
			[],
			$ver
		);

		wp_add_inline_style( 'prelaunch-login', prelaunch_get_login_color_tokens_css( $colors ) );
	}

	/**
	 * Shared login/getpassword color token CSS.
	 *
	 * @param array<string, string> $colors Brand colors.
	 */
	function prelaunch_get_login_color_tokens_css( array $colors ): string {
		return sprintf(
			':root {
			--color-black: %1$s;
			--color-white: %2$s;
			--color-primary: %3$s;
			--color-secondary: %4$s;
			--color-soft-1: %5$s;
			--color-soft-2: %6$s;
			--color-primary-gradient-to: %7$s;
			--color-secondary-gradient-to: %8$s;
			--color-impact-gradient-to: %9$s;
		}',
			esc_html( $colors['black'] ),
			esc_html( $colors['white'] ),
			esc_html( $colors['primary'] ),
			esc_html( $colors['secondary'] ),
			esc_html( $colors['soft-1'] ),
			esc_html( $colors['soft-2'] ),
			esc_html( $colors['primary-gradient-to'] ),
			esc_html( $colors['secondary-gradient-to'] ),
			esc_html( $colors['impact-gradient-to'] )
		);
	}

	/**
	 * Login logo data from theme options.
	 *
	 * @return array{url: string, alt: string}
	 */
	function prelaunch_get_login_logo(): array {
		$image = get_field( 'footer_logo', 'option' );

		return array(
			'url' => ! empty( $image['url'] ) ? esc_url_raw( $image['url'] ) : '',
			'alt' => ! empty( $image['alt'] ) ? (string) $image['alt'] : get_bloginfo( 'name' ),
		);
	}

	add_action( 'login_enqueue_scripts', 'prelaunch_login_logo_styles', 20 );

	/**
	 * Output custom login logo styles from theme options.
	 */
	function prelaunch_login_logo_styles(): void {
		$logo = prelaunch_get_login_logo();

		if ( '' === $logo['url'] ) {
			return;
		}

		wp_add_inline_style(
			'prelaunch-login',
			sprintf(
				'body.login h1 a { background-image: url("%s"); }',
				esc_url( $logo['url'] )
			)
		);
	}

	add_filter( 'login_headerurl', 'prelaunch_login_header_url' );

	/**
	 * Point the login logo URL back to the site homepage.
	 */
	function prelaunch_login_header_url(): string {
		return home_url( '/' );
	}

	add_filter( 'login_headertext', 'prelaunch_login_header_text' );

	/**
	 * Use the option logo alt text when available, otherwise fall back to site name.
	 */
	function prelaunch_login_header_text(): string {
		return prelaunch_get_login_logo()['alt'];
	}

	/**
	 * Current wp-login.php action slug.
	 */
	function prelaunch_get_login_screen_action(): string {
		if ( isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ) {
			return sanitize_key( wp_unslash( $_REQUEST['action'] ) );
		}

		return 'login';
	}

	/**
	 * Whether the current request is using the advisor password setup flow.
	 */
	function prelaunch_is_password_setup_request(): bool {
		if (
			isset( $_REQUEST[ PRELAUNCH_PASSWORD_SETUP_FIELD ] )
			&& '1' === (string) wp_unslash( $_REQUEST[ PRELAUNCH_PASSWORD_SETUP_FIELD ] )
		) {
			return true;
		}

		if (
			isset( $_REQUEST[ PRELAUNCH_PASSWORD_SETUP_QUERY ] )
			&& '1' === (string) wp_unslash( $_REQUEST[ PRELAUNCH_PASSWORD_SETUP_QUERY ] )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Public URL for first-time advisor password setup.
	 */
	function prelaunch_get_password_setup_url(): string {
		return home_url( '/getpassword/' );
	}

	add_filter( 'lost_password_html_link', 'prelaunch_customize_login_password_links' );

	/**
	 * Show split password help links on the login screen.
	 *
	 * @param string $html_link Default lost-password link HTML.
	 */
	function prelaunch_customize_login_password_links( string $html_link ): string {
		if ( ! prelaunch_is_primary_login_screen() ) {
			return $html_link;
		}

		$forgot_link = sprintf(
			'<a class="wp-login-lost-password" href="%1$s">%2$s</a>',
			esc_url( wp_lostpassword_url() ),
			esc_html__( 'Forgot Password?', 'prelaunch-wp' )
		);

		$need_link = sprintf(
			'<a class="wp-login-need-password" href="%1$s">%2$s</a>',
			esc_url( prelaunch_get_password_setup_url() ),
			esc_html__( 'Need a Password?', 'prelaunch-wp' )
		);

		return sprintf(
			'%1$s <span class="prelaunch-login-nav-sep" aria-hidden="true">|</span> %2$s',
			$forgot_link,
			$need_link
		);
	}

	/**
	 * Whether the current screen is the primary login form.
	 */
	function prelaunch_is_primary_login_screen(): bool {
		$action = prelaunch_get_login_screen_action();

		return in_array( $action, array( 'login', '' ), true );
	}

	/**
	 * Read the username from the active password-reset flow.
	 */
	function prelaunch_get_password_reset_login(): string {
		$rp_cookie = 'wp-resetpass-' . COOKIEHASH;

		if (
			isset( $_COOKIE[ $rp_cookie ] )
			&& is_string( $_COOKIE[ $rp_cookie ] )
			&& str_contains( $_COOKIE[ $rp_cookie ], ':' )
		) {
			return sanitize_user( strtok( wp_unslash( $_COOKIE[ $rp_cookie ] ), ':' ) );
		}

		if ( isset( $_GET['login'] ) && is_string( $_GET['login'] ) ) {
			return sanitize_user( wp_unslash( $_GET['login'] ) );
		}

		return '';
	}

	/**
	 * Whether the active password-reset flow belongs to an advisor account.
	 */
	function prelaunch_is_advisor_password_reset(): bool {
		$user_login = prelaunch_get_password_reset_login();

		if ( '' === $user_login ) {
			return false;
		}

		$user = get_user_by( 'login', $user_login );

		return $user instanceof WP_User && prelaunch_user_has_advisor_family_role( $user );
	}

	add_filter( 'login_body_class', 'prelaunch_login_body_classes', 10, 2 );

	/**
	 * Add login screen state classes for styling.
	 *
	 * @param array<int, string> $classes Existing body classes.
	 * @param string             $action  Current login action.
	 *
	 * @return array<int, string>
	 */
	function prelaunch_login_body_classes( array $classes, string $action ): array {
		if ( prelaunch_is_password_setup_request() ) {
			$classes[] = 'prelaunch-password-setup';
		}

		if ( in_array( $action, array( 'login', '' ), true ) ) {
			$classes[] = 'prelaunch-primary-login';
		}

		return $classes;
	}

	add_filter( 'login_message', 'prelaunch_password_setup_login_message' );

	/**
	 * Inject advisor setup messaging across login/password screens.
	 *
	 * @param string $message Existing login message HTML.
	 *
	 * @return string
	 */
	function prelaunch_password_setup_login_message( string $message ): string {
		if ( ! prelaunch_is_password_setup_request() ) {
			if (
				in_array( prelaunch_get_login_screen_action(), array( 'resetpass', 'rp' ), true )
				&& prelaunch_is_advisor_password_reset()
			) {
				return prelaunch_render_password_setup_notice(
					esc_html__( 'Choose a password for your advisor account.', 'prelaunch-wp' )
				);
			}

			return $message;
		}

		$action = prelaunch_get_login_screen_action();

		if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
			return prelaunch_render_password_setup_notice(
				esc_html__(
					'Use your Travelcnc email address. We will send you a link to create your password.',
					'prelaunch-wp'
				)
			);
		}

		if ( in_array( $action, array( 'resetpass', 'rp' ), true ) ) {
			return prelaunch_render_password_setup_notice(
				esc_html__( 'Create your password below.', 'prelaunch-wp' )
			);
		}

		return $message;
	}

	/**
	 * Render a styled info notice for setup-flow screens.
	 *
	 * @param string $text Notice text HTML.
	 */
	function prelaunch_render_password_setup_notice( string $text ): string {
		return wp_get_admin_notice(
			$text,
			array(
				'type'               => 'info',
				'additional_classes' => array( 'message', 'prelaunch-password-setup-notice' ),
			)
		);
	}

	add_action( 'lostpassword_form', 'prelaunch_password_setup_lostpassword_form_fields' );

	/**
	 * Persist setup-flow context through the lost-password form POST.
	 */
	function prelaunch_password_setup_lostpassword_form_fields(): void {
		if ( ! prelaunch_is_password_setup_request() ) {
			return;
		}

		printf(
			'<input type="hidden" name="%1$s" value="1" />',
			esc_attr( PRELAUNCH_PASSWORD_SETUP_FIELD )
		);
	}

	add_filter( 'lostpassword_redirect', 'prelaunch_password_setup_lostpassword_redirect' );

	/**
	 * Keep setup-flow context on the check-email confirmation screen.
	 *
	 * @param string $redirect_to Redirect destination for the hidden form field.
	 */
	function prelaunch_password_setup_lostpassword_redirect( string $redirect_to ): string {
		if ( ! prelaunch_is_password_setup_request() ) {
			return $redirect_to;
		}

		if ( '' !== $redirect_to ) {
			return add_query_arg( PRELAUNCH_PASSWORD_SETUP_QUERY, '1', $redirect_to );
		}

		return add_query_arg(
			array(
				'checkemail'                      => 'confirm',
				PRELAUNCH_PASSWORD_SETUP_QUERY => '1',
			),
			'wp-login.php'
		);
	}

	add_filter( 'wp_login_errors', 'prelaunch_password_setup_checkemail_message', 20, 2 );

	/**
	 * Replace the default check-email copy during the setup flow.
	 *
	 * @param WP_Error $errors      Login screen errors/messages.
	 * @param string   $redirect_to Redirect destination.
	 */
	function prelaunch_password_setup_checkemail_message( WP_Error $errors, string $redirect_to ): WP_Error {
		if ( ! prelaunch_is_password_setup_request() || 'checkemail' !== prelaunch_get_login_screen_action() ) {
			return $errors;
		}

		if ( ! $errors->get_error_message( 'confirm' ) ) {
			return $errors;
		}

		$errors->remove( 'confirm' );
		$errors->add(
			'confirm',
			wp_kses_post(
				sprintf(
					/* translators: %s: login page URL. */
					__(
						'Check your Travelcnc email for a link to create your password, then visit the <a href="%s">login page</a>.',
						'prelaunch-wp'
					),
					esc_url( wp_login_url() )
				)
			),
			'message'
		);

		return $errors;
	}

	add_filter( 'gettext', 'prelaunch_password_setup_gettext', 10, 3 );

	/**
	 * Retarget core login strings during the setup flow.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 */
	function prelaunch_password_setup_gettext( string $translation, string $text, string $domain ): string {
		if ( 'default' !== $domain || ! prelaunch_is_password_setup_request() ) {
			return $translation;
		}

		$action = prelaunch_get_login_screen_action();

		if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
			if ( 'Lost Password' === $text ) {
				return __( 'Get Your Password', 'prelaunch-wp' );
			}

			if ( 'Username or Email Address' === $text ) {
				return __( 'Travelcnc Email Address:', 'prelaunch-wp' );
			}

			if ( 'Get New Password' === $text ) {
				return __( 'Get my password', 'prelaunch-wp' );
			}
		}

		if ( in_array( $action, array( 'resetpass', 'rp' ), true ) ) {
			if ( 'Reset Password' === $text ) {
				return __( 'Create Your Password', 'prelaunch-wp' );
			}

			if ( 'New password' === $text ) {
				return __( 'Password', 'prelaunch-wp' );
			}

			if ( 'Confirm new password' === $text ) {
				return __( 'Confirm password', 'prelaunch-wp' );
			}

			if ( 'Save Password' === $text ) {
				return __( 'Save password', 'prelaunch-wp' );
			}
		}

		if ( 'checkemail' === $action && 'Check your email' === $text ) {
			return __( 'Check your Travelcnc email', 'prelaunch-wp' );
		}

		return $translation;
	}

	add_filter( 'retrieve_password_title', 'prelaunch_password_setup_email_title', 10, 3 );

	/**
	 * Use setup-friendly subject line for advisor password emails.
	 *
	 * @param string  $title      Email subject.
	 * @param string  $user_login Username.
	 * @param WP_User $user_data  User object.
	 */
	function prelaunch_password_setup_email_title( string $title, string $user_login, WP_User $user_data ): string {
		if ( ! prelaunch_is_password_setup_request() ) {
			return $title;
		}

		return sprintf(
			/* translators: %s: Site name. */
			__( '[%s] Create your advisor password', 'prelaunch-wp' ),
			wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
		);
	}

	add_filter( 'retrieve_password_message', 'prelaunch_password_setup_email_message', 10, 4 );

	/**
	 * Use setup-friendly body copy for advisor password emails.
	 *
	 * @param string  $message    Default email body.
	 * @param string  $key        Password reset key.
	 * @param string  $user_login Username.
	 * @param WP_User $user_data  User object.
	 */
	function prelaunch_password_setup_email_message(
		string $message,
		string $key,
		string $user_login,
		WP_User $user_data
	): string {
		if ( ! prelaunch_is_password_setup_request() ) {
			return $message;
		}

		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$locale    = get_user_locale( $user_data );
		$reset_url = network_site_url(
			'wp-login.php?login=' . rawurlencode( $user_login ) . "&key=$key&action=rp",
			'login'
		) . '&wp_lang=' . $locale;

		$message  = __( 'You requested a link to create your advisor account password.', 'prelaunch-wp' ) . "\r\n\r\n";
		$message .= sprintf(
			/* translators: %s: Site name. */
			__( 'Site: %s', 'prelaunch-wp' ),
			$site_name
		) . "\r\n\r\n";
		$message .= __( 'Use the link below to choose your password:', 'prelaunch-wp' ) . "\r\n\r\n";
		$message .= $reset_url . "\r\n\r\n";
		$message .= __( 'If you did not request this, you can ignore this email.', 'prelaunch-wp' ) . "\r\n";

		if ( ! is_user_logged_in() && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$message .= "\r\n" . sprintf(
				/* translators: %s: IP address. */
				__( 'This request came from the IP address %s.', 'prelaunch-wp' ),
				sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) )
			) . "\r\n";
		}

		return $message;
	}
