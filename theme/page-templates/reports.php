<?php
/* Template Name: VMS Reports */

/**
 * Reports & analytics page with Chart.js visualizations.
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

vms_require_module( 'reports' );
vms_require_capability( 'vms_view_reports' );

get_header();
?>

<div class="flex flex-col gap-6" x-data="reportsPage()" x-init="init()">

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Reports & Analytics', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php esc_html_e( 'View and analyze staff performance and activity.', 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <!-- Date Range -->
                <select x-model="dateRange" @change="loadReportData()"
                    class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    <option value="7"><?php esc_html_e( 'Last 7 Days', 'vms-theme' ); ?></option>
                    <option value="30" selected><?php esc_html_e( 'Last 30 Days', 'vms-theme' ); ?></option>
                    <option value="90"><?php esc_html_e( 'Last 90 Days', 'vms-theme' ); ?></option>
                    <option value="365"><?php esc_html_e( 'This Year', 'vms-theme' ); ?></option>
                </select>
                <button @click="exportData()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?php esc_html_e( 'Export', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="vms-card">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Total Visits', 'vms-theme' ); ?></p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white" x-text="reportStats.totalVisits">--</p>
        </div>
        <div class="vms-card">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Unique Guests', 'vms-theme' ); ?></p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white" x-text="reportStats.uniqueGuests">--</p>
        </div>
        <div class="vms-card">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Avg. Daily Visits', 'vms-theme' ); ?></p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white" x-text="reportStats.avgDaily">--</p>
        </div>
        <div class="vms-card">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Completion Rate', 'vms-theme' ); ?></p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white" x-text="reportStats.completionRate">--</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Visits Chart -->
        <div class="vms-card">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                <?php esc_html_e( 'Daily Visits', 'vms-theme' ); ?>
            </h3>
            <div class="relative h-64">
                <canvas x-ref="dailyChart"></canvas>
                <div x-show="loading"
                    class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50 rounded-xl">
                    <svg class="animate-spin h-8 w-8 text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Status Distribution Chart -->
        <div class="vms-card">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                <?php esc_html_e( 'Visit Status Distribution', 'vms-theme' ); ?>
            </h3>
            <div class="relative h-64">
                <canvas x-ref="statusChart"></canvas>
                <div x-show="loading"
                    class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50 rounded-xl">
                    <svg class="animate-spin h-8 w-8 text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Guests Table -->
    <div class="vms-card">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
            <?php esc_html_e( 'Most Frequent Guests', 'vms-theme' ); ?>
        </h3>

        <div x-show="loading" class="animate-pulse flex flex-col gap-3">
            <template x-for="i in 5" :key="i">
                <div class="h-12 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
            </template>
        </div>

        <div x-show="!loading && topGuests.length > 0" class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <?php esc_html_e( 'Guest', 'vms-theme' ); ?></th>
                        <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <?php esc_html_e( 'Visits', 'vms-theme' ); ?></th>
                        <th
                            class="text-left px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase hidden sm:table-cell">
                            <?php esc_html_e( 'Last Visit', 'vms-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <template x-for="guest in topGuests" :key="guest.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white"
                                    x-text="guest.name"></span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--vms-primary)]/10 text-[var(--vms-primary)]"
                                    x-text="guest.count"></span>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400" x-text="guest.lastVisit"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="!loading && topGuests.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            <?php esc_html_e( 'No visit data available for the selected period.', 'vms-theme' ); ?>
        </div>
    </div>
</div>

<script>
function reportsPage() {
    return {
        dateRange: '30',
        loading: true,
        reportStats: {
            totalVisits: '--',
            uniqueGuests: '--',
            avgDaily: '--',
            completionRate: '--'
        },
        topGuests: [],
        dailyChart: null,
        statusChart: null,

        init() {
            this.loadReportData();
        },

        async loadReportData() {
            this.loading = true;

            try {
                // Load today's visits as a starting point for the reports.
                const formData = new FormData();
                formData.append('action', 'vms_get_visits');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const visits = data.data.visits || [];
                    this.processReportData(visits);
                }
            } catch (e) {
                console.error('Failed to load report data:', e);
            } finally {
                this.loading = false;
            }
        },

        processReportData(visits) {
            // Stats
            const completed = visits.filter(v => v.sign_out_time).length;
            const uniqueGuests = [...new Set(visits.map(v => v.guest_id))].length;
            const days = parseInt(this.dateRange) || 30;

            this.reportStats = {
                totalVisits: visits.length,
                uniqueGuests: uniqueGuests,
                avgDaily: visits.length > 0 ? Math.round(visits.length / Math.min(days, 1) * 10) / 10 : 0,
                completionRate: visits.length > 0 ? Math.round(completed / visits.length * 100) + '%' : '0%'
            };

            // Top guests
            const guestMap = {};
            visits.forEach(v => {
                const key = v.guest_id;
                if (!guestMap[key]) {
                    guestMap[key] = {
                        id: key,
                        name: v.first_name + ' ' + v.last_name,
                        count: 0,
                        lastVisit: v.visit_date
                    };
                }
                guestMap[key].count++;
            });
            this.topGuests = Object.values(guestMap).sort((a, b) => b.count - a.count).slice(0, 10);

            // Charts
            this.renderDailyChart(visits);
            this.renderStatusChart(visits);
        },

        renderDailyChart(visits) {
            const ctx = this.$refs.dailyChart;
            if (!ctx || typeof Chart === 'undefined') return;

            if (this.dailyChart) this.dailyChart.destroy();

            // Group by status for daily chart.
            const isDark = document.documentElement.classList.contains('dark');
            const primary = getComputedStyle(document.documentElement).getPropertyValue('--vms-primary').trim() ||
                '#0ea5e9';

            const labels = ['Today'];
            const values = [visits.length];

            this.dailyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?php echo esc_js( __( 'Visits', 'vms-theme' ) ); ?>',
                        data: values,
                        backgroundColor: primary + '80',
                        borderColor: primary,
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: isDark ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                color: isDark ? '#374151' : '#f3f4f6'
                            }
                        },
                        x: {
                            ticks: {
                                color: isDark ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        renderStatusChart(visits) {
            const ctx = this.$refs.statusChart;
            if (!ctx || typeof Chart === 'undefined') return;

            if (this.statusChart) this.statusChart.destroy();

            const statusCounts = {
                approved: 0,
                completed: 0,
                unapproved: 0,
                cancelled: 0
            };
            visits.forEach(v => {
                if (v.sign_out_time) statusCounts.completed++;
                else if (v.status in statusCounts) statusCounts[v.status]++;
            });

            this.statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        '<?php echo esc_js( __( 'Approved', 'vms-theme' ) ); ?>',
                        '<?php echo esc_js( __( 'Completed', 'vms-theme' ) ); ?>',
                        '<?php echo esc_js( __( 'Pending', 'vms-theme' ) ); ?>',
                        '<?php echo esc_js( __( 'Cancelled', 'vms-theme' ) ); ?>'
                    ],
                    datasets: [{
                        data: [statusCounts.approved, statusCounts.completed, statusCounts.unapproved,
                            statusCounts.cancelled
                        ],
                        backgroundColor: ['#3b82f6', '#22c55e', '#eab308', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#d1d5db' :
                                    '#374151',
                                padding: 12,
                                usePointStyle: true
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        },

        exportData() {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'info',
                    message: '<?php echo esc_js( __( 'Export feature coming soon.', 'vms-theme' ) ); ?>'
                }
            }));
        }
    };
}
</script>

<?php
get_footer();