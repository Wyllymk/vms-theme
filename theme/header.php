<?php
/**
 * Theme header — glassmorphism top bar.
 *
 * Layout: fixed glass navbar (blur + saturate) → mobile-off-canvas sidebar
 * → scrollable main content. The segmented theme switch (light/dark/system)
 * lives in the right-hand cluster and is driven by the themeManager() Alpine
 * component declared on <html>.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branding = vms_get_branding();
$role     = vms_current_role();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> x-data="themeManager()"
    x-bind:class="{ 'dark': darkMode === 'dark' || (darkMode === 'system' && prefersDark) }" class="scroll-smooth">

<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="<?php echo esc_attr( $branding['primary_color'] ?? '#0ea5e9' ); ?>">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php
	/**
	 * FOUC guard — runs before Alpine hydrates.
	 *
	 * Alpine's x-bind:class evaluates after the bundle loads (deferred to the
	 * footer), so without this inline snippet the page would flash in light
	 * mode for a frame. We replicate the same resolution logic here: read the
	 * persisted preference from localStorage, fall back to the system media
	 * query, then set the `dark` class synchronously.
	 */
	?>
    <script>
    (function() {
        try {
            var stored = localStorage.getItem('vms-theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = stored === 'dark' || ((stored === 'system' || stored === null) && prefersDark);
            if (isDark) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    })();
    </script>
    <?php wp_head(); ?>
</head>

<body <?php body_class( 'text-slate-900 dark:text-slate-100 antialiased min-h-screen' ); ?>
    x-data="{ sidebarOpen: false, userMenuOpen: false }"
    @keydown.escape.window="sidebarOpen = false; userMenuOpen = false">

    <?php if ( is_user_logged_in() ) : ?>
    <!-- ═══════════════════════════════════════════════════════════════════
	     TOP NAVIGATION BAR
	     Glass panel with heavy blur — floats above the aurora background.
	     Responsive: hamburger (< lg), logo, segmented theme switch, avatar.
	     ═══════════════════════════════════════════════════════════════════ -->
    <nav class="fixed top-0 left-0 right-0 z-50
	            bg-white/55 dark:bg-slate-900/55
	            backdrop-blur-2xl backdrop-saturate-150
	            border-b border-white/40 dark:border-slate-700/40
	            shadow-glass
	            supports-[backdrop-filter]:bg-white/40 dark:supports-[backdrop-filter]:bg-slate-900/40">
        <!-- Top shine line — signature glass highlight -->
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-shine opacity-60" aria-hidden="true"></div>

        <div class="flex items-center justify-between h-16 gap-2 px-3 sm:px-4 lg:px-6">
            <!-- ─── Left cluster ─────────────────────────────────────── -->
            <div class="flex items-center min-w-0 gap-2 sm:gap-3">
                <!-- Mobile hamburger (< 1024px) -->
                <!-- .stop prevents the click from bubbling to any @click.outside
				     listener on the drawer itself — which would immediately
				     close the menu on the same event that opened it.
				     Closing is handled by the scrim overlay further down. -->
                <button type="button" @click.stop="sidebarOpen = !sidebarOpen" class="lg:hidden shrink-0 p-2.5 rounded-xl
					       text-slate-600 dark:text-slate-300
					       hover:bg-white/60 dark:hover:bg-slate-800/60
					       hover:shadow-glow-sm
					       active:scale-95
					       transition-all duration-200 ease-spring" :aria-expanded="sidebarOpen" aria-controls="vms-sidebar"
                    aria-label="<?php esc_attr_e( 'Toggle navigation menu', 'vms-theme' ); ?>">
                    <svg x-show="!sidebarOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="sidebarOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Logo + Club Name -->
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
                    class="flex items-center gap-2.5 shrink-0 group min-w-0">
                    <?php if ( ! empty( $branding['club_logo_url'] ) ) : ?>
                    <!-- Inline max-width/height rather than Tailwind arbitrary
						     utilities so the constraint survives a stale CSS build.
						     Without a hard cap, a wide SVG logo will happily fill
						     the whole flex row on mobile. -->
                    <img src="<?php echo esc_url( $branding['club_logo_url'] ); ?>"
                        alt="<?php echo esc_attr( $branding['club_name'] ); ?>" class="object-contain w-auto transition-transform duration-300 shrink-0 ease-spring group-hover:scale-110"
                        style="max-width:160px;max-height:36px;height:36px;filter:drop-shadow(0 2px 8px rgba(var(--vms-primary-rgb),0.3));">
                    <?php else : ?>
                    <!-- Fallback brand mark — gradient orb with initial -->
                    <div class="flex items-center justify-center text-lg font-bold text-white transition-all duration-300 h-9 w-9 shrink-0 rounded-xl bg-gradient-brand shadow-glow-sm ease-spring group-hover:scale-110 group-hover:shadow-glow-md">
                        <?php echo esc_html( mb_strtoupper( mb_substr( $branding['club_name'], 0, 1 ) ) ); ?>
                    </div>
                    <?php endif; ?>

                    <span class="hidden sm:inline-block text-lg font-bold tracking-tight
					             bg-gradient-brand bg-clip-text text-transparent
					             truncate max-w-[140px] md:max-w-[200px]">
                        <?php echo esc_html( $branding['club_name'] ); ?>
                    </span>
                </a>
            </div>

            <!-- ─── Right cluster ────────────────────────────────────── -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- ═══ THEME SWITCHER — segmented pill ═══════════════════
				     Three-state toggle with a sliding gradient indicator.
				     The indicator position is CSS-driven via [data-mode] on
				     the wrapper (.vms-theme-toggle[data-mode="…"] rules in
				     theme.css), so the spring animation comes from CSS not JS.
				     ═══════════════════════════════════════════════════════ -->
                <div class="vms-theme-toggle" :data-mode="darkMode" role="radiogroup"
                    aria-label="<?php esc_attr_e( 'Theme preference', 'vms-theme' ); ?>">
                    <!-- Sliding indicator -->
                    <span class="vms-theme-toggle-indicator" aria-hidden="true"></span>

                    <!-- Light -->
                    <button type="button" @click="setDarkMode('light')" :class="{ 'active': darkMode === 'light' }"
                        :aria-checked="darkMode === 'light'" role="radio"
                        aria-label="<?php esc_attr_e( 'Light mode', 'vms-theme' ); ?>">
                        <svg class="w-4 h-4 sm:w-[18px] sm:h-[18px]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    <!-- Dark -->
                    <button type="button" @click="setDarkMode('dark')" :class="{ 'active': darkMode === 'dark' }"
                        :aria-checked="darkMode === 'dark'" role="radio"
                        aria-label="<?php esc_attr_e( 'Dark mode', 'vms-theme' ); ?>">
                        <svg class="w-4 h-4 sm:w-[18px] sm:h-[18px]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <!-- System -->
                    <button type="button" @click="setDarkMode('system')" :class="{ 'active': darkMode === 'system' }"
                        :aria-checked="darkMode === 'system'" role="radio"
                        aria-label="<?php esc_attr_e( 'Follow system theme', 'vms-theme' ); ?>">
                        <svg class="w-4 h-4 sm:w-[18px] sm:h-[18px]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>

                <!-- ═══ USER MENU ═════════════════════════════════════════ -->
                <div class="relative">
                    <button type="button" @click="userMenuOpen = !userMenuOpen" @click.outside="userMenuOpen = false"
                        class="group flex items-center gap-2 p-1 pr-2 rounded-2xl
						       bg-white/40 dark:bg-slate-800/40
						       backdrop-blur-md 
						       border border-white/50 dark:border-slate-700/50
						       hover:border-[rgba(var(--vms-primary-rgb),0.4)]
						       hover:shadow-glow-sm
						       transition-all duration-200 ease-spring" :aria-expanded="userMenuOpen" aria-haspopup="true">
                        <!-- Avatar with glow ring on hover -->
                        <div class="relative w-8 h-8 rounded-xl
						            bg-gradient-brand
						            flex items-center justify-center
						            text-slate-950 dark:text-white text-sm font-bold
						            shadow-[0_2px_8px_rgba(var(--vms-primary-rgb),0.4)]
						            transition-transform duration-200 group-hover:scale-105">
                            <?php echo esc_html( mb_strtoupper( mb_substr( wp_get_current_user()->display_name, 0, 1 ) ) ); ?>
                            <!-- Online pulse dot -->
                            <span
                                class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-800"></span>
                        </div>

                        <span
                            class="hidden md:inline-block text-sm font-semibold text-slate-700 dark:text-slate-200 max-w-[100px] truncate">
                            <?php echo esc_html( wp_get_current_user()->display_name ); ?>
                        </span>

                        <svg class="hidden w-4 h-4 transition-transform duration-200 md:block text-slate-400"
                            :class="{ 'rotate-180': userMenuOpen }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- User Dropdown — floating glass panel -->
                    <div x-show="userMenuOpen" x-cloak x-transition:enter="transition ease-spring duration-200"
                        x-transition:enter-start="opacity-0 scale-90 -translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-smooth duration-150"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                        class="absolute right-0 z-50 w-64 mt-3 overflow-hidden border bg-white/90 dark:bg-slate-800/90 backdrop-blur-2xl backdrop-saturate-150 rounded-2xl border-white/50 dark:border-slate-700/50 shadow-glass-hover" role="menu">
                        <!-- Top shine -->
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-shine" aria-hidden="true"></div>

                        <!-- User info header with gradient wash -->
                        <div class="relative px-4 py-4 border-b border-slate-200/50 dark:border-slate-700/50 bg-gradient-brand-soft">
                            <p class="text-sm font-bold text-slate-900 dark:text-white">
                                <?php echo esc_html( wp_get_current_user()->display_name ); ?>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                <?php echo esc_html( wp_get_current_user()->user_email ); ?>
                            </p>
                            <?php if ( $role ) : ?>
                            <span class="inline-flex items-center gap-1 mt-2 px-2.5 py-1 text-[0.65rem] font-bold tracking-wide uppercase rounded-full
								             bg-gradient-brand text-white shadow-glow-sm">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>
                                <?php echo esc_html( ucwords( str_replace( '_', ' ', $role ) ) ); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Menu links -->
                        <div class="py-1.5">
                            <a href="<?php echo esc_url( vms_get_page_url( 'profile' ) ); ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium
								       text-slate-700 dark:text-slate-300
								       hover:bg-gradient-brand-soft hover:text-[var(--vms-primary)]
								       transition-colors" role="menuitem">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <?php esc_html_e( 'My Profile', 'vms-theme' ); ?>
                            </a>

                            <?php if ( current_user_can( 'manage_options' ) ) : ?>
                            <a href="<?php echo esc_url( vms_get_page_url( 'admin' ) ); ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium
									       text-slate-700 dark:text-slate-300
									       hover:bg-gradient-brand-soft hover:text-[var(--vms-primary)]
									       transition-colors" role="menuitem">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <?php esc_html_e( 'Admin Panel', 'vms-theme' ); ?>
                            </a>

                            <a href="<?php echo esc_url( admin_url() ); ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium
									       text-slate-700 dark:text-slate-300
									       hover:bg-gradient-brand-soft hover:text-[var(--vms-primary)]
									       transition-colors" role="menuitem">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?php esc_html_e( 'WP Admin', 'vms-theme' ); ?>
                            </a>
                            <?php endif; ?>
                        </div>

                        <!-- Sign out -->
                        <div class="border-t border-slate-200/50 dark:border-slate-700/50 py-1.5">
                            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium
								       text-red-600 dark:text-red-400
								       hover:bg-red-50/80 dark:hover:bg-red-900/20
								       transition-colors" role="menuitem">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <?php esc_html_e( 'Sign Out', 'vms-theme' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ═══════════════════════════════════════════════════════════════════
	     MOBILE SIDEBAR OVERLAY
	     Full-viewport scrim with blur — tapping closes the drawer.
	     ═══════════════════════════════════════════════════════════════════ -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-md lg:hidden"
        x-transition:enter="transition ease-smooth duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-smooth duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" aria-hidden="true"></div>

    <!-- ═══════════════════════════════════════════════════════════════════
	     MAIN LAYOUT WRAPPER
	     pt-16 offsets the fixed navbar height; lg:ml-64 reserves sidebar.
	     ═══════════════════════════════════════════════════════════════════ -->
    <div class="flex min-h-screen pt-16">
        <?php get_sidebar(); ?>

        <main class="flex-1 max-w-full min-w-0 p-4 lg:ml-64 sm:p-5 md:p-6 lg:p-8">
            <?php else : ?>
            <!-- Guest (not logged in) layout — full-bleed hero -->
            <main class="min-h-screen">
                <?php endif; ?>

                <?php vms_render_toast(); ?>