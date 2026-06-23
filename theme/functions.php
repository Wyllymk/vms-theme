<?php
/**
 * VMS Theme — bootstrap file.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------

define( 'VMS_THEME_VERSION', '2.0.0' );
define( 'VMS_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'VMS_THEME_URL', trailingslashit( get_template_directory_uri() ) );

// -----------------------------------------------------------------------------
// Includes
// -----------------------------------------------------------------------------

require_once VMS_THEME_DIR . 'inc/template-functions.php';
require_once VMS_THEME_DIR . 'inc/page-setup.php';

if ( file_exists( VMS_THEME_DIR . 'inc/template-tags.php' ) ) {
	require_once VMS_THEME_DIR . 'inc/template-tags.php';
}

// -----------------------------------------------------------------------------
// Theme setup
// -----------------------------------------------------------------------------

/**
 * Register core theme features and nav menus.
 *
 * @return void
 */
function vms_theme_setup(): void {
	load_theme_textdomain( 'vms-theme', VMS_THEME_DIR . 'languages' );

	// Globally disable the admin bar on the frontend for all users.
	add_filter( 'show_admin_bar', '__return_false' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'editor-style.css' );

	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		)
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);

	register_nav_menus(
		array(
			'primary'           => __( 'Primary Navigation', 'vms-theme' ),
			'dashboard-sidebar' => __( 'Dashboard Sidebar', 'vms-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'vms_theme_setup' );

// -----------------------------------------------------------------------------
// Assets
// -----------------------------------------------------------------------------

/**
 * Enqueue compiled theme assets and expose runtime config to JS.
 *
 * @return void
 */
function vms_theme_enqueue_assets(): void {
	wp_enqueue_style( 'vms-theme-style', get_stylesheet_uri(), array(), VMS_THEME_VERSION );
	wp_enqueue_script( 'vms-theme-main', get_template_directory_uri() . '/js/script.min.js', array(), VMS_THEME_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// -----------------------------------------------------------------------
	// Build the vmsTheme runtime object.
	// -----------------------------------------------------------------------
	$branding = vms_get_branding();
	$role     = vms_current_role();

	// Enabled plugin modules — used to conditionally render sidebar items.
	$module_keys     = array( 'guests', 'signin', 'suppliers', 'accommodation', 'reciprocation', 'employees', 'reports', 'members', 'settings' );
	$enabled_modules = array();

	$settings_cls = null;
	if ( class_exists( '\WyllyMk\VMS\VMS_Settings' ) ) {
		$settings_cls = '\WyllyMk\VMS\VMS_Settings';
	} elseif ( class_exists( 'VMS_Settings' ) ) {
		$settings_cls = 'VMS_Settings';
	}

	foreach ( $module_keys as $key ) {
		if ( $settings_cls && method_exists( $settings_cls, 'is_module_enabled' ) ) {
			$enabled_modules[ $key ] = (bool) $settings_cls::is_module_enabled( $key );
		} else {
			$enabled_modules[ $key ] = true;
		}
	}

	wp_localize_script(
		'vms-theme-main',
		'vmsTheme',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'restUrl'     => esc_url_raw( rest_url() ),
			'nonces'      => array(
				// ───────────────────────────────────────────────────────
				// NONCE CONTRACT — these are the *only* nonce actions the
				// theme-side JS should ever send. Every plugin AJAX handler
				// must verify against one of these exact action strings.
				// The POST field name is always 'nonce' (see Base_Ajax_Trait).
				// ───────────────────────────────────────────────────────
				'guest'    => wp_create_nonce( 'vms_guest_nonce' ),    // guests, visits, members, suppliers, accom, recip, reports
				'settings' => wp_create_nonce( 'vms_settings_nonce' ), // plugin settings
				'audit'    => wp_create_nonce( 'vms_audit_nonce' ),    // audit-log queries/exports
				'auth'     => wp_create_nonce( 'vms_auth_nonce' ),     // login, logout, password reset
				// Consumed by page-templates/admin.php → vms_admin_run_tests
				// et al. Separate from 'vms_admin_nonce' (wp-admin-side) so
				// the frontend panel can be locked down independently.
				'admin'    => wp_create_nonce( 'vms_admin_panel' ),
			),
			'branding'    => $branding,
			'currentRole' => $role,
			'currentUser' => array(
				'id'          => get_current_user_id(),
				'displayName' => is_user_logged_in() ? wp_get_current_user()->display_name : '',
			),
			'modules'     => $enabled_modules,
			'dashboardUrl' => vms_get_dashboard_url_for_role( $role ),
			'i18n'        => array(
				'loading'       => __( 'Loading…', 'vms-theme' ),
				'error'         => __( 'Something went wrong. Please try again.', 'vms-theme' ),
				'confirm'       => __( 'Are you sure?', 'vms-theme' ),
				'noResults'     => __( 'No results found.', 'vms-theme' ),
				'saved'         => __( 'Saved successfully.', 'vms-theme' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'vms_theme_enqueue_assets' );

/**
 * Inject branding CSS variables into <head> so Tailwind utilities that
 * reference var(--vms-primary) resolve correctly per-club.
 *
 * @return void
 */
function vms_theme_inline_branding_vars(): void {
	$branding  = vms_get_branding();
	$primary   = sanitize_hex_color( $branding['primary_color'] ?? '' ) ?: '#0ea5e9';
	$secondary = sanitize_hex_color( $branding['secondary_color'] ?? '' ) ?: '#8b5cf6';

	/**
	 * Convert a #rrggbb hex string to a comma-separated "r, g, b" triple.
	 *
	 * The glassmorphism layer (theme.css) composes glow shadows and gradient
	 * borders with `rgba(var(--vms-primary-rgb), 0.3)` — those need the raw
	 * channel values, not the hex, so we emit both forms.
	 */
	$hex_to_rgb = static function ( string $hex ): string {
		$hex = ltrim( $hex, '#' );
		// sanitize_hex_color() upstream already filtered out 3-digit and
		// invalid inputs, so sscanf on 6-digit is safe here.
		$parts = sscanf( $hex, '%02x%02x%02x' );
		return $parts ? implode( ', ', $parts ) : '14, 165, 233';
	};

	$css = sprintf(
		':root{--vms-primary:%1$s;--vms-primary-rgb:%2$s;--vms-secondary:%3$s;--vms-secondary-rgb:%4$s;}',
		esc_attr( $primary ),
		esc_attr( $hex_to_rgb( $primary ) ),
		esc_attr( $secondary ),
		esc_attr( $hex_to_rgb( $secondary ) )
	);

	wp_add_inline_style( 'vms-theme-style', $css );
}
add_action( 'wp_enqueue_scripts', 'vms_theme_inline_branding_vars', 20 );