<?php
/* Template Name: VMS Member Registration */

/**
 * Public member-registration page.
 *
 * Anonymous visitors complete a form; on submit the plugin creates a WP user
 * with the `member` role and `pending` status. They cannot log in until a
 * receptionist / chairman / GM / admin approves them from the Members page.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Logged-in users don't need this page.
if ( is_user_logged_in() ) {
	wp_safe_redirect( vms_get_dashboard_url_for_role( vms_current_role() ) );
	exit;
}

// If the Members module is disabled, registration is closed.
vms_require_module( 'members' );

$branding = vms_get_branding();

get_header();
?>

<div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6 overflow-hidden isolate"
    x-data="memberRegister()">

    <!-- Aurora backdrop (shared with front-page) -->
    <div aria-hidden="true" class="absolute inset-0 -z-10">
        <div
            class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-gradient-to-br from-[var(--vms-primary)] to-[var(--vms-secondary)] opacity-20 dark:opacity-30 blur-3xl animate-aurora">
        </div>
        <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] rounded-full bg-gradient-to-tl from-[var(--vms-secondary)] to-fuchsia-500 opacity-20 dark:opacity-25 blur-3xl animate-aurora"
            style="animation-delay:-10s"></div>
    </div>

    <!-- Floating theme toggle -->
    <div class="fixed bottom-6 right-6 z-50">
        <div class="vms-theme-toggle shadow-glass-hover" :data-mode="darkMode" role="radiogroup">
            <span class="vms-theme-toggle-indicator" aria-hidden="true"></span>
            <button type="button" @click="setDarkMode('light')" :class="{ 'active': darkMode === 'light' }"
                role="radio"><svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg></button>
            <button type="button" @click="setDarkMode('dark')" :class="{ 'active': darkMode === 'dark' }"
                role="radio"><svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg></button>
            <button type="button" @click="setDarkMode('system')" :class="{ 'active': darkMode === 'system' }"
                role="radio"><svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg></button>
        </div>
    </div>

    <div class="w-full max-w-lg animate-float-in">
        <!-- Back link -->
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <?php esc_html_e( 'Back to sign in', 'vms-theme' ); ?>
        </a>

        <!-- Card -->
        <div
            class="relative bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xl backdrop-saturate-150 rounded-3xl shadow-glass-hover border border-white/60 dark:border-slate-700/50 p-6 sm:p-8 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-shine opacity-70" aria-hidden="true"></div>

            <!-- ── FORM ── -->
            <template x-if="!done">
                <div>
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                            <?php
							printf(
								/* translators: %s: club name */
								esc_html__( 'Apply to join %s', 'vms-theme' ),
								esc_html( $branding['club_name'] )
							);
							?>
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            <?php esc_html_e( 'Complete the form below. Your application will be reviewed by our team.', 'vms-theme' ); ?>
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"><?php esc_html_e( 'First name', 'vms-theme' ); ?>
                                    <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.first_name" required autocomplete="given-name"
                                    class="vms-input w-full px-4 py-3 rounded-2xl">
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"><?php esc_html_e( 'Last name', 'vms-theme' ); ?>
                                    <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.last_name" required autocomplete="family-name"
                                    class="vms-input w-full px-4 py-3 rounded-2xl">
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"><?php esc_html_e( 'Email address', 'vms-theme' ); ?>
                                <span class="text-red-500">*</span></label>
                            <input type="email" x-model="form.email" required autocomplete="email"
                                class="vms-input w-full px-4 py-3 rounded-2xl">
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"><?php esc_html_e( 'Phone number', 'vms-theme' ); ?>
                                    <span class="text-red-500">*</span></label>
                                <input type="tel" x-model="form.phone" required autocomplete="tel"
                                    class="vms-input w-full px-4 py-3 rounded-2xl"
                                    placeholder="<?php esc_attr_e( '+254712345678', 'vms-theme' ); ?>">
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"><?php esc_html_e( 'Member number', 'vms-theme' ); ?>
                                    <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.member_number" required pattern="[A-Za-z0-9/\-]+"
                                    title="<?php esc_attr_e( 'Letters, numbers, dashes and slashes only', 'vms-theme' ); ?>"
                                    class="vms-input w-full px-4 py-3 rounded-2xl"
                                    placeholder="<?php esc_attr_e( 'As shown on your membership card', 'vms-theme' ); ?>">
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"><?php esc_html_e( 'Choose a password', 'vms-theme' ); ?>
                                <span class="text-red-500">*</span></label>
                            <input type="password" x-model="form.password" required minlength="8"
                                autocomplete="new-password" class="vms-input w-full px-4 py-3 rounded-2xl"
                                placeholder="<?php esc_attr_e( 'At least 8 characters', 'vms-theme' ); ?>">
                        </div>

                        <div x-show="error" x-cloak x-transition
                            class="p-4 rounded-2xl bg-red-50/80 dark:bg-red-900/20 border border-red-200/80 dark:border-red-800/50">
                            <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                        </div>

                        <button type="submit" :disabled="loading"
                            class="w-full py-3.5 px-6 bg-gradient-brand text-white font-bold rounded-2xl shadow-glow-md hover:shadow-glow-lg active:scale-[0.98] disabled:opacity-50 transition-all duration-200 ease-spring flex items-center justify-center gap-2">
                            <svg x-show="loading" x-cloak class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <span
                                x-text="loading ? '<?php echo esc_js( __( 'Submitting…', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Submit Application', 'vms-theme' ) ); ?>'"></span>
                        </button>
                    </form>
                </div>
            </template>

            <!-- ── SUCCESS ── -->
            <template x-if="done">
                <div class="text-center py-6">
                    <div
                        class="w-20 h-20 mx-auto mb-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">
                        <?php esc_html_e( 'Application received', 'vms-theme' ); ?>
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-6" x-text="successMsg"></p>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-brand text-white font-semibold rounded-2xl shadow-glow-sm hover:shadow-glow-md transition-all">
                        <?php esc_html_e( 'Back to home', 'vms-theme' ); ?>
                    </a>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function memberRegister() {
    return {
        loading: false,
        error: '',
        done: false,
        successMsg: '',
        form: {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            member_number: '',
            password: ''
        },

        async submit() {
            this.error = '';
            this.loading = true;

            var fd = new FormData();
            fd.append('action', 'vms_register_member');
            fd.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'vms_member_register' ) ); ?>);
            Object.entries(this.form).forEach(([k, v]) => fd.append(k, v));

            try {
                var res = await fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                var json = await res.json();

                if (json.success) {
                    this.successMsg = json.data.message;
                    this.done = true;
                } else {
                    this.error = (json.data && json.data.message) ||
                        <?php echo wp_json_encode( __( 'Registration failed. Please try again.', 'vms-theme' ) ); ?>;
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