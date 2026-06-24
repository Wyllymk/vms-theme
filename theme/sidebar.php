<?php
/**
 * Dashboard sidebar navigation — glassmorphism edition.
 *
 * Rendered conditionally based on enabled modules and user role.
 * Uses Alpine.js for mobile off-canvas state; active-item glow is pure CSS.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	return;
}

$role        = vms_current_role();
$current_url = trailingslashit( get_permalink() );

/**
 * Navigation items definition.
 *
 * Each item: slug, label, icon (SVG <path> markup), module key (or null
 * to always show), and the roles allowed to see it (empty = everyone).
 */
$nav_items = array(
	array(
		'slug'   => 'dashboard',
		'label'  => __( 'Dashboard', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
		'module' => null,
		'roles'  => array( 'administrator', 'chairman', 'general_manager' ),
	),
	array(
		'slug'   => 'sign-in',
		'label'  => __( 'Sign-In Desk', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
		'module' => null,
		'roles'  => array( 'administrator', 'chairman', 'general_manager', 'reception', 'gate' ),
	),
	array(
		'slug'   => 'guests',
		'label'  => __( 'Guests', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
		'module' => 'guests',
		'roles'  => array( 'administrator', 'chairman', 'general_manager', 'reception', 'member' ),
	),
	array(
		'slug'   => 'members',
		'label'  => __( 'Members', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
		'module' => 'members',
		'roles'  => array( 'administrator', 'chairman', 'general_manager', 'reception' ),
	),
	array(
		'slug'   => 'employees',
		'label'  => __( 'Staff', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>',
		'module' => 'employees',
		'roles'  => array( 'administrator', 'chairman', 'general_manager' ),
	),
	array(
		'slug'   => 'suppliers',
		'label'  => __( 'Suppliers', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
		'module' => 'suppliers',
		'roles'  => array( 'administrator', 'chairman', 'general_manager', 'gate' ),
	),
	array(
		'slug'   => 'accommodation',
		'label'  => __( 'Accommodation', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
		'module' => 'accommodation',
		'roles'  => array( 'administrator', 'chairman', 'general_manager', 'gate' ),
	),
	array(
		'slug'   => 'reciprocation',
		'label'  => __( 'Reciprocating Clubs', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>',
		'module' => 'reciprocation',
		'roles'  => array( 'administrator', 'chairman', 'general_manager', 'gate' ),
	),
	array(
		'slug'   => 'reports',
		'label'  => __( 'Reports', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
		'module' => 'reports',
		'roles'  => array( 'administrator', 'chairman', 'general_manager' ),
	),
	array(
		'slug'   => 'audit-logs',
		'label'  => __( 'Audit Logs', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
		'module' => null,
		'roles'  => array( 'administrator', 'chairman', 'general_manager' ),
	),
	array(
		'slug'   => 'admin',
		'label'  => __( 'Admin Panel', 'vms-theme' ),
		'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
		'module' => null,
		'roles'  => array( 'administrator' ),
	),
);

/**
 * Resolve the VMS_Settings class regardless of namespace aliasing.
 *
 * The previous implementation checked `class_exists('VMS_Settings')` (no
 * leading backslash) and then unconditionally called the namespaced variant.
 * On installs without a global class alias that check fails silently and
 * leaves every module "enabled" — which masked the real accommodation bug
 * in the plugin but broke in the opposite direction when an alias *did*
 * exist. Probe both forms and return whichever resolves.
 */
$vms_settings_class = null;
if ( class_exists( '\WyllyMk\VMS\VMS_Settings' ) ) {
	$vms_settings_class = '\WyllyMk\VMS\VMS_Settings';
} elseif ( class_exists( 'VMS_Settings' ) ) {
	$vms_settings_class = 'VMS_Settings';
}

$stagger = 0; // Entrance-animation stagger counter — increments per visible item.
?>

<aside id="vms-sidebar"
    class="fixed bottom-0 left-0 z-40 w-64 overflow-y-auto transition-transform duration-300 -translate-x-full border-r top-16 bg-white/55 dark:bg-slate-900/55 backdrop-blur-2xl backdrop-saturate-150 border-white/30 dark:border-slate-700/30 shadow-glass ease-spring lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0 shadow-glass-hover' : '-translate-x-full lg:translate-x-0'"
    aria-label="<?php esc_attr_e( 'Dashboard navigation', 'vms-theme' ); ?>">
    <!-- Gradient wash at the top — fades into the blur -->
    <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[rgba(var(--vms-primary-rgb),0.08)] to-transparent pointer-events-none"
        aria-hidden="true"></div>

    <nav class="relative flex flex-col gap-1 p-3">
        <?php foreach ( $nav_items as $item ) :

			// ─── Module gating ──────────────────────────────────────────
			// Default to visible if the plugin/Settings class is absent so
			// the theme never renders an empty sidebar on a partial install.
			if ( $item['module'] ) {
				$module_enabled = true;
				if ( $vms_settings_class && method_exists( $vms_settings_class, 'is_module_enabled' ) ) {
					$module_enabled = $vms_settings_class::is_module_enabled( $item['module'] );
				}
				if ( ! $module_enabled ) {
					continue;
				}
			}

			// ─── Role gating ────────────────────────────────────────────
			if ( ! empty( $item['roles'] ) && ! in_array( $role, $item['roles'], true ) ) {
				continue;
			}

			$item_url  = vms_get_page_url( $item['slug'] );
			$is_active = trailingslashit( $item_url ) === $current_url;
			$delay     = $stagger * 0.04;
			$stagger++;
			?>
        <a href="<?php echo esc_url( $item_url ); ?>"
            class="vms-nav-item group animate-float-in <?php echo $is_active ? 'active' : ''; ?>"
            style="--vms-delay: <?php echo esc_attr( $delay ); ?>s" <?php if ( $is_active ) : ?>aria-current="page"
            <?php endif; ?> @click="if (window.innerWidth < 1024) sidebarOpen = false">
            <svg class="w-5 h-5 transition-transform duration-200 shrink-0 group-hover:scale-110" fill="none"
                stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG paths are hardcoded above. ?>
            </svg>
            <span class="truncate"><?php echo esc_html( $item['label'] ); ?></span>

            <?php if ( $is_active ) : ?>
            <!-- Active indicator — pulsing dot -->
            <span class="flex items-center ml-auto" aria-hidden="true">
                <span class="relative flex w-2 h-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--vms-primary)] opacity-60"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[var(--vms-primary)]"></span>
                </span>
            </span>
            <?php else : ?>
            <svg class="w-4 h-4 ml-auto transition-all duration-200 -translate-x-2 opacity-0 group-hover:opacity-100 group-hover:translate-x-0"
                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Sidebar Footer — version badge in a tiny glass pill -->
    <div class="absolute bottom-0 left-0 right-0 p-4">
        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[0.65rem] font-semibold
			             bg-white/40 dark:bg-slate-800/40
			             backdrop-blur-sm
			             border border-white/40 dark:border-slate-700/40
			             rounded-full
			             text-slate-500 dark:text-slate-400">
                <span class="relative flex h-1.5 w-1.5">
                    <span
                        class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-emerald-400"></span>
                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                </span>
                <?php
				printf(
					/* translators: %s: VMS Theme version */
					esc_html__( 'VMS v%s', 'vms-theme' ),
					esc_html( '2.0.0' )
				);
				?>
            </span>
        </div>
    </div>
</aside>