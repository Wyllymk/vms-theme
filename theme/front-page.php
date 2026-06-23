<?php
/**
 * Front page — login / password reset landing.
 *
 * Logged-in users are bounced straight to their role-specific dashboard.
 * Guests see a full-bleed aurora hero with a frosted-glass auth card that
 * switches between login / forgot / reset views without a page reload.
 *
 * The theme switcher floats bottom-right as a pill so visitors can flip to
 * dark mode before they've even logged in — header.php's segmented toggle
 * isn't visible on this page because the navbar is hidden for guests.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Redirect logged-in users to their dashboard.
if ( is_user_logged_in() ) {
	wp_safe_redirect( vms_get_dashboard_url_for_role( vms_current_role() ) );
	exit;
}

$branding = vms_get_branding();

// Detect an incoming password-reset link (?key=…&login=…).
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$reset_key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
$reset_login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : '';
$is_reset    = ! empty( $reset_key ) && ! empty( $reset_login );

// ?view=forgot from lostpassword_url filter.
$initial_view = $is_reset ? 'reset' : ( ( $_GET['view'] ?? '' ) === 'forgot' ? 'forgot' : 'login' );
$redirect_to  = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
// phpcs:enable

get_header();
?>

<div x-data="loginPage()"
    class="relative min-h-screen flex items-center justify-center p-4 sm:p-6 overflow-hidden isolate">
    <!-- ═══════════════════════════════════════════════════════════════════
	     AURORA BACKDROP
	     Three drifting gradient orbs — the frosted card blur picks these
	     up and produces the signature glassmorphism colour bleed.
	     ═══════════════════════════════════════════════════════════════════ -->
    <div aria-hidden="true" class="absolute inset-0 -z-10">
        <div class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full
		            bg-gradient-to-br from-[var(--vms-primary)] to-[var(--vms-secondary)]
		            opacity-20 dark:opacity-30 blur-3xl animate-aurora"></div>
        <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] rounded-full
		            bg-gradient-to-tl from-[var(--vms-secondary)] to-fuchsia-500
		            opacity-20 dark:opacity-25 blur-3xl animate-aurora" style="animation-delay: -10s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] rounded-full
		            bg-[var(--vms-primary)] opacity-5 dark:opacity-10 blur-3xl"></div>

        <!-- Subtle dot grid — adds texture behind the blur -->
        <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]"
            style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 32px 32px;">
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
	     FLOATING THEME SWITCHER
	     Standalone pill with the same three-state logic as the header
	     component. Uses the root themeManager() scope from <html>.
	     ═══════════════════════════════════════════════════════════════════ -->
    <div class="fixed bottom-6 right-6 z-50">
        <div class="vms-theme-toggle shadow-glass-hover animate-float-in" :data-mode="darkMode" role="radiogroup"
            aria-label="<?php esc_attr_e( 'Theme preference', 'vms-theme' ); ?>">
            <span class="vms-theme-toggle-indicator" aria-hidden="true"></span>

            <button type="button" @click="setDarkMode('light')" :class="{ 'active': darkMode === 'light' }"
                :aria-checked="darkMode === 'light'" role="radio"
                aria-label="<?php esc_attr_e( 'Light mode', 'vms-theme' ); ?>">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
            <button type="button" @click="setDarkMode('dark')" :class="{ 'active': darkMode === 'dark' }"
                :aria-checked="darkMode === 'dark'" role="radio"
                aria-label="<?php esc_attr_e( 'Dark mode', 'vms-theme' ); ?>">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
            <button type="button" @click="setDarkMode('system')" :class="{ 'active': darkMode === 'system' }"
                :aria-checked="darkMode === 'system'" role="radio"
                aria-label="<?php esc_attr_e( 'Follow system theme', 'vms-theme' ); ?>">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
	     AUTH CARD
	     ═══════════════════════════════════════════════════════════════════ -->
    <div class="w-full max-w-md animate-float-in">
        <!-- Logo + Club Name -->
        <div class="text-center mb-8">
            <?php if ( ! empty( $branding['club_logo_url'] ) ) : ?>
            <img src="<?php echo esc_url( $branding['club_logo_url'] ); ?>"
                alt="<?php echo esc_attr( $branding['club_name'] ); ?>"
                class="h-20 w-auto max-w-[200px] mx-auto mb-4 object-contain drop-shadow-[0_8px_24px_rgba(var(--vms-primary-rgb),0.4)]">
            <?php else : ?>
            <!-- Gradient orb with club initial -->
            <div class="h-20 w-20 mx-auto mb-4 rounded-3xl
				            bg-gradient-brand
				            flex items-center justify-center
				            text-white text-3xl font-black
				            shadow-glow-lg animate-glow-pulse">
                <?php echo esc_html( mb_strtoupper( mb_substr( $branding['club_name'], 0, 1 ) ) ); ?>
            </div>
            <?php endif; ?>

            <h1 class="text-3xl sm:text-4xl font-black tracking-tight
			           bg-gradient-brand bg-clip-text text-transparent">
                <?php echo esc_html( $branding['club_name'] ); ?>
            </h1>
            <p class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-400">
                <?php esc_html_e( 'Visitor Management System', 'vms-theme' ); ?>
            </p>
        </div>

        <!-- Glass card -->
        <div class="relative bg-white/70 dark:bg-slate-900/70
		            backdrop-blur-2xl backdrop-saturate-150
		            rounded-3xl shadow-glass-hover
		            border border-white/60 dark:border-slate-700/50
		            p-6 sm:p-8 overflow-hidden">

            <!-- Top shine line -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-shine opacity-70" aria-hidden="true"></div>

            <!-- ────────────────────────────────────────────────────────────
			     LOGIN VIEW
			     ──────────────────────────────────────────────────────────── -->
            <div x-show="view === 'login'" x-transition:enter="transition ease-spring duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php esc_html_e( 'Welcome back', 'vms-theme' ); ?>
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        <?php esc_html_e( 'Sign in to continue to your dashboard', 'vms-theme' ); ?>
                    </p>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-5">
                    <!-- Username / Email -->
                    <div>
                        <label for="login-user"
                            class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            <?php esc_html_e( 'Username or Email', 'vms-theme' ); ?>
                        </label>
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <input id="login-user" type="text" x-model="loginForm.username" required
                                autocomplete="username" autofocus class="vms-input w-full pl-12 pr-4 py-3 rounded-2xl"
                                placeholder="<?php esc_attr_e( 'you@example.com', 'vms-theme' ); ?>">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="login-pass"
                            class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            <?php esc_html_e( 'Password', 'vms-theme' ); ?>
                        </label>
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input id="login-pass" :type="showPassword ? 'text' : 'password'"
                                x-model="loginForm.password" required autocomplete="current-password"
                                class="vms-input w-full pl-12 pr-12 py-3 rounded-2xl"
                                placeholder="<?php esc_attr_e( '••••••••', 'vms-theme' ); ?>">
                            <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" x-model="loginForm.remember"
                                class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-[var(--vms-primary)] focus:ring-[var(--vms-primary)] focus:ring-offset-0">
                            <span
                                class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200 transition-colors">
                                <?php esc_html_e( 'Remember me', 'vms-theme' ); ?>
                            </span>
                        </label>
                        <button type="button" @click="switchView('forgot')"
                            class="text-sm font-semibold text-[var(--vms-primary)] hover:text-[var(--vms-primary-hover)] transition-colors">
                            <?php esc_html_e( 'Forgot password?', 'vms-theme' ); ?>
                        </button>
                    </div>

                    <!-- Error banner -->
                    <div x-show="error" x-cloak x-transition
                        class="p-4 rounded-2xl bg-red-50/80 dark:bg-red-900/20 border border-red-200/80 dark:border-red-800/50 backdrop-blur-sm">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" :disabled="loading" class="group w-full py-3.5 px-6 bg-gradient-brand text-white font-bold rounded-2xl
					               shadow-glow-md hover:shadow-glow-lg
					               active:scale-[0.98]
					               disabled:opacity-50 disabled:cursor-not-allowed
					               transition-all duration-200 ease-spring
					               flex items-center justify-center gap-2">
                        <svg x-show="loading" x-cloak class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span
                            x-text="loading ? '<?php echo esc_js( __( 'Signing in…', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Sign In', 'vms-theme' ) ); ?>'"></span>
                        <svg x-show="!loading" class="w-5 h-5 group-hover:translate-x-1 transition-transform"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>
                </form>

                <!-- Member registration link -->
                <?php if ( vms_module_enabled( 'members' ) ) : ?>
                <div class="mt-6 pt-6 border-t border-slate-200/60 dark:border-slate-700/60 text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?php esc_html_e( 'Not a member yet?', 'vms-theme' ); ?>
                        <a href="<?php echo esc_url( home_url( '/member-register/' ) ); ?>"
                            class="font-semibold text-[var(--vms-primary)] hover:text-[var(--vms-primary-hover)] transition-colors">
                            <?php esc_html_e( 'Apply for membership', 'vms-theme' ); ?>
                        </a>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- ────────────────────────────────────────────────────────────
			     FORGOT PASSWORD VIEW
			     ──────────────────────────────────────────────────────────── -->
            <div x-show="view === 'forgot'" x-cloak x-transition:enter="transition ease-spring duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

                <button @click="switchView('login')"
                    class="flex items-center gap-1.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white mb-4 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <?php esc_html_e( 'Back to sign in', 'vms-theme' ); ?>
                </button>

                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php esc_html_e( 'Reset password', 'vms-theme' ); ?></h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        <?php esc_html_e( 'Enter your email and we will send you a reset link', 'vms-theme' ); ?>
                    </p>
                </div>

                <form @submit.prevent="handleForgot" class="space-y-5">
                    <div>
                        <label for="forgot-email"
                            class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            <?php esc_html_e( 'Email address', 'vms-theme' ); ?>
                        </label>
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <input id="forgot-email" type="email" x-model="forgotForm.email" required
                                autocomplete="email" class="vms-input w-full pl-12 pr-4 py-3 rounded-2xl"
                                placeholder="<?php esc_attr_e( 'you@example.com', 'vms-theme' ); ?>">
                        </div>
                    </div>

                    <div x-show="error" x-cloak x-transition
                        class="p-4 rounded-2xl bg-red-50/80 dark:bg-red-900/20 border border-red-200/80 dark:border-red-800/50">
                        <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                    </div>

                    <div x-show="success" x-cloak x-transition
                        class="p-4 rounded-2xl bg-emerald-50/80 dark:bg-emerald-900/20 border border-emerald-200/80 dark:border-emerald-800/50">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300" x-text="success"></p>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading || success"
                        class="w-full py-3.5 px-6 bg-gradient-brand text-white font-bold rounded-2xl shadow-glow-md hover:shadow-glow-lg active:scale-[0.98] disabled:opacity-50 transition-all duration-200 ease-spring flex items-center justify-center gap-2">
                        <svg x-show="loading" x-cloak class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span
                            x-text="loading ? '<?php echo esc_js( __( 'Sending…', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Send Reset Link', 'vms-theme' ) ); ?>'"></span>
                    </button>
                </form>
            </div>

            <!-- ────────────────────────────────────────────────────────────
			     RESET PASSWORD VIEW (from email link)
			     ──────────────────────────────────────────────────────────── -->
            <div x-show="view === 'reset'" x-cloak x-transition:enter="transition ease-spring duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php esc_html_e( 'Choose a new password', 'vms-theme' ); ?></h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        <?php
						printf(
							/* translators: %s: user login */
							esc_html__( 'For account %s', 'vms-theme' ),
							'<strong class="text-slate-700 dark:text-slate-300">' . esc_html( $reset_login ) . '</strong>'
						);
						?>
                    </p>
                </div>

                <form @submit.prevent="handleReset" class="space-y-5">
                    <div>
                        <label for="reset-pass"
                            class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            <?php esc_html_e( 'New password', 'vms-theme' ); ?>
                        </label>
                        <input id="reset-pass" type="password" x-model="resetForm.password" required minlength="8"
                            autocomplete="new-password" class="vms-input w-full px-4 py-3 rounded-2xl"
                            placeholder="<?php esc_attr_e( 'At least 8 characters', 'vms-theme' ); ?>">
                        <!-- Strength indicator -->
                        <div class="flex gap-1 mt-2">
                            <template x-for="i in 4" :key="i">
                                <div class="h-1 flex-1 rounded-full transition-colors"
                                    :class="passwordStrength >= i ? (passwordStrength < 3 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-slate-200 dark:bg-slate-700'">
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label for="reset-confirm"
                            class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            <?php esc_html_e( 'Confirm password', 'vms-theme' ); ?>
                        </label>
                        <input id="reset-confirm" type="password" x-model="resetForm.confirm" required minlength="8"
                            autocomplete="new-password" class="vms-input w-full px-4 py-3 rounded-2xl"
                            :class="resetForm.confirm && resetForm.password !== resetForm.confirm ? 'ring-2 ring-red-500' : ''">
                    </div>

                    <div x-show="error" x-cloak x-transition
                        class="p-4 rounded-2xl bg-red-50/80 dark:bg-red-900/20 border border-red-200/80 dark:border-red-800/50">
                        <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                    </div>

                    <div x-show="success" x-cloak x-transition
                        class="p-4 rounded-2xl bg-emerald-50/80 dark:bg-emerald-900/20 border border-emerald-200/80 dark:border-emerald-800/50">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300" x-text="success"></p>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading || success"
                        class="w-full py-3.5 px-6 bg-gradient-brand text-white font-bold rounded-2xl shadow-glow-md hover:shadow-glow-lg active:scale-[0.98] disabled:opacity-50 transition-all duration-200 ease-spring flex items-center justify-center gap-2">
                        <svg x-show="loading" x-cloak class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span
                            x-text="loading ? '<?php echo esc_js( __( 'Saving…', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Update Password', 'vms-theme' ) ); ?>'"></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Tiny footer -->
        <p class="text-center text-xs text-slate-400 dark:text-slate-600 mt-6">
            <?php
			printf(
				/* translators: %s: current year */
				esc_html__( '© %s · Secured by VMS', 'vms-theme' ),
				esc_html( gmdate( 'Y' ) )
			);
			?>
        </p>
    </div>
</div>

<script>
/**
 * Login page Alpine component.
 *
 * All three auth flows (login / forgot / reset) live in a single factory
 * so switching views is instant — no navigation, no flash of unstyled
 * content, just Alpine toggling x-show.
 */
function loginPage() {
    var ajax = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var redirectTo = <?php echo wp_json_encode( $redirect_to ); ?>;

    return {
        view: <?php echo wp_json_encode( $initial_view ); ?>,
        loading: false,
        error: '',
        success: '',
        showPassword: false,

        loginForm: {
            username: '',
            password: '',
            remember: false
        },
        forgotForm: {
            email: ''
        },
        resetForm: {
            password: '',
            confirm: '',
            key: <?php echo wp_json_encode( $reset_key ); ?>,
            login: <?php echo wp_json_encode( $reset_login ); ?>
        },

        // Naive password-strength meter: length + character-class variety.
        get passwordStrength() {
            var p = this.resetForm.password;
            if (!p) return 0;
            var score = 0;
            if (p.length >= 8) score++;
            if (p.length >= 12) score++;
            if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++;
            if (/\d/.test(p) && /[^A-Za-z0-9]/.test(p)) score++;
            return score;
        },

        switchView(v) {
            this.view = v;
            this.error = '';
            this.success = '';
        },

        async post(body) {
            var res = await fetch(ajax, {
                method: 'POST',
                credentials: 'same-origin',
                body: body
            });
            return res.json();
        },

        async handleLogin() {
            this.error = '';
            this.loading = true;

            // NB: action + nonce action + field name must match
            // VMS_Auth::ajax_login() *exactly* — see nonce-contract comment
            // in functions.php. The plugin expects:
            //   action='vms_login', nonce-action='vms_auth_nonce', field='nonce'.
            var fd = new FormData();
            fd.append('action', 'vms_login');
            fd.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'vms_auth_nonce' ) ); ?>);
            fd.append('username', this.loginForm.username);
            fd.append('password', this.loginForm.password);
            fd.append('remember', this.loginForm.remember ? '1' : '0');
            if (redirectTo) fd.append('redirect_to', redirectTo);

            try {
                var json = await this.post(fd);
                console.log('AJAX response:', json); // ← log full response

                if (json.success) {
                    window.location.href = json.data.redirect;
                } else {
                    // Log the full error object for debugging
                    console.error('Login failed:', json.data);
                    this.error = (json.data && json.data.message) ||
                        <?php echo wp_json_encode( __( 'Invalid username or password.', 'vms-theme' ) ); ?>;
                    this.loading = false;
                }
            } catch (e) {
                console.error('Network error:', e); // ← log network errors
                this.error =
                    <?php echo wp_json_encode( __( 'Network error. Please check your connection and try again.', 'vms-theme' ) ); ?>;
                this.loading = false;
            }
        },

        async handleForgot() {
            this.error = '';
            this.success = '';
            this.loading = true;

            var fd = new FormData();
            fd.append('action', 'vms_request_password_reset');
            fd.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'vms_auth_nonce' ) ); ?>);
            fd.append('email', this.forgotForm.email);

            try {
                var json = await this.post(fd);
                if (json.success) {
                    this.success = json.data.message;
                    this.forgotForm.email = '';
                } else {
                    this.error = (json.data && json.data.message) ||
                        <?php echo wp_json_encode( __( 'Could not send reset email.', 'vms-theme' ) ); ?>;
                }
            } catch (e) {
                this.error = <?php echo wp_json_encode( __( 'Network error. Please try again.', 'vms-theme' ) ); ?>;
            } finally {
                this.loading = false;
            }
        },

        async handleReset() {
            this.error = '';
            this.success = '';

            if (this.resetForm.password !== this.resetForm.confirm) {
                this.error = <?php echo wp_json_encode( __( 'Passwords do not match.', 'vms-theme' ) ); ?>;
                return;
            }
            if (this.resetForm.password.length < 8) {
                this.error =
                    <?php echo wp_json_encode( __( 'Password must be at least 8 characters.', 'vms-theme' ) ); ?>;
                return;
            }

            this.loading = true;

            var fd = new FormData();
            fd.append('action', 'vms_reset_password');
            fd.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'vms_auth_nonce' ) ); ?>);
            fd.append('key', this.resetForm.key);
            fd.append('login', this.resetForm.login);
            fd.append('password', this.resetForm.password);

            try {
                var json = await this.post(fd);
                if (json.success) {
                    this.success = json.data.message;
                    // Strip the key/login from the URL and flip back to login
                    // after a beat so the user sees the confirmation.
                    setTimeout(() => {
                        window.history.replaceState({}, '',
                            <?php echo wp_json_encode( home_url( '/' ) ); ?>);
                        this.switchView('login');
                    }, 2500);
                } else {
                    this.error = (json.data && json.data.message) ||
                        <?php echo wp_json_encode( __( 'Could not reset password. The link may have expired.', 'vms-theme' ) ); ?>;
                }
            } catch (e) {
                this.error = <?php echo wp_json_encode( __( 'Network error. Please try again.', 'vms-theme' ) ); ?>;
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>

<?php get_footer(); ?>