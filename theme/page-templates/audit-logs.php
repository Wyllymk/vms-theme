<?php
/* Template Name: VMS Audit Logs */

/**
 * Audit logs viewer — searchable, filterable table with expandable detail
 * rows, summary stats, and PDF export.
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

if ( ! current_user_can( 'vms_view_audit_logs' ) ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

get_header();
?>

<div class="flex flex-col gap-6" x-data="auditLogsPage()" x-init="init()">

    <!-- ═══════════════════════════════════════════════════════════════════
	     PAGE HEADER
	     ═══════════════════════════════════════════════════════════════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <?php esc_html_e( 'Audit Logs', 'vms-theme' ); ?>
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Track all system activity, changes, and user actions.', 'vms-theme' ); ?>
            </p>
        </div>

        <!-- Export Dropdown -->
        <div class="relative" x-data="{ exportOpen: false }">
            <button @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <?php esc_html_e( 'Export PDF', 'vms-theme' ); ?>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': exportOpen }" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="exportOpen" x-cloak x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-64 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 py-1.5 z-50">
                <button @click="exportAllPdf(); exportOpen = false"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?php esc_html_e( 'Export All Logs', 'vms-theme' ); ?>
                </button>
                <button @click="exportFilteredPdf(); exportOpen = false"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <?php esc_html_e( 'Export Filtered Logs', 'vms-theme' ); ?>
                </button>
                <div class="border-t border-gray-200/50 dark:border-gray-700/50 my-1"></div>
                <button @click="showUserExportModal = true; exportOpen = false"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <?php esc_html_e( 'Export for User/Guest', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card overflow-visible!">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Audit Logs', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php esc_html_e( 'Track all system activity, changes, and user actions.', 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <!-- Export Dropdown -->
                <div class="relative" x-data="{ exportOpen: false }">
                    <button @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <?php esc_html_e( 'Export PDF', 'vms-theme' ); ?>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': exportOpen }" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="exportOpen" x-cloak x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-64 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 py-1.5 z-50">
                        <button @click="exportAllPdf(); exportOpen = false"
                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <?php esc_html_e( 'Export All Logs', 'vms-theme' ); ?>
                        </button>
                        <button @click="exportFilteredPdf(); exportOpen = false"
                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <?php esc_html_e( 'Export Filtered Logs', 'vms-theme' ); ?>
                        </button>
                        <div class="border-t border-gray-200/50 dark:border-gray-700/50 my-1"></div>
                        <button @click="showUserExportModal = true; exportOpen = false"
                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <?php esc_html_e( 'Export for User/Guest', 'vms-theme' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
	     SUMMARY STATS
	     ═══════════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Today's Actions -->
        <div class="vms-card">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                <?php esc_html_e( 'Today', 'vms-theme' ); ?>
            </p>
            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.today">--</p>
        </div>

        <!-- Category breakdown cards -->
        <template x-for="cat in statsCategories" :key="cat.key">
            <div class="vms-card">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full" :class="cat.dotClass"></span>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                        x-text="cat.label"></p>
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white" x-text="cat.count">0</p>
            </div>
        </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
	     FILTERS & SEARCH
	     ═══════════════════════════════════════════════════════════════════ -->
    <div class="vms-card">
        <div class="flex flex-col lg:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1 min-w-0">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="search" x-model.debounce.400ms="filters.search" @input="resetAndLoad()"
                    placeholder="<?php esc_attr_e( 'Search by user, action, or entity...', 'vms-theme' ); ?>"
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
            </div>

            <!-- Date From -->
            <div class="relative">
                <label class="sr-only"><?php esc_html_e( 'Date from', 'vms-theme' ); ?></label>
                <input type="date" x-model="filters.date_from" @change="resetAndLoad()"
                    class="w-full lg:w-40 px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent text-sm"
                    placeholder="<?php esc_attr_e( 'From date', 'vms-theme' ); ?>"
                    title="<?php esc_attr_e( 'From date', 'vms-theme' ); ?>">
            </div>

            <!-- Date To -->
            <div class="relative">
                <label class="sr-only"><?php esc_html_e( 'Date to', 'vms-theme' ); ?></label>
                <input type="date" x-model="filters.date_to" @change="resetAndLoad()"
                    class="w-full lg:w-40 px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent text-sm"
                    placeholder="<?php esc_attr_e( 'To date', 'vms-theme' ); ?>"
                    title="<?php esc_attr_e( 'To date', 'vms-theme' ); ?>">
            </div>

            <!-- Category Filter -->
            <select x-model="filters.category" @change="resetAndLoad()"
                class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent text-sm">
                <option value=""><?php esc_html_e( 'All Categories', 'vms-theme' ); ?></option>
                <option value="guest"><?php esc_html_e( 'Guest', 'vms-theme' ); ?></option>
                <option value="member"><?php esc_html_e( 'Member', 'vms-theme' ); ?></option>
                <option value="visit"><?php esc_html_e( 'Visit', 'vms-theme' ); ?></option>
                <option value="accommodation"><?php esc_html_e( 'Accommodation', 'vms-theme' ); ?></option>
                <option value="supplier"><?php esc_html_e( 'Supplier', 'vms-theme' ); ?></option>
                <option value="reciprocation"><?php esc_html_e( 'Reciprocation', 'vms-theme' ); ?></option>
                <option value="employee"><?php esc_html_e( 'Employee', 'vms-theme' ); ?></option>
                <option value="settings"><?php esc_html_e( 'Settings', 'vms-theme' ); ?></option>
                <option value="system"><?php esc_html_e( 'System', 'vms-theme' ); ?></option>
            </select>

            <!-- Action Type Filter -->
            <select x-model="filters.action_type" @change="resetAndLoad()"
                class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent text-sm">
                <option value=""><?php esc_html_e( 'All Actions', 'vms-theme' ); ?></option>
                <option value="create"><?php esc_html_e( 'Create', 'vms-theme' ); ?></option>
                <option value="update"><?php esc_html_e( 'Update', 'vms-theme' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete', 'vms-theme' ); ?></option>
                <option value="login"><?php esc_html_e( 'Login', 'vms-theme' ); ?></option>
                <option value="logout"><?php esc_html_e( 'Logout', 'vms-theme' ); ?></option>
                <option value="approve"><?php esc_html_e( 'Approve', 'vms-theme' ); ?></option>
                <option value="reject"><?php esc_html_e( 'Reject', 'vms-theme' ); ?></option>
                <option value="signin"><?php esc_html_e( 'Sign In', 'vms-theme' ); ?></option>
                <option value="signout"><?php esc_html_e( 'Sign Out', 'vms-theme' ); ?></option>
                <option value="export"><?php esc_html_e( 'Export', 'vms-theme' ); ?></option>
                <option value="import"><?php esc_html_e( 'Import', 'vms-theme' ); ?></option>
            </select>

            <!-- User Filter -->
            <div class="relative" x-data="{ userDropdownOpen: false }">
                <input type="text" x-model="userSearchTerm" @input.debounce.300ms="searchUsers()"
                    @focus="userDropdownOpen = true" @click.outside="userDropdownOpen = false"
                    placeholder="<?php esc_attr_e( 'Filter by user...', 'vms-theme' ); ?>"
                    class="w-full lg:w-44 px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent text-sm">
                <!-- Clear user filter -->
                <button x-show="filters.user_id" x-cloak @click="clearUserFilter()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    title="<?php esc_attr_e( 'Clear user filter', 'vms-theme' ); ?>">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <!-- User suggestions dropdown -->
                <div x-show="userDropdownOpen && userSuggestions.length > 0" x-cloak
                    class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 max-h-48 overflow-y-auto z-50">
                    <template x-for="user in userSuggestions" :key="user.id">
                        <button @click="selectUser(user); userDropdownOpen = false"
                            class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="w-6 h-6 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-xs font-medium text-[var(--vms-primary)]"
                                x-text="user.display_name.charAt(0).toUpperCase()">
                            </div>
                            <span x-text="user.display_name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Clear All Filters -->
            <button x-show="hasActiveFilters" x-cloak @click="clearAllFilters()"
                class="px-3 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors whitespace-nowrap">
                <?php esc_html_e( 'Clear Filters', 'vms-theme' ); ?>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
	     AUDIT LOG TABLE
	     ═══════════════════════════════════════════════════════════════════ -->
    <div class="vms-card">

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <svg class="animate-spin h-8 w-8 mx-auto text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Loading audit logs...', 'vms-theme' ); ?></p>
        </div>

        <!-- Table -->
        <div x-show="!loading && rows.length > 0" class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th
                            class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-8">
                        </th>
                        <th
                            class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?php esc_html_e( 'Date/Time', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?php esc_html_e( 'User', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                            <?php esc_html_e( 'Role', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?php esc_html_e( 'Action', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?php esc_html_e( 'Category', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                            <?php esc_html_e( 'Entity', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden xl:table-cell">
                            <?php esc_html_e( 'IP Address', 'vms-theme' ); ?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <template x-for="row in rows" :key="row.id">
                        <template x-if="true">
                <tbody>
                    <!-- Main Row -->
                    <tr @click="toggleRow(row.id)"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer group">
                        <!-- Expand Icon -->
                        <td class="px-4 py-3">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-[var(--vms-primary)] transition-all duration-200"
                                :class="{ 'rotate-90': expandedRows[row.id] }" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </td>

                        <!-- Date/Time -->
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 dark:text-white" x-text="formatDate(row.created_at)">
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(row.created_at)">
                            </div>
                        </td>

                        <!-- User -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-xs font-medium text-[var(--vms-primary)] shrink-0"
                                    x-text="getUserInitials(row)">
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[120px]"
                                    x-text="row.display_name || '<?php echo esc_js( __( 'System', 'vms-theme' ) ); ?>'"></span>
                            </div>
                        </td>

                        <!-- Role -->
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="text-xs text-gray-500 dark:text-gray-400 capitalize"
                                x-text="formatRole(row.user_role)"></span>
                        </td>

                        <!-- Action -->
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md"
                                :class="getActionBadgeClass(row.action_type)"
                                x-text="formatActionType(row.action_type)">
                            </span>
                        </td>

                        <!-- Category -->
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full"
                                :class="getCategoryBadgeClass(row.action_category)"
                                x-text="formatCategory(row.action_category)">
                            </span>
                        </td>

                        <!-- Entity -->
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span class="text-sm text-gray-600 dark:text-gray-400" x-text="formatEntity(row)"></span>
                        </td>

                        <!-- IP Address -->
                        <td class="px-4 py-3 hidden xl:table-cell">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400"
                                x-text="row.ip_address || '---'"></span>
                        </td>
                    </tr>

                    <!-- Expanded Detail Row -->
                    <tr x-show="expandedRows[row.id]" x-cloak>
                        <td colspan="8" class="px-4 py-0">
                            <div x-show="expandedRows[row.id]" x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 max-h-0"
                                x-transition:enter-end="opacity-100 max-h-[600px]"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 max-h-[600px]"
                                x-transition:leave-end="opacity-0 max-h-0" class="overflow-hidden">
                                <div class="py-4 flex flex-col gap-4">
                                    <!-- Detail Grid -->
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-200/50 dark:border-gray-700/50">
                                        <!-- Entity Info -->
                                        <div>
                                            <p
                                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                                                <?php esc_html_e( 'Entity', 'vms-theme' ); ?>
                                            </p>
                                            <p class="text-sm text-gray-900 dark:text-white">
                                                <span x-text="row.entity_type || '---'"></span>
                                                <span class="text-gray-400" x-show="row.entity_id"> #<span
                                                        x-text="row.entity_id"></span></span>
                                            </p>
                                        </div>
                                        <!-- User Agent -->
                                        <div class="lg:col-span-2">
                                            <p
                                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                                                <?php esc_html_e( 'User Agent', 'vms-theme' ); ?>
                                            </p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 break-all line-clamp-2"
                                                x-text="row.user_agent || '---'"></p>
                                        </div>
                                        <!-- IP -->
                                        <div>
                                            <p
                                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                                                <?php esc_html_e( 'IP Address', 'vms-theme' ); ?>
                                            </p>
                                            <p class="text-sm font-mono text-gray-900 dark:text-white"
                                                x-text="row.ip_address || '---'"></p>
                                        </div>
                                    </div>

                                    <!-- JSON Fields -->
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                        <!-- Old Values -->
                                        <div x-show="row.old_values && row.old_values !== 'null' && row.old_values !== '{}'"
                                            x-cloak>
                                            <div
                                                class="rounded-xl border border-red-200/50 dark:border-red-800/30 overflow-hidden">
                                                <div
                                                    class="px-3 py-2 bg-red-50 dark:bg-red-900/20 border-b border-red-200/50 dark:border-red-800/30">
                                                    <p
                                                        class="text-xs font-semibold text-red-700 dark:text-red-400 uppercase tracking-wider">
                                                        <?php esc_html_e( 'Previous Values', 'vms-theme' ); ?>
                                                    </p>
                                                </div>
                                                <pre class="p-3 text-xs font-mono text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-900/60 overflow-x-auto max-h-48"
                                                    x-text="formatJson(row.old_values)"></pre>
                                            </div>
                                        </div>

                                        <!-- New Values -->
                                        <div x-show="row.new_values && row.new_values !== 'null' && row.new_values !== '{}'"
                                            x-cloak>
                                            <div
                                                class="rounded-xl border border-green-200/50 dark:border-green-800/30 overflow-hidden">
                                                <div
                                                    class="px-3 py-2 bg-green-50 dark:bg-green-900/20 border-b border-green-200/50 dark:border-green-800/30">
                                                    <p
                                                        class="text-xs font-semibold text-green-700 dark:text-green-400 uppercase tracking-wider">
                                                        <?php esc_html_e( 'New Values', 'vms-theme' ); ?>
                                                    </p>
                                                </div>
                                                <pre class="p-3 text-xs font-mono text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-900/60 overflow-x-auto max-h-48"
                                                    x-text="formatJson(row.new_values)"></pre>
                                            </div>
                                        </div>

                                        <!-- Metadata -->
                                        <div x-show="row.metadata && row.metadata !== 'null' && row.metadata !== '{}'"
                                            x-cloak>
                                            <div
                                                class="rounded-xl border border-blue-200/50 dark:border-blue-800/30 overflow-hidden">
                                                <div
                                                    class="px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200/50 dark:border-blue-800/30">
                                                    <p
                                                        class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wider">
                                                        <?php esc_html_e( 'Metadata', 'vms-theme' ); ?>
                                                    </p>
                                                </div>
                                                <pre class="p-3 text-xs font-mono text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-900/60 overflow-x-auto max-h-48"
                                                    x-text="formatJson(row.metadata)"></pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty JSON state -->
                                    <div x-show="(!row.old_values || row.old_values === 'null' || row.old_values === '{}') && (!row.new_values || row.new_values === 'null' || row.new_values === '{}') && (!row.metadata || row.metadata === 'null' || row.metadata === '{}')"
                                        class="text-center py-3">
                                        <p class="text-xs text-gray-400 dark:text-gray-500 italic">
                                            <?php esc_html_e( 'No additional detail data available for this log entry.', 'vms-theme' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
                </template>
                </template>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && rows.length === 0" class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                <?php esc_html_e( 'No Audit Logs Found', 'vms-theme' ); ?>
            </h3>
            <p class="text-gray-500 dark:text-gray-400"
                x-text="hasActiveFilters ? '<?php echo esc_js( __( 'No logs match your current filters. Try adjusting your search criteria.', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'System activity will appear here as actions are performed.', 'vms-theme' ) ); ?>'">
            </p>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
		     PAGINATION
		     ═══════════════════════════════════════════════════════════════ -->
        <div x-show="!loading && rows.length > 0"
            class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <!-- Results info & per-page selector -->
            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                <span>
                    <?php esc_html_e( 'Showing', 'vms-theme' ); ?>
                    <span class="font-medium text-gray-900 dark:text-white"
                        x-text="((currentPage - 1) * perPage) + 1"></span>
                    -
                    <span class="font-medium text-gray-900 dark:text-white"
                        x-text="Math.min(currentPage * perPage, totalRows)"></span>
                    <?php esc_html_e( 'of', 'vms-theme' ); ?>
                    <span class="font-medium text-gray-900 dark:text-white" x-text="totalRows"></span>
                </span>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <label class="flex items-center gap-1.5">
                    <span><?php esc_html_e( 'Per page:', 'vms-theme' ); ?></span>
                    <select x-model.number="perPage" @change="resetAndLoad()"
                        class="px-2 py-1 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent text-sm">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </label>
            </div>

            <!-- Page Navigation -->
            <div class="flex items-center gap-1">
                <!-- First Page -->
                <button @click="goToPage(1)" :disabled="currentPage <= 1"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                    title="<?php esc_attr_e( 'First page', 'vms-theme' ); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
                <!-- Previous Page -->
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                    title="<?php esc_attr_e( 'Previous page', 'vms-theme' ); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Page Numbers -->
                <template x-for="page in visiblePages" :key="page">
                    <button x-show="page !== '...'" @click="goToPage(page)"
                        class="w-9 h-9 rounded-lg text-sm font-medium transition-colors" :class="page === currentPage
							? 'bg-[var(--vms-primary)] text-white shadow-lg shadow-[var(--vms-primary)]/25'
							: 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50'" x-text="page">
                    </button>
                    <span x-show="page === '...'"
                        class="w-9 h-9 flex items-center justify-center text-sm text-gray-400 dark:text-gray-500">...</span>
                </template>

                <!-- Next Page -->
                <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                    title="<?php esc_attr_e( 'Next page', 'vms-theme' ); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <!-- Last Page -->
                <button @click="goToPage(totalPages)" :disabled="currentPage >= totalPages"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                    title="<?php esc_attr_e( 'Last page', 'vms-theme' ); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
	     USER/GUEST EXPORT MODAL
	     ═══════════════════════════════════════════════════════════════════ -->
    <div x-show="showUserExportModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div @click="showUserExportModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-md"
            @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php esc_html_e( 'Export Logs for User/Guest', 'vms-theme' ); ?>
                </h3>
                <button @click="showUserExportModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <?php esc_html_e( 'Search for a user or guest to generate a personalized audit log PDF with their name and details in the header.', 'vms-theme' ); ?>
                </p>

                <!-- User search within modal -->
                <div class="relative" x-data="{ exportUserDropdownOpen: false }">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php esc_html_e( 'Select User', 'vms-theme' ); ?>
                    </label>
                    <input type="text" x-model="exportUserSearchTerm" @input.debounce.300ms="searchExportUsers()"
                        @focus="exportUserDropdownOpen = true" @click.outside="exportUserDropdownOpen = false"
                        placeholder="<?php esc_attr_e( 'Type to search users...', 'vms-theme' ); ?>"
                        class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    <!-- Suggestions -->
                    <div x-show="exportUserDropdownOpen && exportUserSuggestions.length > 0" x-cloak
                        class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 max-h-48 overflow-y-auto z-50">
                        <template x-for="user in exportUserSuggestions" :key="user.id">
                            <button @click="selectExportUser(user); exportUserDropdownOpen = false"
                                class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <div class="w-6 h-6 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-xs font-medium text-[var(--vms-primary)]"
                                    x-text="user.display_name.charAt(0).toUpperCase()">
                                </div>
                                <span x-text="user.display_name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Selected user preview -->
                <div x-show="exportSelectedUser" x-cloak
                    class="flex items-center gap-3 p-3 bg-[var(--vms-primary)]/5 border border-[var(--vms-primary)]/20 rounded-xl">
                    <div class="w-10 h-10 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-sm font-bold text-[var(--vms-primary)]"
                        x-text="exportSelectedUser?.display_name?.charAt(0)?.toUpperCase() || ''">
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white"
                            x-text="exportSelectedUser?.display_name"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ID: <span
                                x-text="exportSelectedUser?.id"></span></p>
                    </div>
                    <button @click="exportSelectedUser = null; exportUserSearchTerm = ''"
                        class="ml-auto p-1 rounded-lg text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showUserExportModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button @click="exportUserPdf()" :disabled="!exportSelectedUser || exporting"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="exporting" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <?php esc_html_e( 'Generate PDF', 'vms-theme' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function auditLogsPage() {
    return {
        // --- State ---
        rows: [],
        loading: true,
        currentPage: 1,
        perPage: 20,
        totalRows: 0,
        totalPages: 0,
        expandedRows: {},
        exporting: false,

        // Filters
        filters: {
            search: '',
            date_from: '',
            date_to: '',
            category: '',
            action_type: '',
            user_id: ''
        },

        // User filter search
        userSearchTerm: '',
        userSuggestions: [],

        // Summary stats
        stats: {
            today: '--'
        },
        statsCategories: [],

        // Export modal
        showUserExportModal: false,
        exportUserSearchTerm: '',
        exportUserSuggestions: [],
        exportSelectedUser: null,

        // --- Computed ---
        get hasActiveFilters() {
            return this.filters.search ||
                this.filters.date_from ||
                this.filters.date_to ||
                this.filters.category ||
                this.filters.action_type ||
                this.filters.user_id;
        },

        get visiblePages() {
            const pages = [];
            const total = this.totalPages;
            const current = this.currentPage;

            if (total <= 7) {
                for (let i = 1; i <= total; i++) pages.push(i);
                return pages;
            }

            pages.push(1);

            if (current > 3) pages.push('...');

            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);

            for (let i = start; i <= end; i++) pages.push(i);

            if (current < total - 2) pages.push('...');

            pages.push(total);

            return pages;
        },

        // --- Lifecycle ---
        init() {
            this.loadLogs();
        },

        // --- AJAX ---
        async loadLogs() {
            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_audit_logs');
                formData.append('_ajax_nonce', vmsTheme.nonces.audit);
                formData.append('page', this.currentPage);
                formData.append('per_page', this.perPage);

                if (this.filters.search) formData.append('search', this.filters.search);
                if (this.filters.date_from) formData.append('date_from', this.filters.date_from);
                if (this.filters.date_to) formData.append('date_to', this.filters.date_to);
                if (this.filters.user_id) formData.append('user_id', this.filters.user_id);
                if (this.filters.category) formData.append('category', this.filters.category);
                if (this.filters.action_type) formData.append('action_type', this.filters.action_type);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.rows = data.data.rows || [];
                    this.totalRows = data.data.total || 0;
                    this.totalPages = data.data.pages || 0;
                    this.buildStats(this.rows, data.data);
                } else {
                    this.rows = [];
                    this.totalRows = 0;
                    this.totalPages = 0;
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            type: 'error',
                            message: data.data?.message || vmsTheme.i18n.error
                        }
                    }));
                }
            } catch (e) {
                console.error('Failed to load audit logs:', e);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        type: 'error',
                        message: vmsTheme.i18n.error
                    }
                }));
            } finally {
                this.loading = false;
            }
        },

        buildStats(rows, responseData) {
            // Use server-provided stats if available, otherwise compute from current page
            const todayStr = new Date().toISOString().split('T')[0];

            if (responseData.stats) {
                this.stats.today = responseData.stats.today || 0;
                this.statsCategories = (responseData.stats.categories || []).map(cat => ({
                    key: cat.key,
                    label: this.formatCategory(cat.key),
                    count: cat.count,
                    dotClass: this.getCategoryDotClass(cat.key)
                }));
            } else {
                // Fallback: compute from visible rows
                const todayCount = rows.filter(r => r.created_at && r.created_at.startsWith(todayStr)).length;
                this.stats.today = todayCount;

                const catCounts = {};
                rows.forEach(r => {
                    const cat = r.action_category || 'system';
                    catCounts[cat] = (catCounts[cat] || 0) + 1;
                });

                this.statsCategories = Object.entries(catCounts)
                    .sort((a, b) => b[1] - a[1])
                    .slice(0, 5)
                    .map(([key, count]) => ({
                        key,
                        label: this.formatCategory(key),
                        count,
                        dotClass: this.getCategoryDotClass(key)
                    }));
            }
        },

        // --- User search for filter ---
        async searchUsers() {
            if (!this.userSearchTerm || this.userSearchTerm.length < 2) {
                this.userSuggestions = [];
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'vms_search_guests');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('term', this.userSearchTerm);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.userSuggestions = (data.data.results || []).map(u => ({
                        id: u.id || u.user_id,
                        display_name: [u.first_name, u.last_name].filter(Boolean).join(' ') || u
                            .display_name || '<?php echo esc_js( __( 'Unknown', 'vms-theme' ) ); ?>'
                    }));
                }
            } catch (e) {
                console.error('User search failed:', e);
            }
        },

        selectUser(user) {
            this.filters.user_id = user.id;
            this.userSearchTerm = user.display_name;
            this.userSuggestions = [];
            this.resetAndLoad();
        },

        clearUserFilter() {
            this.filters.user_id = '';
            this.userSearchTerm = '';
            this.userSuggestions = [];
            this.resetAndLoad();
        },

        clearAllFilters() {
            this.filters = {
                search: '',
                date_from: '',
                date_to: '',
                category: '',
                action_type: '',
                user_id: ''
            };
            this.userSearchTerm = '';
            this.userSuggestions = [];
            this.resetAndLoad();
        },

        // --- Export user modal ---
        async searchExportUsers() {
            if (!this.exportUserSearchTerm || this.exportUserSearchTerm.length < 2) {
                this.exportUserSuggestions = [];
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'vms_search_guests');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('term', this.exportUserSearchTerm);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.exportUserSuggestions = (data.data.results || []).map(u => ({
                        id: u.id || u.user_id,
                        display_name: [u.first_name, u.last_name].filter(Boolean).join(' ') || u
                            .display_name || '<?php echo esc_js( __( 'Unknown', 'vms-theme' ) ); ?>'
                    }));
                }
            } catch (e) {
                console.error('Export user search failed:', e);
            }
        },

        selectExportUser(user) {
            this.exportSelectedUser = user;
            this.exportUserSearchTerm = user.display_name;
            this.exportUserSuggestions = [];
        },

        // --- Pagination ---
        resetAndLoad() {
            this.currentPage = 1;
            this.expandedRows = {};
            this.loadLogs();
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages || page === this.currentPage) return;
            this.currentPage = page;
            this.expandedRows = {};
            this.loadLogs();
        },

        // --- Row Expansion ---
        toggleRow(id) {
            this.expandedRows[id] = !this.expandedRows[id];
        },

        // --- Formatting ---
        formatDate(dateStr) {
            if (!dateStr) return '---';
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (e) {
                return dateStr.split(' ')[0] || dateStr;
            }
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr);
                return d.toLocaleTimeString(undefined, {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            } catch (e) {
                return dateStr.split(' ')[1] || '';
            }
        },

        formatRole(role) {
            if (!role) return '---';
            return role.replace(/_/g, ' ');
        },

        formatActionType(action) {
            if (!action) return '---';
            return action.charAt(0).toUpperCase() + action.slice(1).replace(/_/g, ' ');
        },

        formatCategory(category) {
            if (!category) return '---';
            return category.charAt(0).toUpperCase() + category.slice(1).replace(/_/g, ' ');
        },

        formatEntity(row) {
            if (!row.entity_type) return '---';
            let str = row.entity_type;
            if (row.entity_id) str += ' #' + row.entity_id;
            return str;
        },

        formatJson(val) {
            if (!val || val === 'null' || val === '{}') return '';
            try {
                const parsed = typeof val === 'string' ? JSON.parse(val) : val;
                return JSON.stringify(parsed, null, 2);
            } catch (e) {
                return String(val);
            }
        },

        getUserInitials(row) {
            const name = row.display_name || '';
            if (!name) return 'S';
            const parts = name.trim().split(/\s+/);
            if (parts.length >= 2) {
                return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
            }
            return name.charAt(0).toUpperCase();
        },

        // --- Badge Classes ---
        getCategoryBadgeClass(category) {
            const map = {
                'guest': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'member': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                'visit': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'accommodation': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                'supplier': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                'reciprocation': 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                'employee': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                'settings': 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                'system': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
            };
            return map[category] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
        },

        getCategoryDotClass(category) {
            const map = {
                'guest': 'bg-blue-500',
                'member': 'bg-purple-500',
                'visit': 'bg-green-500',
                'accommodation': 'bg-amber-500',
                'supplier': 'bg-orange-500',
                'reciprocation': 'bg-teal-500',
                'employee': 'bg-indigo-500',
                'settings': 'bg-gray-400',
                'system': 'bg-red-500'
            };
            return map[category] || 'bg-gray-400';
        },

        getActionBadgeClass(action) {
            const map = {
                'create': 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400 ring-1 ring-green-600/10',
                'update': 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400 ring-1 ring-blue-600/10',
                'delete': 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400 ring-1 ring-red-600/10',
                'login': 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 ring-1 ring-emerald-600/10',
                'logout': 'bg-slate-50 text-slate-700 dark:bg-slate-900/20 dark:text-slate-400 ring-1 ring-slate-600/10',
                'approve': 'bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-400 ring-1 ring-teal-600/10',
                'reject': 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-400 ring-1 ring-rose-600/10',
                'signin': 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-400 ring-1 ring-cyan-600/10',
                'signout': 'bg-violet-50 text-violet-700 dark:bg-violet-900/20 dark:text-violet-400 ring-1 ring-violet-600/10',
                'export': 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400 ring-1 ring-amber-600/10',
                'import': 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400 ring-1 ring-indigo-600/10'
            };
            return map[action] ||
                'bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-400 ring-1 ring-gray-600/10';
        },

        // --- Export Functions ---
        async exportAllPdf() {
            await this.doExport({});
        },

        async exportFilteredPdf() {
            await this.doExport({
                search: this.filters.search,
                date_from: this.filters.date_from,
                date_to: this.filters.date_to,
                user_id: this.filters.user_id,
                category: this.filters.category,
                action_type: this.filters.action_type
            });
        },

        async exportUserPdf() {
            if (!this.exportSelectedUser) return;

            await this.doExport({
                user_id: this.exportSelectedUser.id,
                personalized: 1,
                user_name: this.exportSelectedUser.display_name
            });

            this.showUserExportModal = false;
        },

        async doExport(params) {
            this.exporting = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_export_audit_pdf');
                formData.append('_ajax_nonce', vmsTheme.nonces.audit);

                Object.entries(params).forEach(([key, val]) => {
                    if (val) formData.append(key, val);
                });

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const contentType = response.headers.get('content-type');

                if (contentType && contentType.includes('application/pdf')) {
                    // Direct PDF download
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'audit-logs-' + new Date().toISOString().split('T')[0] + '.pdf';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();

                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            type: 'success',
                            message: '<?php echo esc_js( __( 'PDF exported successfully.', 'vms-theme' ) ); ?>'
                        }
                    }));
                } else if (contentType && contentType.includes('application/json')) {
                    // JSON response (possibly with a download URL or error)
                    const data = await response.json();

                    if (data.success && data.data.url) {
                        window.open(data.data.url, '_blank');
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'success',
                                message: '<?php echo esc_js( __( 'PDF exported successfully.', 'vms-theme' ) ); ?>'
                            }
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'error',
                                message: data.data?.message || vmsTheme.i18n.error
                            }
                        }));
                    }
                } else {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            type: 'error',
                            message: vmsTheme.i18n.error
                        }
                    }));
                }
            } catch (e) {
                console.error('Export failed:', e);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        type: 'error',
                        message: vmsTheme.i18n.error
                    }
                }));
            } finally {
                this.exporting = false;
            }
        }
    };
}

/**
 * Global toast helper — wraps the custom event dispatch.
 * Usage: window.vmsToast('Saved!', 'success')
 */
if (typeof window.vmsToast === 'undefined') {
    window.vmsToast = function(message, type) {
        type = type || 'info';
        window.dispatchEvent(new CustomEvent('toast', {
            detail: {
                type: type,
                message: message
            }
        }));
    };
}
</script>

<?php
get_footer();