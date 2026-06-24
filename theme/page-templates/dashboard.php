<?php
/* Template Name: VMS Dashboard */

/**
 * Role-based dashboard with stats, quick actions, and recent activity.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

get_header();

$role     = vms_current_role();
$branding = vms_get_branding();
?>

<div class="flex flex-col gap-6" x-data="dashboardPage()" x-init="init()">

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Dashboard', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php
                    printf(
                        /* translators: %s: user display name */
                        esc_html__( 'Welcome back, %s', 'vms-theme' ),
                        esc_html( wp_get_current_user()->display_name )
                    );
                    ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <span x-text="currentDate"></span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Today's Visits -->
        <div class="vms-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        <?php esc_html_e( "Today's Visits", 'vms-theme' ); ?>
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white" x-text="stats.todayVisits">
                        <span class="inline-block w-8 h-8 bg-gray-200 rounded dark:bg-gray-700 animate-pulse"></span>
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Signed In Now -->
        <div class="vms-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        <?php esc_html_e( 'Signed In Now', 'vms-theme' ); ?>
                    </p>
                    <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400" x-text="stats.signedIn">
                        <span class="inline-block w-8 h-8 bg-gray-200 rounded dark:bg-gray-700 animate-pulse"></span>
                    </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Approval -->
        <div class="vms-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        <?php esc_html_e( 'Pending Approval', 'vms-theme' ); ?>
                    </p>
                    <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400" x-text="stats.pending">
                        <span class="inline-block w-8 h-8 bg-gray-200 rounded dark:bg-gray-700 animate-pulse"></span>
                    </p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Monthly Total -->
        <div class="vms-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        <?php esc_html_e( 'Monthly Total', 'vms-theme' ); ?>
                    </p>
                    <p class="mt-2 text-3xl font-bold text-purple-600 dark:text-purple-400" x-text="stats.monthlyTotal">
                        <span class="inline-block w-8 h-8 bg-gray-200 rounded dark:bg-gray-700 animate-pulse"></span>
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="vms-card">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            <?php esc_html_e( 'Quick Actions', 'vms-theme' ); ?>
        </h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <?php if ( current_user_can( 'vms_register_guests' ) ) : ?>
            <a href="<?php echo esc_url( vms_get_page_url( 'guests' ) ); ?>"
                class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[var(--vms-primary)]/10 dark:hover:bg-[var(--vms-primary)]/10 border border-transparent hover:border-[var(--vms-primary)]/20 transition-all duration-200 group">
                <svg class="w-8 h-8 text-gray-400 group-hover:text-[var(--vms-primary)] transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span class="text-sm font-medium text-center text-gray-700 dark:text-gray-300">
                    <?php esc_html_e( 'Register Guest', 'vms-theme' ); ?>
                </span>
            </a>
            <?php endif; ?>

            <?php if ( current_user_can( 'vms_signin_guests' ) ) : ?>
            <a href="<?php echo esc_url( vms_get_page_url( 'sign-in' ) ); ?>"
                class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[var(--vms-primary)]/10 dark:hover:bg-[var(--vms-primary)]/10 border border-transparent hover:border-[var(--vms-primary)]/20 transition-all duration-200 group">
                <svg class="w-8 h-8 text-gray-400 group-hover:text-[var(--vms-primary)] transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="text-sm font-medium text-center text-gray-700 dark:text-gray-300">
                    <?php esc_html_e( 'Sign-In Desk', 'vms-theme' ); ?>
                </span>
            </a>
            <?php endif; ?>

            <?php if ( current_user_can( 'vms_view_reports' ) ) : ?>
            <a href="<?php echo esc_url( vms_get_page_url( 'reports' ) ); ?>"
                class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[var(--vms-primary)]/10 dark:hover:bg-[var(--vms-primary)]/10 border border-transparent hover:border-[var(--vms-primary)]/20 transition-all duration-200 group">
                <svg class="w-8 h-8 text-gray-400 group-hover:text-[var(--vms-primary)] transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="text-sm font-medium text-center text-gray-700 dark:text-gray-300">
                    <?php esc_html_e( 'View Reports', 'vms-theme' ); ?>
                </span>
            </a>
            <?php endif; ?>

            <a href="<?php echo esc_url( vms_get_page_url( 'guests' ) ); ?>"
                class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[var(--vms-primary)]/10 dark:hover:bg-[var(--vms-primary)]/10 border border-transparent hover:border-[var(--vms-primary)]/20 transition-all duration-200 group">
                <svg class="w-8 h-8 text-gray-400 group-hover:text-[var(--vms-primary)] transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="text-sm font-medium text-center text-gray-700 dark:text-gray-300">
                    <?php esc_html_e( 'Search Guests', 'vms-theme' ); ?>
                </span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="vms-card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <?php esc_html_e( 'Recent Activity', 'vms-theme' ); ?>
            </h2>
            <button @click="loadRecentActivity()" class="text-sm text-[var(--vms-primary)] hover:underline"
                :disabled="loadingActivity">
                <span x-show="!loadingActivity"><?php esc_html_e( 'Refresh', 'vms-theme' ); ?></span>
                <svg x-show="loadingActivity" x-cloak class="inline w-4 h-4 animate-spin" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </button>
        </div>

        <!-- Loading State -->
        <div x-show="loadingActivity && !activity.length" class="flex flex-col gap-3">
            <template x-for="i in 5" :key="i">
                <div class="flex items-center gap-4 animate-pulse">
                    <div class="w-10 h-10 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                    <div class="flex-1 space-y-2">
                        <div class="w-3/4 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
                        <div class="w-1/2 h-3 bg-gray-200 rounded dark:bg-gray-700"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Activity List -->
        <div x-show="activity.length > 0" class="flex flex-col gap-3">
            <template x-for="item in activity" :key="item.id">
                <div
                    class="flex items-start gap-3 p-3 transition-colors rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <div class="flex items-center justify-center w-10 h-10 text-sm font-medium rounded-full shrink-0"
                        :class="{
							'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': item.type === 'signin',
							'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': item.type === 'visit',
							'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': item.type === 'signout',
							'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': item.type === 'register',
							'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400': !['signin','visit','signout','register'].includes(item.type)
						}" x-text="item.initials"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="item.description"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="item.time"></p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loadingActivity && !activity.length" class="py-8 text-center">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'No recent activity to display.', 'vms-theme' ); ?>
            </p>
        </div>
    </div>
</div>

<script>
function dashboardPage() {
    return {
        stats: {
            todayVisits: '...',
            signedIn: '...',
            pending: '...',
            monthlyTotal: '...'
        },
        activity: [],
        loadingActivity: true,
        currentDate: new Date().toLocaleDateString(undefined, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }),

        init() {
            this.loadStats();
            this.loadRecentActivity();
        },

        async loadStats() {
            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_visits');
                formData.append('nonce', vmsTheme.nonces.guest);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success && data.data.visits) {
                    const visits = data.data.visits;
                    this.stats.todayVisits = visits.length;
                    this.stats.signedIn = visits.filter(v => v.sign_in_time && !v.sign_out_time).length;
                    this.stats.pending = visits.filter(v => v.status === 'unapproved').length;
                    this.stats.monthlyTotal = visits.length;
                }
            } catch (e) {
                console.error('Failed to load stats:', e);
            }
        },

        async loadRecentActivity() {
            this.loadingActivity = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_visits');
                formData.append('nonce', vmsTheme.nonces.guest);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success && data.data.visits) {
                    this.activity = data.data.visits.slice(0, 10).map((visit, idx) => {
                        let type = 'visit';
                        let desc = '';

                        if (visit.sign_out_time) {
                            type = 'signout';
                            desc = `${visit.first_name} ${visit.last_name} signed out`;
                        } else if (visit.sign_in_time) {
                            type = 'signin';
                            desc = `${visit.first_name} ${visit.last_name} signed in`;
                        } else if (visit.status === 'approved') {
                            type = 'visit';
                            desc = `${visit.first_name} ${visit.last_name} - visit approved`;
                        } else {
                            type = 'register';
                            desc = `${visit.first_name} ${visit.last_name} - visit registered`;
                        }

                        return {
                            id: visit.id || idx,
                            type: type,
                            description: desc,
                            initials: ((visit.first_name || '')[0] || '') + ((visit.last_name || '')[0] ||
                                ''),
                            time: visit.sign_in_time || visit.created_at || ''
                        };
                    });
                }
            } catch (e) {
                console.error('Failed to load activity:', e);
            } finally {
                this.loadingActivity = false;
            }
        }
    };
}
</script>

<?php
get_footer();