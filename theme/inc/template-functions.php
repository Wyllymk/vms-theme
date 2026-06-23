<?php
/**
 * Template functions — integration layer between theme & VMS plugin.
 *
 * These wrappers degrade gracefully when the companion plugin is inactive
 * so the theme never produces a fatal error on a vanilla WP install.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get club branding from the VMS plugin, with sane fallbacks.
 *
 * @return array{club_name:string,club_logo_url:string,primary_color:string,secondary_color:string}
 */
function vms_get_branding(): array {
	$defaults = array(
		'club_name'       => get_bloginfo( 'name' ),
		'club_logo_url'   => has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : '',
		'primary_color'   => '#0ea5e9',
		'secondary_color' => '#8b5cf6',
	);

	// Try namespaced class first, then non-namespaced.
	$settings_class = null;
	if ( class_exists( '\WyllyMk\VMS\VMS_Settings' ) ) {
		$settings_class = '\WyllyMk\VMS\VMS_Settings';
	} elseif ( class_exists( 'VMS_Settings' ) ) {
		$settings_class = 'VMS_Settings';
	}

	if ( $settings_class && method_exists( $settings_class, 'get_branding' ) ) {
		$branding = $settings_class::get_branding();
		if ( is_array( $branding ) ) {
			return wp_parse_args( $branding, $defaults );
		}
	}

	return $defaults;
}

/**
 * Get the current user's VMS role.
 *
 * @return string|null One of member|chairman|general_manager|reception|gate|administrator, or null.
 */
function vms_current_role(): ?string {
	$roles_class = null;
	if ( class_exists( '\WyllyMk\VMS\VMS_Roles' ) ) {
		$roles_class = '\WyllyMk\VMS\VMS_Roles';
	} elseif ( class_exists( 'VMS_Roles' ) ) {
		$roles_class = 'VMS_Roles';
	}

	if ( $roles_class && method_exists( $roles_class, 'get_user_vms_role' ) ) {
		$role = $roles_class::get_user_vms_role();
		return is_string( $role ) ? $role : null;
	}

	// Fallback: map WP admin → administrator.
	if ( current_user_can( 'manage_options' ) ) {
		return 'administrator';
	}

	return null;
}

/**
 * Thin wrapper around current_user_can() for template readability.
 *
 * @param string $capability Capability to check.
 * @return bool
 */
function vms_can( string $capability ): bool {
	return current_user_can( $capability );
}

/**
 * Resolve the dashboard landing page URL for a given role.
 *
 * Page slugs are expected to exist (created by site admin or plugin activation):
 *   dashboard, sign-in, register-guest.
 *
 * @param string|null $role VMS role slug.
 * @return string Absolute URL.
 */
function vms_get_dashboard_url_for_role( ?string $role = null ): string {
	$role = $role ?? vms_current_role();

	$map = array(
		'reception'       => 'sign-in',
		'gate'            => 'sign-in',
		'member'          => 'guests',
		'chairman'        => 'dashboard',
		'general_manager' => 'dashboard',
		'administrator'   => 'dashboard',
	);

	$slug = $map[ $role ] ?? 'dashboard';
	$page = get_page_by_path( $slug );

	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	return home_url( '/' );
}

// -----------------------------------------------------------------------------
// Filters
// -----------------------------------------------------------------------------

/**
 * Add a role-specific class to <body> so CSS/JS can target per-role UI.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function vms_theme_body_classes( array $classes ): array {
	$role = vms_current_role();

	if ( $role ) {
		$classes[] = 'vms-role-' . sanitize_html_class( $role );
	} else {
		$classes[] = 'vms-role-none';
	}

	if ( is_page_template() ) {
		$classes[] = 'vms-has-dashboard';
	}

	return $classes;
}
add_filter( 'body_class', 'vms_theme_body_classes' );

/**
 * After a successful login, route the user to the dashboard that matches
 * their VMS role instead of wp-admin.
 *
 * @param string           $redirect_to Default redirect.
 * @param string           $requested   Requested redirect.
 * @param WP_User|WP_Error $user        Logged-in user or error.
 * @return string
 */
function vms_theme_login_redirect( $redirect_to, $requested, $user ) {
	if ( ! $user instanceof WP_User ) {
		return $redirect_to;
	}

	// Let real admins go wherever they asked.
	if ( user_can( $user, 'manage_options' ) && ! empty( $requested ) ) {
		return $redirect_to;
	}

	// Resolve role for this specific user (plugin reads current user internally,
	// so we temporarily set it — login_redirect fires before the cookie is read).
	$role = null;
	$roles_cls = null;
	if ( class_exists( '\WyllyMk\VMS\VMS_Roles' ) ) {
		$roles_cls = '\WyllyMk\VMS\VMS_Roles';
	} elseif ( class_exists( 'VMS_Roles' ) ) {
		$roles_cls = 'VMS_Roles';
	}

	if ( $roles_cls && method_exists( $roles_cls, 'get_user_vms_role' ) ) {
		wp_set_current_user( $user->ID );
		$role = $roles_cls::get_user_vms_role();
	}

	return vms_get_dashboard_url_for_role( $role );
}
add_filter( 'login_redirect', 'vms_theme_login_redirect', 10, 3 );

// -----------------------------------------------------------------------------
// wp-login.php interception
// -----------------------------------------------------------------------------

/**
 * Redirect wp-login.php to our custom front-page login.
 *
 * We still allow the native handler for:
 *   - logout (needs to clear the auth cookie server-side)
 *   - postpass (password-protected posts)
 *   - any request with interim-login (session-expired modal in wp-admin)
 *
 * Password reset links are rewritten to land on front-page.php with the
 * same key/login params, where Alpine picks them up and shows the reset form.
 *
 * @return void
 */
function vms_theme_intercept_wp_login(): void {
	// Only act on wp-login.php itself.
	if ( 'wp-login.php' !== ( $GLOBALS['pagenow'] ?? '' ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : 'login';

	// Let WordPress handle these natively — they do actual work and then
	// redirect, so the user never sees the ugly default form.
	$native_actions = array( 'logout', 'postpass', 'confirmaction' );
	if ( in_array( $action, $native_actions, true ) ) {
		return;
	}

	// Interim login (wp-admin session-expired modal) must stay native or
	// the iframe gets a broken full-page redirect.
	if ( isset( $_REQUEST['interim-login'] ) ) {
		return;
	}

	// Reset-password links: forward key + login to our front page.
	if ( in_array( $action, array( 'rp', 'resetpass' ), true ) ) {
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : '';

		// WP 5.3+ stores these in a cookie and drops the query args; read
		// the cookie if the GET params are empty.
		if ( ( empty( $key ) || empty( $login ) ) && isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ) {
			list( $login, $key ) = array_map(
				'sanitize_text_field',
				explode( ':', wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ), 2 )
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array( 'key' => $key, 'login' => rawurlencode( $login ) ),
				home_url( '/' )
			)
		);
		exit;
	}

	// Lost-password form → our front page shows the same thing.
	if ( 'lostpassword' === $action ) {
		wp_safe_redirect( add_query_arg( 'view', 'forgot', home_url( '/' ) ) );
		exit;
	}

	// Registration → public member-register page.
	if ( 'register' === $action ) {
		wp_safe_redirect( home_url( '/member-register/' ) );
		exit;
	}
	// phpcs:enable

	// Default: send to front-page login.
	wp_safe_redirect( home_url( '/' ) );
	exit;
}
add_action( 'init', 'vms_theme_intercept_wp_login' );

/**
 * Point WordPress-generated login URLs at our front page.
 *
 * Covers wp_login_url(), auth_redirect() and anything else that builds
 * a login link via the login_url filter.
 *
 * @param string $login_url    Default login URL.
 * @param string $redirect     Where to send the user after login.
 * @param bool   $force_reauth Whether to force re-authentication.
 * @return string
 */
function vms_theme_custom_login_url( $login_url, $redirect, $force_reauth ) {
	$url = home_url( '/' );
	if ( ! empty( $redirect ) ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
	}
	return $url;
}
add_filter( 'login_url', 'vms_theme_custom_login_url', 10, 3 );

/**
 * Point lost-password URLs at our front page.
 *
 * @param string $url Default lostpassword URL.
 * @return string
 */
function vms_theme_custom_lostpassword_url( $url ) {
	return add_query_arg( 'view', 'forgot', home_url( '/' ) );
}
add_filter( 'lostpassword_url', 'vms_theme_custom_lostpassword_url' );

// -----------------------------------------------------------------------------
// Theme-side AJAX auth handlers
//
// front-page.php posts to these (vms_theme_*) rather than the plugin's
// vms_login so the theme is self-contained — it works even if the plugin
// isn't active yet (e.g. during initial install ordering).
// -----------------------------------------------------------------------------

/**
 * Lightweight theme-side rate limiter for public auth actions.
 *
 * @param string $action Action identifier.
 * @param int    $max    Maximum attempts.
 * @param int    $window Window in seconds.
 * @param string $id     Optional user/email identifier.
 * @return bool
 */
function vms_theme_rate_limit( string $action, int $max, int $window, string $id = '' ): bool {
	if ( class_exists( '\WyllyMk\VMS\VMS_Security' ) && method_exists( '\WyllyMk\VMS\VMS_Security', 'rate_limit' ) ) {
		return \WyllyMk\VMS\VMS_Security::rate_limit( 'theme_' . $action, $max, $window, $id );
	}

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	$key = 'vms_theme_rl_' . md5( $action . '|' . $ip . '|' . $id );
	$hit = (int) get_transient( $key );

	if ( $hit >= $max ) {
		return false;
	}

	set_transient( $key, $hit + 1, $window );
	return true;
}

/**
 * AJAX: Log a user in from the front-page form.
 *
 * @return void
 */
function vms_theme_ajax_login(): void {
	check_ajax_referer( 'vms_theme_login', '_wpnonce' );

	$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
	$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$remember = ! empty( $_POST['remember'] );

	if ( ! vms_theme_rate_limit( 'login', 10, 300, $username ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many login attempts. Please try again later.', 'vms-theme' ) ), 429 );
	}

	if ( empty( $username ) || empty( $password ) ) {
		wp_send_json_error( array( 'message' => __( 'Username and password are required.', 'vms-theme' ) ) );
	}

	$user = wp_signon(
		array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => $remember,
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		// Surface pending-approval messages from the plugin's authenticate
		// filter verbatim; genericize everything else so we don't leak
		// whether a username exists.
		$code = $user->get_error_code();
		$msg  = in_array( $code, array( 'vms_pending_approval', 'vms_rejected' ), true )
			? $user->get_error_message()
			: __( 'Invalid username or password.', 'vms-theme' );

		wp_send_json_error( array( 'message' => $msg ) );
	}

	wp_set_current_user( $user->ID );

	$role     = vms_current_role();
	$redirect = vms_get_dashboard_url_for_role( $role );

	// Honour an explicit redirect_to if it's local.
	if ( ! empty( $_POST['redirect_to'] ) ) {
		$requested = wp_unslash( $_POST['redirect_to'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$requested = wp_validate_redirect( $requested, $redirect );
		if ( $requested ) {
			$redirect = $requested;
		}
	}

	wp_send_json_success( array( 'redirect' => $redirect, 'role' => $role ) );
}
add_action( 'wp_ajax_nopriv_vms_theme_login', 'vms_theme_ajax_login' );
add_action( 'wp_ajax_vms_theme_login', 'vms_theme_ajax_login' );

/**
 * AJAX: Send password reset email.
 *
 * @return void
 */
function vms_theme_ajax_forgot_password(): void {
	check_ajax_referer( 'vms_theme_forgot_password', '_wpnonce' );

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( empty( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter your email address.', 'vms-theme' ) ) );
	}

	if ( ! vms_theme_rate_limit( 'forgot_password', 3, 300, strtolower( $email ) ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many reset requests. Please try again in a few minutes.', 'vms-theme' ) ), 429 );
	}

	// Always report success regardless of whether the account exists —
	// don't leak account-existence information.
	retrieve_password( $email );

	wp_send_json_success(
		array( 'message' => __( 'If an account exists for that address, a password reset link has been sent. Please check your inbox.', 'vms-theme' ) )
	);
}
add_action( 'wp_ajax_nopriv_vms_theme_forgot_password', 'vms_theme_ajax_forgot_password' );
add_action( 'wp_ajax_vms_theme_forgot_password', 'vms_theme_ajax_forgot_password' );

/**
 * AJAX: Execute password reset with key + new password.
 *
 * @return void
 */
function vms_theme_ajax_reset_password(): void {
	check_ajax_referer( 'vms_theme_reset_password', '_wpnonce' );

	$key      = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	$login    = isset( $_POST['login'] ) ? sanitize_user( wp_unslash( $_POST['login'] ) ) : '';
	$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( empty( $key ) || empty( $login ) || empty( $password ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid reset request.', 'vms-theme' ) ) );
	}

	if ( ! vms_theme_rate_limit( 'reset_password', 5, 300, strtolower( $login ) ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many reset attempts. Please try again in a few minutes.', 'vms-theme' ) ), 429 );
	}

	if ( strlen( $password ) < 8 || ! preg_match( '/[A-Z]/', $password ) || ! preg_match( '/[a-z]/', $password ) || ! preg_match( '/\d/', $password ) ) {
		wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters and include uppercase, lowercase, and a number.', 'vms-theme' ) ) );
	}

	$user = check_password_reset_key( $key, $login );

	if ( is_wp_error( $user ) ) {
		wp_send_json_error(
			array( 'message' => __( 'This reset link is invalid or has expired. Please request a new one.', 'vms-theme' ) )
		);
	}

	reset_password( $user, $password );

	wp_send_json_success(
		array( 'message' => __( 'Password updated successfully. You can now sign in with your new password.', 'vms-theme' ) )
	);
}
add_action( 'wp_ajax_nopriv_vms_theme_reset_password', 'vms_theme_ajax_reset_password' );
add_action( 'wp_ajax_vms_theme_reset_password', 'vms_theme_ajax_reset_password' );

// -----------------------------------------------------------------------------
// Module gating for page templates
// -----------------------------------------------------------------------------

/**
 * Check whether a module is enabled. Returns true when the plugin isn't
 * active so the theme never shows a dead-end on a partial install.
 *
 * @param string $module Module key (guests|accommodation|suppliers|…).
 * @return bool
 */
function vms_module_enabled( string $module ): bool {
	if ( class_exists( '\WyllyMk\VMS\VMS_Settings' ) ) {
		return \WyllyMk\VMS\VMS_Settings::is_module_enabled( $module );
	}
	return true;
}

/**
 * Guard a page template against access when its backing module is disabled.
 *
 * Call this at the top of a page template after the login check. If the
 * module is disabled the request is served as a 404 so disabled features
 * are fully invisible — no "module disabled" placeholder, no dead link.
 *
 * @param string $module Module key.
 * @return void
 */
function vms_require_module( string $module ): void {
	if ( vms_module_enabled( $module ) ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' );
	exit;
}

/**
 * Guard a page template by capability.
 *
 * @param string $capability Required capability.
 * @return void
 */
function vms_require_capability( string $capability ): void {
	if ( current_user_can( $capability ) ) {
		return;
	}

	wp_safe_redirect( vms_get_dashboard_url_for_role( vms_current_role() ) );
	exit;
}
