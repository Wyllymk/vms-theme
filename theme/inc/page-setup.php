<?php
/**
 * Page auto-creation on theme activation.
 *
 * Creates every page the VMS frontend needs and binds each one to its page
 * template via _wp_page_template meta. Safe to re-run: existing pages are
 * updated in place (slug + template fixed, content untouched).
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page definitions.
 *
 * Each entry: title, slug, template path relative to theme root, and
 * whether the page requires login (used for capability checks inside the
 * template, not here).
 *
 * @return array<int, array{title:string,slug:string,template:string,is_front?:bool}>
 */
function vms_theme_page_definitions(): array {
	return array(
		array(
			'title'    => __( 'Home', 'vms-theme' ),
			'slug'     => 'home',
			'template' => '', // front-page.php handles this automatically.
			'is_front' => true,
		),
		array(
			'title'    => __( 'Dashboard', 'vms-theme' ),
			'slug'     => 'dashboard',
			'template' => 'page-templates/dashboard.php',
		),
		array(
			'title'    => __( 'Guests', 'vms-theme' ),
			'slug'     => 'guests',
			'template' => 'page-templates/guests.php',
		),
		array(
			'title'    => __( 'Sign-In Desk', 'vms-theme' ),
			'slug'     => 'sign-in',
			'template' => 'page-templates/sign-in.php',
		),
		array(
			'title'    => __( 'Suppliers', 'vms-theme' ),
			'slug'     => 'suppliers',
			'template' => 'page-templates/suppliers.php',
		),
		array(
			'title'    => __( 'Accommodation', 'vms-theme' ),
			'slug'     => 'accommodation',
			'template' => 'page-templates/accommodation.php',
		),
		array(
			'title'    => __( 'Reciprocation', 'vms-theme' ),
			'slug'     => 'reciprocation',
			'template' => 'page-templates/reciprocation.php',
		),
		array(
			'title'    => __( 'Staff', 'vms-theme' ),
			'slug'     => 'employees',
			'template' => 'page-templates/employees.php',
		),
		array(
			'title'    => __( 'Members', 'vms-theme' ),
			'slug'     => 'members',
			'template' => 'page-templates/members.php',
		),
		array(
			'title'    => __( 'Member Registration', 'vms-theme' ),
			'slug'     => 'member-register',
			'template' => 'page-templates/member-register.php',
		),
		array(
			'title'    => __( 'Reports', 'vms-theme' ),
			'slug'     => 'reports',
			'template' => 'page-templates/reports.php',
		),
		array(
			'title'    => __( 'Audit Logs', 'vms-theme' ),
			'slug'     => 'audit-logs',
			'template' => 'page-templates/audit-logs.php',
		),
		array(
			'title'    => __( 'My Profile', 'vms-theme' ),
			'slug'     => 'profile',
			'template' => 'page-templates/profile.php',
		),
		array(
			'title'    => __( 'Reset Password', 'vms-theme' ),
			'slug'     => 'reset-password',
			'template' => 'page-templates/reset-password.php',
		),
		array(
			'title'    => __( 'Settings', 'vms-theme' ),
			'slug'     => 'settings',
			'template' => 'page-templates/settings.php',
		),
		array(
			'title'    => __( 'Module Builder', 'vms-theme' ),
			'slug'     => 'module-builder',
			'template' => 'page-templates/module-builder.php',
		),
	);
}

/**
 * Create or update every required page.
 *
 * Resolution strategy:
 *   1. Look up by stored ID (vms_theme_page_ids option) — survives renames.
 *   2. Fall back to slug lookup — catches pages that pre-exist the option.
 *   3. Create if neither finds a match.
 *
 * Every path updates the template binding so switching themes and coming
 * back always repairs the template association.
 *
 * @return void
 */
function vms_theme_create_pages(): void {
	$tracked  = get_option( 'vms_theme_page_ids', array() );
	$front_id = 0;

	foreach ( vms_theme_page_definitions() as $def ) {
		$slug    = $def['slug'];
		$page_id = 0;

		// 1. Try tracked ID.
		if ( isset( $tracked[ $slug ] ) ) {
			$existing = get_post( $tracked[ $slug ] );
			if ( $existing instanceof WP_Post && 'page' === $existing->post_type && 'trash' !== $existing->post_status ) {
				$page_id = $existing->ID;
			}
		}

		// 2. Try slug.
		if ( ! $page_id ) {
			$existing = get_page_by_path( $slug );
			if ( $existing instanceof WP_Post ) {
				$page_id = $existing->ID;
			}
		}

		// 3. Create.
		if ( ! $page_id ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => $def['title'],
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '<!-- VMS managed page. Content rendered by template. -->',
				)
			);

			if ( is_wp_error( $page_id ) || ! $page_id ) {
				continue;
			}
		} else {
			// Page exists — make sure slug and status are correct.
			$update = array( 'ID' => $page_id );
			$post   = get_post( $page_id );

			if ( $post->post_name !== $slug ) {
				$update['post_name'] = $slug;
			}
			if ( 'publish' !== $post->post_status ) {
				$update['post_status'] = 'publish';
			}
			if ( count( $update ) > 1 ) {
				wp_update_post( $update );
			}
		}

		// Always (re-)bind the template — this is the whole point.
		if ( ! empty( $def['template'] ) ) {
			update_post_meta( $page_id, '_wp_page_template', $def['template'] );
		}
		update_post_meta( $page_id, '_vms_managed', '1' );

		$tracked[ $slug ] = $page_id;

		if ( ! empty( $def['is_front'] ) ) {
			$front_id = $page_id;
		}
	}

	update_option( 'vms_theme_page_ids', $tracked, false );

	// Set static front page.
	if ( $front_id ) {
		update_option( 'page_on_front', $front_id );
		update_option( 'show_on_front', 'page' );
	}

	// Flush permalinks so the new pages resolve immediately.
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'vms_theme_create_pages' );

/**
 * Admin notice with a manual "Recreate Pages" button.
 *
 * Shows on Settings → Reading when VMS pages are missing so admins can
 * repair the setup without switching the theme off and on again.
 *
 * @return void
 */
function vms_theme_maybe_show_setup_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'options-reading', 'themes' ), true ) ) {
		return;
	}

	// Quick sanity check — if the dashboard page is missing, assume setup
	// hasn't run (or was undone).
	if ( get_page_by_path( 'dashboard' ) ) {
		return;
	}

	$url = wp_nonce_url(
		add_query_arg( 'vms_theme_setup', '1', admin_url( 'themes.php' ) ),
		'vms_theme_setup'
	);

	printf(
		'<div class="notice notice-warning"><p>%s <a href="%s" class="button button-primary">%s</a></p></div>',
		esc_html__( 'VMS Theme pages are not set up yet.', 'vms-theme' ),
		esc_url( $url ),
		esc_html__( 'Create Pages Now', 'vms-theme' )
	);
}
add_action( 'admin_notices', 'vms_theme_maybe_show_setup_notice' );

/**
 * Handle the manual setup trigger.
 *
 * @return void
 */
function vms_theme_handle_manual_setup(): void {
	if ( ! isset( $_GET['vms_theme_setup'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'vms-theme' ) );
	}

	check_admin_referer( 'vms_theme_setup' );

	vms_theme_create_pages();

	wp_safe_redirect( add_query_arg( 'vms_setup_done', '1', admin_url( 'themes.php' ) ) );
	exit;
}
add_action( 'admin_init', 'vms_theme_handle_manual_setup' );