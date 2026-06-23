<?php
/* Template Name: VMS Reciprocation */

/**
 * Reciprocating clubs, members, and visits management.
 *
 * Reciprocating members are guests from other clubs who visit.
 * They MUST be affiliated with at least one reciprocating club.
 * They CANNOT login (no WP user account).
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

vms_require_module( 'reciprocation' );
vms_require_capability( 'vms_manage_reciprocation' );

get_header();
?>

<div class="flex flex-col gap-6" x-data="reciprocationPage()" x-init="init()">

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Reciprocation', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php esc_html_e( 'Manage reciprocation records, register new reciprocation, and view visit history.', 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <!-- Export Button -->
                <button @click="handleExport()" class="vms-btn vms-btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?php esc_html_e( 'Export PDF', 'vms-theme' ); ?>
                </button>
                <!-- Primary Action Button (context-sensitive) -->
                <button @click="openAddModal()" class="vms-btn vms-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span
                        x-text="activeTab === 'clubs' ? '<?php echo esc_js( __( 'Add Club', 'vms-theme' ) ); ?>' : activeTab === 'members' ? '<?php echo esc_js( __( 'Register Member', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Register Visit', 'vms-theme' ) ); ?>'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="vms-card">
        <div class="flex gap-1">
            <template x-for="tab in tabs" :key="tab.key">
                <button @click="switchTab(tab.key)"
                    class="flex-1 px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200" :class="activeTab === tab.key
						? 'bg-[var(--vms-primary)] text-white shadow-lg shadow-[var(--vms-primary)]/25'
						: 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50'" x-text="tab.label"></button>
            </template>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="vms-card">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="search" x-model.debounce.400ms="searchTerm"
                    :placeholder="activeTab === 'clubs' ? '<?php echo esc_attr( __( 'Search clubs by name, email, country...', 'vms-theme' ) ); ?>' : activeTab === 'members' ? '<?php echo esc_attr( __( 'Search members by name, member #, ID...', 'vms-theme' ) ); ?>' : '<?php echo esc_attr( __( 'Search visits by member name, club...', 'vms-theme' ) ); ?>'"
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
            </div>
            <!-- Club filter for Members tab -->
            <select x-show="activeTab === 'members'" x-model="memberClubFilter"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                <option value=""><?php esc_html_e( 'All Clubs', 'vms-theme' ); ?></option>
                <template x-for="club in clubs" :key="club.id">
                    <option :value="club.id" x-text="club.name"></option>
                </template>
            </select>
            <!-- Reason filter for Visits tab -->
            <select x-show="activeTab === 'visits'" x-model="visitReasonFilter"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                <option value=""><?php esc_html_e( 'All Reasons', 'vms-theme' ); ?></option>
                <option value="casual"><?php esc_html_e( 'Casual', 'vms-theme' ); ?></option>
                <option value="tournament"><?php esc_html_e( 'Tournament', 'vms-theme' ); ?></option>
            </select>
            <!-- Per-page selector -->
            <select x-model="perPage" @change="currentPage = 1"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                <option value="20">20 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
                <option value="50">50 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
                <option value="100">100 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
            </select>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="vms-card text-center">
        <svg class="animate-spin h-8 w-8 mx-auto text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?php esc_html_e( 'Loading...', 'vms-theme' ); ?></p>
    </div>

    <!-- ==================== CLUBS TAB ==================== -->
    <div x-show="!loading && activeTab === 'clubs'" x-cloak>
        <!-- Clubs Table -->
        <div x-show="paginatedClubs.length > 0" class="vms-card">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Club Name', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                                <?php esc_html_e( 'Email', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                                <?php esc_html_e( 'Phone', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                                <?php esc_html_e( 'Country', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Status', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                                <?php esc_html_e( '# Members', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-right px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Actions', 'vms-theme' ); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <template x-for="club in paginatedClubs" :key="club.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-sm font-medium text-indigo-600 dark:text-indigo-400"
                                            x-text="(club.name || '?')[0].toUpperCase()"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                                x-text="club.name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 md:hidden"
                                                x-text="club.email || ''"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="club.email || '-'"></span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="club.phone || '-'"></span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="club.country || '-'"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full" :class="club.status === 'active'
											? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
											: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                                        x-text="club.status === 'active' ? '<?php echo esc_js( __( 'Active', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Inactive', 'vms-theme' ) ); ?>'"></span>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--vms-primary)]/10 text-[var(--vms-primary)]"
                                        x-text="getClubMemberCount(club.id)"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="editClub(club)"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                            title="<?php esc_attr_e( 'Edit', 'vms-theme' ); ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteClub(club)"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                            title="<?php esc_attr_e( 'Delete', 'vms-theme' ); ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Clubs Empty State -->
        <div x-show="filteredClubs.length === 0" class="vms-card text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                <?php esc_html_e( 'No Reciprocating Clubs', 'vms-theme' ); ?>
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Add reciprocal club agreements to manage cross-club visits.', 'vms-theme' ); ?>
            </p>
        </div>
    </div>

    <!-- ==================== MEMBERS TAB ==================== -->
    <div x-show="!loading && activeTab === 'members'" x-cloak>
        <!-- Members Table -->
        <div x-show="paginatedMembers.length > 0" class="vms-card">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Member', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                                <?php esc_html_e( 'Club', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                                <?php esc_html_e( 'Member #', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                                <?php esc_html_e( 'ID Number', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                                <?php esc_html_e( 'Phone', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Status', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-right px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Actions', 'vms-theme' ); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <template x-for="member in paginatedMembers" :key="member.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-sm font-medium text-[var(--vms-primary)]"
                                            x-text="(member.first_name?.[0] || '') + (member.last_name?.[0] || '')">
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                                x-text="member.first_name + ' ' + member.last_name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 md:hidden"
                                                x-text="getClubName(member.club_id)"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400"
                                        x-text="getClubName(member.club_id)"></span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="member.member_number || '-'"></span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="member.id_number || '-'"></span>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="member.phone || '-'"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full" :class="{
											'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': member.status === 'active',
											'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': member.status === 'suspended',
											'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': member.status === 'banned',
											'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': !['active','suspended','banned'].includes(member.status)
										}" x-text="member.status || '<?php echo esc_js( __( 'Unknown', 'vms-theme' ) ); ?>'"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="editMember(member)"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                            title="<?php esc_attr_e( 'Edit', 'vms-theme' ); ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Members Empty State -->
        <div x-show="filteredMembers.length === 0" class="vms-card text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                <?php esc_html_e( 'No Reciprocating Members', 'vms-theme' ); ?>
            </h3>
            <p class="text-gray-500 dark:text-gray-400"
                x-text="searchTerm || memberClubFilter ? '<?php echo esc_js( __( 'No members match your search or filter criteria.', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Register members from reciprocating clubs to track their visits.', 'vms-theme' ) ); ?>'">
            </p>
        </div>
    </div>

    <!-- ==================== VISITS TAB ==================== -->
    <div x-show="!loading && activeTab === 'visits'" x-cloak>
        <!-- Visits Table -->
        <div x-show="paginatedVisits.length > 0" class="vms-card">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Member', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                                <?php esc_html_e( 'Club', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Visit Date', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                                <?php esc_html_e( 'Reason', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Status', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                                <?php esc_html_e( 'Sign In', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                                <?php esc_html_e( 'Sign Out', 'vms-theme' ); ?>
                            </th>
                            <th
                                class="text-right px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?php esc_html_e( 'Actions', 'vms-theme' ); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <template x-for="visit in paginatedVisits" :key="visit.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                                            :class="{
												'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': visit.sign_in_time && !visit.sign_out_time,
												'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': !visit.sign_in_time,
												'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400': visit.sign_out_time
											}" x-text="(visit.member_first_name?.[0] || '') + (visit.member_last_name?.[0] || '')"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                                x-text="(visit.member_first_name || '') + ' ' + (visit.member_last_name || '')">
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 md:hidden"
                                                x-text="getClubName(visit.club_id)"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400"
                                        x-text="getClubName(visit.club_id)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="visit.visit_date"></span>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full" :class="visit.reason === 'tournament'
											? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
											: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'"
                                        x-text="visit.reason === 'tournament' ? '<?php echo esc_js( __( 'Tournament', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Casual', 'vms-theme' ) ); ?>'"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full" :class="{
											'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': visit.sign_in_time && !visit.sign_out_time,
											'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': !visit.sign_in_time && visit.status === 'approved',
											'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400': visit.sign_out_time,
											'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': !visit.sign_in_time && visit.status !== 'approved'
										}">
                                        <span
                                            x-show="visit.sign_out_time"><?php esc_html_e( 'Completed', 'vms-theme' ); ?></span>
                                        <span
                                            x-show="visit.sign_in_time && !visit.sign_out_time"><?php esc_html_e( 'Signed In', 'vms-theme' ); ?></span>
                                        <span
                                            x-show="!visit.sign_in_time && visit.status === 'approved'"><?php esc_html_e( 'Expected', 'vms-theme' ); ?></span>
                                        <span x-show="!visit.sign_in_time && visit.status !== 'approved'"
                                            x-text="visit.status || '<?php echo esc_js( __( 'Pending', 'vms-theme' ) ); ?>'"></span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="visit.sign_in_time ? formatTime(visit.sign_in_time) : '-'"></span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400"
                                        x-text="visit.sign_out_time ? formatTime(visit.sign_out_time) : '-'"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Sign In -->
                                        <template
                                            x-if="!visit.sign_in_time && (visit.status === 'approved' || visit.status === 'pending')">
                                            <button @click="handleSignIn(visit)"
                                                class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition-colors"
                                                title="<?php esc_attr_e( 'Sign In', 'vms-theme' ); ?>">
                                                <?php esc_html_e( 'Sign In', 'vms-theme' ); ?>
                                            </button>
                                        </template>
                                        <!-- Sign Out -->
                                        <template x-if="visit.sign_in_time && !visit.sign_out_time">
                                            <button @click="handleSignOut(visit)"
                                                class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition-colors"
                                                title="<?php esc_attr_e( 'Sign Out', 'vms-theme' ); ?>">
                                                <?php esc_html_e( 'Sign Out', 'vms-theme' ); ?>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Visits Empty State -->
        <div x-show="filteredVisits.length === 0" class="vms-card text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                <?php esc_html_e( 'No Visits Found', 'vms-theme' ); ?>
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Register a visit for a reciprocating member to get started.', 'vms-theme' ); ?>
            </p>
        </div>
    </div>

    <!-- ==================== PAGINATION (shared) ==================== -->
    <div x-show="!loading && totalPages > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <?php esc_html_e( 'Showing', 'vms-theme' ); ?>
            <span class="font-medium text-gray-900 dark:text-white"
                x-text="((currentPage - 1) * parseInt(perPage)) + 1"></span>
            -
            <span class="font-medium text-gray-900 dark:text-white"
                x-text="Math.min(currentPage * parseInt(perPage), totalFilteredItems)"></span>
            <?php esc_html_e( 'of', 'vms-theme' ); ?>
            <span class="font-medium text-gray-900 dark:text-white" x-text="totalFilteredItems"></span>
        </p>
        <div class="flex items-center gap-1">
            <button @click="currentPage = 1" :disabled="currentPage <= 1"
                class="px-3 py-2 text-sm font-medium rounded-lg transition-colors disabled:opacity-40"
                :class="currentPage <= 1 ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                &laquo;
            </button>
            <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage <= 1"
                class="px-3 py-2 text-sm font-medium rounded-lg transition-colors disabled:opacity-40"
                :class="currentPage <= 1 ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                &lsaquo;
            </button>
            <template x-for="p in paginationRange" :key="p">
                <button x-show="p !== '...'" @click="currentPage = p"
                    class="px-3 py-2 text-sm font-medium rounded-lg transition-colors" :class="p === currentPage
						? 'bg-[var(--vms-primary)] text-white shadow-sm'
						: 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" x-text="p"></button>
                <span x-show="p === '...'" class="px-2 py-2 text-sm text-gray-400">...</span>
            </template>
            <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage >= totalPages"
                class="px-3 py-2 text-sm font-medium rounded-lg transition-colors disabled:opacity-40"
                :class="currentPage >= totalPages ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                &rsaquo;
            </button>
            <button @click="currentPage = totalPages" :disabled="currentPage >= totalPages"
                class="px-3 py-2 text-sm font-medium rounded-lg transition-colors disabled:opacity-40"
                :class="currentPage >= totalPages ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                &raquo;
            </button>
        </div>
    </div>

    <!-- ==================== ADD/EDIT CLUB MODAL ==================== -->
    <div x-show="showClubModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div @click="showClubModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto"
            @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                    x-text="editingClub ? '<?php echo esc_js( __( 'Edit Club', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Add Reciprocating Club', 'vms-theme' ) ); ?>'">
                </h3>
                <button @click="showClubModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form @submit.prevent="saveClub" class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php esc_html_e( 'Club Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="clubForm.name" required
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Email', 'vms-theme' ); ?>
                        </label>
                        <input type="email" x-model="clubForm.email"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Phone', 'vms-theme' ); ?>
                        </label>
                        <input type="tel" x-model="clubForm.phone"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Country', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="clubForm.country"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Agreement Date', 'vms-theme' ); ?>
                        </label>
                        <input type="date" x-model="clubForm.agreement_date"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php esc_html_e( 'Address / Location', 'vms-theme' ); ?>
                    </label>
                    <input type="text" x-model="clubForm.location"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php esc_html_e( 'Status', 'vms-theme' ); ?>
                    </label>
                    <select x-model="clubForm.status"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                        <option value="active"><?php esc_html_e( 'Active', 'vms-theme' ); ?></option>
                        <option value="suspended"><?php esc_html_e( 'Suspended', 'vms-theme' ); ?></option>
                        <option value="banned"><?php esc_html_e( 'Banned', 'vms-theme' ); ?></option>
                    </select>
                </div>

                <div x-show="clubError" x-cloak
                    class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
                    x-text="clubError"></div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showClubModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button type="submit" :disabled="saving"
                        class="px-4 py-2 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="saving" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span
                            x-text="editingClub ? '<?php echo esc_js( __( 'Update Club', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Add Club', 'vms-theme' ) ); ?>'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== ADD/EDIT MEMBER MODAL ==================== -->
    <div x-show="showMemberModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div @click="showMemberModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto"
            @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                    x-text="editingMember ? '<?php echo esc_js( __( 'Edit Member', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Register Reciprocating Member', 'vms-theme' ) ); ?>'">
                </h3>
                <button @click="showMemberModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form @submit.prevent="saveMember" class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php esc_html_e( 'Affiliated Club', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                    </label>
                    <select x-model="memberForm.club_id" required
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                        <option value=""><?php esc_html_e( '-- Select Club --', 'vms-theme' ); ?></option>
                        <template x-for="club in clubs.filter(c => c.status === 'active')" :key="club.id">
                            <option :value="club.id" x-text="club.name"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <?php esc_html_e( 'Members must be affiliated with a reciprocating club.', 'vms-theme' ); ?>
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'First Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="memberForm.first_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Last Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="memberForm.last_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Member Number', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="memberForm.member_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'ID / Passport Number', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="memberForm.id_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Phone', 'vms-theme' ); ?>
                        </label>
                        <input type="tel" x-model="memberForm.phone"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Email', 'vms-theme' ); ?>
                        </label>
                        <input type="email" x-model="memberForm.email"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php esc_html_e( 'Status', 'vms-theme' ); ?>
                    </label>
                    <select x-model="memberForm.status"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                        <option value="active"><?php esc_html_e( 'Active', 'vms-theme' ); ?></option>
                        <option value="suspended"><?php esc_html_e( 'Suspended', 'vms-theme' ); ?></option>
                        <option value="banned"><?php esc_html_e( 'Banned', 'vms-theme' ); ?></option>
                    </select>
                </div>

                <div x-show="memberError" x-cloak
                    class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
                    x-text="memberError"></div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showMemberModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button type="submit" :disabled="saving"
                        class="px-4 py-2 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="saving" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span
                            x-text="editingMember ? '<?php echo esc_js( __( 'Update Member', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Register Member', 'vms-theme' ) ); ?>'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== REGISTER VISIT MODAL ==================== -->
    <div x-show="showVisitModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div @click="showVisitModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto"
            @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php esc_html_e( 'Register Visit', 'vms-theme' ); ?>
                </h3>
                <button @click="showVisitModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form @submit.prevent="saveVisit" class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php esc_html_e( 'Member', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                    </label>
                    <select x-model="visitForm.member_id" required
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                        <option value=""><?php esc_html_e( '-- Select Member --', 'vms-theme' ); ?></option>
                        <template x-for="member in members.filter(m => m.status === 'active')" :key="member.id">
                            <option :value="member.id"
                                x-text="member.first_name + ' ' + member.last_name + ' (' + getClubName(member.club_id) + ')'">
                            </option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Visit Date', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="date" x-model="visitForm.visit_date" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Reason for Visit', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <select x-model="visitForm.reason" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                            <option value=""><?php esc_html_e( '-- Select Reason --', 'vms-theme' ); ?></option>
                            <option value="casual"><?php esc_html_e( 'Casual', 'vms-theme' ); ?></option>
                            <option value="tournament"><?php esc_html_e( 'Tournament', 'vms-theme' ); ?></option>
                        </select>
                    </div>
                </div>

                <div x-show="visitError" x-cloak
                    class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
                    x-text="visitError"></div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showVisitModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button type="submit" :disabled="saving"
                        class="px-4 py-2 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="saving" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <?php esc_html_e( 'Register Visit', 'vms-theme' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function reciprocationPage() {
    return {
        /* ---- State ---- */
        activeTab: 'clubs',
        loading: true,
        saving: false,
        searchTerm: '',
        perPage: '20',
        currentPage: 1,

        /* Data */
        clubs: [],
        members: [],
        visits: [],

        /* Tabs */
        tabs: [{
                key: 'clubs',
                label: '<?php echo esc_js( __( 'Clubs', 'vms-theme' ) ); ?>'
            },
            {
                key: 'members',
                label: '<?php echo esc_js( __( 'Members', 'vms-theme' ) ); ?>'
            },
            {
                key: 'visits',
                label: '<?php echo esc_js( __( 'Visits', 'vms-theme' ) ); ?>'
            }
        ],

        /* Filters */
        memberClubFilter: '',
        visitReasonFilter: '',

        /* Club modal */
        showClubModal: false,
        editingClub: null,
        clubForm: {
            name: '',
            email: '',
            phone: '',
            country: '',
            location: '',
            agreement_date: '',
            status: 'active'
        },
        clubError: '',

        /* Member modal */
        showMemberModal: false,
        editingMember: null,
        memberForm: {
            club_id: '',
            first_name: '',
            last_name: '',
            member_number: '',
            id_number: '',
            phone: '',
            email: '',
            status: 'active'
        },
        memberError: '',

        /* Visit modal */
        showVisitModal: false,
        visitForm: {
            member_id: '',
            visit_date: '',
            reason: ''
        },
        visitError: '',

        /* ---- Computed: Filtered lists ---- */

        get filteredClubs() {
            let list = this.clubs;
            if (this.searchTerm) {
                const term = this.searchTerm.toLowerCase();
                list = list.filter(c =>
                    (c.name && c.name.toLowerCase().includes(term)) ||
                    (c.email && c.email.toLowerCase().includes(term)) ||
                    (c.country && c.country.toLowerCase().includes(term))
                );
            }
            return list;
        },

        get filteredMembers() {
            let list = this.members;
            if (this.memberClubFilter) {
                list = list.filter(m => String(m.club_id) === String(this.memberClubFilter));
            }
            if (this.searchTerm) {
                const term = this.searchTerm.toLowerCase();
                list = list.filter(m =>
                    (m.first_name && m.first_name.toLowerCase().includes(term)) ||
                    (m.last_name && m.last_name.toLowerCase().includes(term)) ||
                    (m.member_number && m.member_number.toLowerCase().includes(term)) ||
                    (m.id_number && m.id_number.toLowerCase().includes(term)) ||
                    (m.phone && m.phone.toLowerCase().includes(term))
                );
            }
            return list;
        },

        get filteredVisits() {
            let list = this.visits;
            if (this.visitReasonFilter) {
                list = list.filter(v => v.reason === this.visitReasonFilter);
            }
            if (this.searchTerm) {
                const term = this.searchTerm.toLowerCase();
                list = list.filter(v =>
                    (v.member_first_name && v.member_first_name.toLowerCase().includes(term)) ||
                    (v.member_last_name && v.member_last_name.toLowerCase().includes(term)) ||
                    (this.getClubName(v.club_id).toLowerCase().includes(term))
                );
            }
            return list;
        },

        /* ---- Computed: Pagination ---- */

        get currentFilteredList() {
            if (this.activeTab === 'clubs') return this.filteredClubs;
            if (this.activeTab === 'members') return this.filteredMembers;
            return this.filteredVisits;
        },

        get totalFilteredItems() {
            return this.currentFilteredList.length;
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.totalFilteredItems / parseInt(this.perPage)));
        },

        get paginatedClubs() {
            const start = (this.currentPage - 1) * parseInt(this.perPage);
            return this.filteredClubs.slice(start, start + parseInt(this.perPage));
        },

        get paginatedMembers() {
            const start = (this.currentPage - 1) * parseInt(this.perPage);
            return this.filteredMembers.slice(start, start + parseInt(this.perPage));
        },

        get paginatedVisits() {
            const start = (this.currentPage - 1) * parseInt(this.perPage);
            return this.filteredVisits.slice(start, start + parseInt(this.perPage));
        },

        get paginationRange() {
            const total = this.totalPages;
            const current = this.currentPage;
            const range = [];

            if (total <= 7) {
                for (let i = 1; i <= total; i++) range.push(i);
            } else {
                range.push(1);
                if (current > 3) range.push('...');
                for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
                    range.push(i);
                }
                if (current < total - 2) range.push('...');
                range.push(total);
            }
            return range;
        },

        /* ---- Helpers ---- */

        getClubName(clubId) {
            const club = this.clubs.find(c => String(c.id) === String(clubId));
            return club ? club.name : '<?php echo esc_js( __( 'Unknown Club', 'vms-theme' ) ); ?>';
        },

        getClubMemberCount(clubId) {
            return this.members.filter(m => String(m.club_id) === String(clubId)).length;
        },

        formatTime(datetime) {
            if (!datetime) return '';
            const d = new Date(datetime.replace(' ', 'T'));
            return d.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        /* ---- Tab switching ---- */

        switchTab(tab) {
            this.activeTab = tab;
            this.searchTerm = '';
            this.currentPage = 1;
        },

        /* ---- Open add modals (context-sensitive) ---- */

        openAddModal() {
            if (this.activeTab === 'clubs') {
                this.editingClub = null;
                this.clubForm = {
                    name: '',
                    email: '',
                    phone: '',
                    country: '',
                    location: '',
                    agreement_date: '',
                    status: 'active'
                };
                this.clubError = '';
                this.showClubModal = true;
            } else if (this.activeTab === 'members') {
                this.editingMember = null;
                this.memberForm = {
                    club_id: '',
                    first_name: '',
                    last_name: '',
                    member_number: '',
                    id_number: '',
                    phone: '',
                    email: '',
                    status: 'active'
                };
                this.memberError = '';
                this.showMemberModal = true;
            } else {
                this.visitForm = {
                    member_id: '',
                    visit_date: new Date().toISOString().split('T')[0],
                    reason: ''
                };
                this.visitError = '';
                this.showVisitModal = true;
            }
        },

        /* ---- Init ---- */

        init() {
            this.loadClubs();
            this.loadMembers();
            this.loadVisits();
        },

        /* ---- AJAX: Clubs ---- */

        async loadClubs() {
            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_recip_clubs');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    console.log("loadClubs response:", data);
                    let fetchedClubs = data.data && data.data.rows ? data.data.rows : (data.data || []);
                    this.clubs = fetchedClubs.map(c => ({
                        ...c,
                        name: c.club_name,
                        email: c.club_email,
                        phone: c.club_phone,
                        location: c.club_address,
                        status: c.club_status
                    }));
                }
            } catch (e) {
                console.error('Failed to load reciprocating clubs:', e);
            } finally {
                this.loading = false;
            }
        },

        editClub(club) {
            this.editingClub = club;
            this.clubForm = {
                name: club.name || '',
                email: club.email || '',
                phone: club.phone || '',
                country: club.country || '',
                location: club.location || '',
                agreement_date: club.agreement_date || '',
                status: club.status || 'active'
            };
            this.clubError = '';
            this.showClubModal = true;
        },

        async saveClub() {
            this.clubError = '';
            this.saving = true;

            try {
                const formData = new FormData();
                formData.append('action', this.editingClub ? 'vms_update_recip_club' : 'vms_register_recip_club');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                if (this.editingClub) {
                    formData.append('club_id', this.editingClub.id);
                }

                const apiMap = {
                    name: 'club_name',
                    email: 'club_email',
                    phone: 'club_phone',
                    location: 'club_address',
                    status: 'club_status'
                };
                Object.keys(this.clubForm).forEach(key => {
                    let apiKey = apiMap[key] || key;
                    formData.append(apiKey, this.clubForm[key]);
                });

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showClubModal = false;
                    this.loadClubs();
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Club saved successfully.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    this.clubError = data.data?.message || vmsTheme.i18n.error;
                }
            } catch (e) {
                console.error(e);
                this.clubError = `${vmsTheme.i18n.error} [${e.message}]`;
            } finally {
                this.saving = false;
            }
        },

        async deleteClub(club) {
            if (!confirm(vmsTheme.i18n.confirm)) return;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_delete_recip_club');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('club_id', club.id);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.loadClubs();
                    this.loadMembers();
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Club removed.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    window.vmsToast(data.data?.message || vmsTheme.i18n.error, 'error');
                }
            } catch (e) {
                window.vmsToast(vmsTheme.i18n.error, 'error');
            }
        },

        /* ---- AJAX: Members ---- */

        async loadMembers() {
            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_recip_members');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    console.log("loadMembers response:", data);
                    let fetchedMembers = data.data && data.data.rows ? data.data.rows : (data.data || []);
                    this.members = fetchedMembers.map(m => ({
                        ...m,
                        phone: m.phone_number,
                        status: m.member_status
                    }));
                }
            } catch (e) {
                console.error('Failed to load reciprocating members:', e);
            }
        },

        editMember(member) {
            this.editingMember = member;
            this.memberForm = {
                club_id: member.club_id || '',
                first_name: member.first_name || '',
                last_name: member.last_name || '',
                member_number: member.member_number || '',
                id_number: member.id_number || '',
                phone: member.phone || '',
                email: member.email || '',
                status: member.status || 'active'
            };
            this.memberError = '';
            this.showMemberModal = true;
        },

        async saveMember() {
            this.memberError = '';

            if (!this.memberForm.club_id) {
                this.memberError =
                    '<?php echo esc_js( __( 'Please select an affiliated club.', 'vms-theme' ) ); ?>';
                return;
            }

            this.saving = true;

            try {
                const formData = new FormData();
                formData.append('action', this.editingMember ? 'vms_update_recip_member' :
                    'vms_register_recip_member');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                if (this.editingMember) {
                    formData.append('member_id', this.editingMember.id);
                }

                const apiMap = {
                    fname: 'first_name',
                    lname: 'last_name',
                    email: 'email',
                    phone: 'phone_number',
                    id_num: 'id_number',
                    member_no: 'member_number',
                    status: 'member_status'
                };
                Object.keys(this.memberForm).forEach(key => {
                    let apiKey = apiMap[key] || key;
                    if (key === 'receive_emails' || key === 'receive_messages') {
                        formData.append(apiKey, this.memberForm[key] ? 1 : 0);
                    } else {
                        formData.append(apiKey, this.memberForm[key]);
                    }
                });

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showMemberModal = false;
                    this.loadMembers();
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Member saved successfully.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    this.memberError = data.data?.message || vmsTheme.i18n.error;
                }
            } catch (e) {
                console.error(e);
                this.memberError = `${vmsTheme.i18n.error} [${e.message}]`;
            } finally {
                this.saving = false;
            }
        },

        /* ---- AJAX: Visits ---- */

        async loadVisits() {
            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_recip_visits');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    // Ensure visits is always an array - handle various response formats
                    let fetchedVisits = [];
                    if (data.data && Array.isArray(data.data.visits)) {
                        fetchedVisits = data.data.visits;
                    } else if (data.data && Array.isArray(data.data)) {
                        fetchedVisits = data.data;
                    } else if (data.data && data.data.rows && Array.isArray(data.data.rows)) {
                        fetchedVisits = data.data.rows;
                    }
                    this.visits = fetchedVisits;
                } else {
                    this.visits = [];
                }
            } catch (e) {
                console.error('Failed to load reciprocating visits:', e);
                this.visits = [];
            }
        },

        async saveVisit() {
            this.visitError = '';

            if (!this.visitForm.member_id) {
                this.visitError = '<?php echo esc_js( __( 'Please select a member.', 'vms-theme' ) ); ?>';
                return;
            }
            if (!this.visitForm.reason) {
                this.visitError = '<?php echo esc_js( __( 'Please select a reason for visit.', 'vms-theme' ) ); ?>';
                return;
            }

            this.saving = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_register_recip_visit');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('member_id', this.visitForm.member_id);
                formData.append('visit_date', this.visitForm.visit_date);
                formData.append('visit_reason', this.visitForm.reason);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showVisitModal = false;
                    this.loadVisits();
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Visit registered successfully.', 'vms-theme' ) ); ?>',
                        'success');
                } else {
                    this.visitError = data.data?.message || vmsTheme.i18n.error;
                }
            } catch (e) {
                console.error(e);
                this.visitError = `${vmsTheme.i18n.error} [${e.message}]`;
            } finally {
                this.saving = false;
            }
        },

        /* ---- Sign In / Sign Out ---- */

        async handleSignIn(visit) {
            if (!confirm('<?php echo esc_js( __( 'Sign in this member?', 'vms-theme' ) ); ?>')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_signin_recip');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('visit_id', visit.id);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.loadVisits();
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Member signed in.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    window.vmsToast(data.data?.message || vmsTheme.i18n.error, 'error');
                }
            } catch (e) {
                window.vmsToast(vmsTheme.i18n.error, 'error');
            }
        },

        async handleSignOut(visit) {
            if (!confirm('<?php echo esc_js( __( 'Sign out this member?', 'vms-theme' ) ); ?>')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_signout_recip');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('visit_id', visit.id);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.loadVisits();
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Member signed out.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    window.vmsToast(data.data?.message || vmsTheme.i18n.error, 'error');
                }
            } catch (e) {
                window.vmsToast(vmsTheme.i18n.error, 'error');
            }
        },

        /* ---- Export PDF ---- */

        handleExport() {
            if (typeof jspdf === 'undefined' && typeof window.jspdf === 'undefined') {
                /* Load jsPDF dynamically if not already present */
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                script.onload = () => {
                    const autoTableScript = document.createElement('script');
                    autoTableScript.src =
                        'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js';
                    autoTableScript.onload = () => this.generatePdf();
                    document.head.appendChild(autoTableScript);
                };
                document.head.appendChild(script);
                return;
            }
            this.generatePdf();
        },

        generatePdf() {
            try {
                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF();

                if (this.activeTab === 'members' || this.activeTab === 'clubs') {
                    /* Export members list */
                    doc.setFontSize(16);
                    doc.text('<?php echo esc_js( __( 'Reciprocating Members', 'vms-theme' ) ); ?>', 14, 20);
                    doc.setFontSize(10);
                    doc.text('<?php echo esc_js( __( 'Generated:', 'vms-theme' ) ); ?> ' + new Date()
                        .toLocaleDateString(), 14, 28);

                    const memberRows = this.filteredMembers.map(m => [
                        m.first_name + ' ' + m.last_name,
                        this.getClubName(m.club_id),
                        m.member_number || '-',
                        m.id_number || '-',
                        m.phone || '-',
                        m.status || '-'
                    ]);

                    doc.autoTable({
                        startY: 34,
                        head: [
                            ['<?php echo esc_js( __( 'Name', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Club', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Member #', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'ID Number', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Phone', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Status', 'vms-theme' ) ); ?>'
                            ]
                        ],
                        body: memberRows,
                        styles: {
                            fontSize: 8
                        },
                        headStyles: {
                            fillColor: [14, 165, 233]
                        }
                    });

                    doc.save('reciprocating-members.pdf');
                } else {
                    /* Export visits */
                    doc.setFontSize(16);
                    doc.text('<?php echo esc_js( __( 'Reciprocating Visits', 'vms-theme' ) ); ?>', 14, 20);
                    doc.setFontSize(10);
                    doc.text('<?php echo esc_js( __( 'Generated:', 'vms-theme' ) ); ?> ' + new Date()
                        .toLocaleDateString(), 14, 28);

                    const visitRows = this.filteredVisits.map(v => [
                        (v.member_first_name || '') + ' ' + (v.member_last_name || ''),
                        this.getClubName(v.club_id),
                        v.visit_date || '-',
                        v.reason === 'tournament' ? '<?php echo esc_js( __( 'Tournament', 'vms-theme' ) ); ?>' :
                        '<?php echo esc_js( __( 'Casual', 'vms-theme' ) ); ?>',
                        v.sign_in_time ? this.formatTime(v.sign_in_time) : '-',
                        v.sign_out_time ? this.formatTime(v.sign_out_time) : '-'
                    ]);

                    doc.autoTable({
                        startY: 34,
                        head: [
                            ['<?php echo esc_js( __( 'Member', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Club', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Date', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Reason', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Sign In', 'vms-theme' ) ); ?>',
                                '<?php echo esc_js( __( 'Sign Out', 'vms-theme' ) ); ?>'
                            ]
                        ],
                        body: visitRows,
                        styles: {
                            fontSize: 8
                        },
                        headStyles: {
                            fillColor: [14, 165, 233]
                        }
                    });

                    doc.save('reciprocating-visits.pdf');
                }

                window.vmsToast('<?php echo esc_js( __( 'PDF exported successfully.', 'vms-theme' ) ); ?>', 'success');
            } catch (e) {
                console.error('PDF export error:', e);
                window.vmsToast('<?php echo esc_js( __( 'Failed to export PDF.', 'vms-theme' ) ); ?>', 'error');
            }
        }
    };
}
</script>

<?php
get_footer();