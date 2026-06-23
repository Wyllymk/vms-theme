<?php
/* Template Name: VMS Members */

/**
 * Member list and management page.
 *
 * Shows all club members (WordPress users with 'member' or 'chairman' roles)
 * with search, filtering, pagination, approve/reject workflows, member detail
 * modals, and PDF export capabilities.
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

vms_require_module( 'members' );
vms_require_capability( 'vms_approve_members' );

get_header();
?>

<div class="flex flex-col gap-6" x-data="membersPage()" x-init="init()">

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Members', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php esc_html_e( 'Manage club members, approve registrations, and export records.', 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <!-- Export All Members PDF -->
                <button @click="exportAllMembers()" :disabled="exportingAll"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50">
                    <svg x-show="!exportingAll" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <svg x-show="exportingAll" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                    <?php esc_html_e( 'Export All', 'vms-theme' ); ?>
                </button>
                <!-- Total badge -->
                <span
                    class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full bg-[var(--vms-primary)]/10 text-[var(--vms-primary)]"
                    x-text="totalMembers + ' total'"></span>
            </div>
        </div>
    </div>

    <!-- ================================================================
	     SEARCH & FILTERS
	     ================================================================ -->
    <div class="vms-card">
        <div class="flex flex-col gap-3 lg:flex-row">
            <!-- Search input -->
            <div class="relative flex-1">
                <svg class="absolute w-5 h-5 text-gray-400 -translate-y-1/2 left-3 top-1/2" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="search" x-model="searchTerm" @input.debounce.400ms="loadMembers()"
                    placeholder="<?php esc_attr_e( 'Search by name, email, or phone...', 'vms-theme' ); ?>"
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
            </div>

            <!-- Role filter -->
            <select x-model="roleFilter" @change="currentPage = 1; loadMembers()"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                <option value=""><?php esc_html_e( 'All Roles', 'vms-theme' ); ?></option>
                <option value="member"><?php esc_html_e( 'Members', 'vms-theme' ); ?></option>
                <option value="chairman"><?php esc_html_e( 'Chairman', 'vms-theme' ); ?></option>
            </select>

            <!-- Per-page selector -->
            <select x-model.number="perPage" @change="currentPage = 1; loadMembers()"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                <option value="20">20 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
                <option value="50">50 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
                <option value="100">100 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
            </select>
        </div>

        <!-- Status filter tabs -->
        <div class="flex flex-wrap gap-2 mt-3">
            <template x-for="tab in statusTabs" :key="tab.value">
                <button @click="statusFilter = tab.value; currentPage = 1; loadMembers()"
                    class="px-3.5 py-1.5 text-sm font-medium rounded-lg transition-all duration-200" :class="statusFilter === tab.value
						? 'bg-[var(--vms-primary)] text-white shadow-lg shadow-[var(--vms-primary)]/25'
						: 'bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600/50'"
                    x-text="tab.label"></button>
            </template>
        </div>
    </div>

    <!-- ================================================================
	     MEMBERS TABLE
	     ================================================================ -->
    <div class="overflow-hidden vms-card">

        <!-- Loading state -->
        <div x-show="loading" class="text-center vms-card">
            <svg class="animate-spin h-8 w-8 mx-auto text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Loading members...', 'vms-theme' ); ?></p>
        </div>

        <!-- Table -->
        <div x-show="!loading && members.length > 0" x-cloak class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th
                            class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                            <?php esc_html_e( 'Name', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="hidden px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400 md:table-cell">
                            <?php esc_html_e( 'Email', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="hidden px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400 lg:table-cell">
                            <?php esc_html_e( 'Phone', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                            <?php esc_html_e( 'Role', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                            <?php esc_html_e( 'Status', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="hidden px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400 xl:table-cell">
                            <?php esc_html_e( 'Registered', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            <?php esc_html_e( 'Actions', 'vms-theme' ); ?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <template x-for="member in members" :key="member.user_id">
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <!-- Name with avatar -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-sm font-medium text-[var(--vms-primary)]"
                                        x-text="getInitials(member)"></div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate dark:text-white"
                                            x-text="getFullName(member)"></p>
                                        <p class="text-xs text-gray-500 truncate dark:text-gray-400 md:hidden"
                                            x-text="member.email"></p>
                                    </div>
                                </div>
                            </td>
                            <!-- Email -->
                            <td class="hidden px-6 py-4 md:table-cell">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="member.email || '-'"></span>
                            </td>
                            <!-- Phone -->
                            <td class="hidden px-6 py-4 lg:table-cell">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="member.phone || '-'"></span>
                            </td>
                            <!-- Role badge -->
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full"
                                    :class="getRoleBadgeClass(member)" x-text="formatRole(member)"></span>
                            </td>
                            <!-- Status badge -->
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full"
                                    :class="getStatusBadgeClass(member)" x-text="formatStatus(member)"></span>
                            </td>
                            <!-- Registered date -->
                            <td class="hidden px-6 py-4 xl:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="formatDate(member.registered)"></span>
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Approve (pending only) -->
                                    <button x-show="member.member_status === 'pending'"
                                        @click="openApproveConfirm(member)"
                                        class="p-1.5 rounded-lg text-green-500 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                                        title="<?php esc_attr_e( 'Approve', 'vms-theme' ); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <!-- Reject (pending only) -->
                                    <button x-show="member.member_status === 'pending'" @click="openRejectModal(member)"
                                        class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        title="<?php esc_attr_e( 'Reject', 'vms-theme' ); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <!-- Edit -->
                                    <button @click="openEditModal(member)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/10 transition-colors"
                                        title="<?php esc_attr_e( 'Edit Member', 'vms-theme' ); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <!-- View -->
                                    <button @click="viewMember(member)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/10 transition-colors"
                                        title="<?php esc_attr_e( 'View Details', 'vms-theme' ); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <!-- Export member PDF -->
                                    <button @click="exportMemberPdf(member)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors"
                                        title="<?php esc_attr_e( 'Export PDF', 'vms-theme' ); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div x-show="!loading && members.length === 0" x-cloak class="text-center vms-card">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                <?php esc_html_e( 'No Members Found', 'vms-theme' ); ?>
            </h3>
            <p class="text-gray-500 dark:text-gray-400" x-text="searchTerm || statusFilter || roleFilter
					? '<?php echo esc_js( __( 'No members match your current filters. Try adjusting your search criteria.', 'vms-theme' ) ); ?>'
					: '<?php echo esc_js( __( 'There are no registered members yet.', 'vms-theme' ) ); ?>'"></p>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && totalPages > 1" x-cloak
            class="flex flex-col items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Showing', 'vms-theme' ); ?>
                <span class="font-medium text-gray-900 dark:text-white"
                    x-text="((currentPage - 1) * perPage) + 1"></span>
                <?php esc_html_e( 'to', 'vms-theme' ); ?>
                <span class="font-medium text-gray-900 dark:text-white"
                    x-text="Math.min(currentPage * perPage, totalMembers)"></span>
                <?php esc_html_e( 'of', 'vms-theme' ); ?>
                <span class="font-medium text-gray-900 dark:text-white" x-text="totalMembers"></span>
                <?php esc_html_e( 'members', 'vms-theme' ); ?>
            </p>
            <div class="flex items-center gap-1">
                <button @click="goToPage(1)" :disabled="currentPage <= 1"
                    class="p-2 text-gray-400 transition-colors rounded-lg hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed"
                    title="<?php esc_attr_e( 'First page', 'vms-theme' ); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1"
                    class="p-2 text-gray-400 transition-colors rounded-lg hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed"
                    title="<?php esc_attr_e( 'Previous page', 'vms-theme' ); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <template x-for="p in paginationRange()" :key="p">
                    <button x-show="p !== '...'" @click="goToPage(p)"
                        class="min-w-[36px] h-9 px-2 rounded-lg text-sm font-medium transition-all duration-200" :class="p === currentPage
							? 'bg-[var(--vms-primary)] text-white shadow-lg shadow-[var(--vms-primary)]/25'
							: 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'" x-text="p"></button>
                    <span x-show="p === '...'"
                        class="min-w-[36px] h-9 flex items-center justify-center text-gray-400 text-sm">...</span>
                </template>

                <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages"
                    class="p-2 text-gray-400 transition-colors rounded-lg hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed"
                    title="<?php esc_attr_e( 'Next page', 'vms-theme' ); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <button @click="goToPage(totalPages)" :disabled="currentPage >= totalPages"
                    class="p-2 text-gray-400 transition-colors rounded-lg hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed"
                    title="<?php esc_attr_e( 'Last page', 'vms-theme' ); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- ================================================================
	     REJECT MODAL  (with optional reason)
	     ================================================================ -->
    <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click="showRejectModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md bg-white border border-gray-200 shadow-2xl dark:bg-gray-800 rounded-2xl dark:border-gray-700"
            @click.stop x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            <!-- Top shine -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 to-transparent rounded-t-2xl"
                aria-hidden="true"></div>

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php esc_html_e( 'Reject Member', 'vms-theme' ); ?>
                </h3>
                <button @click="showRejectModal = false"
                    class="p-1 text-gray-400 transition-colors rounded-lg hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <p class="mb-1 text-sm text-gray-600 dark:text-gray-400">
                    <?php esc_html_e( 'You are about to reject the membership application for:', 'vms-theme' ); ?>
                </p>
                <p class="mb-4 text-sm font-semibold text-gray-900 dark:text-white"
                    x-text="rejectTarget ? getFullName(rejectTarget) : ''"></p>

                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <?php esc_html_e( 'Reason (optional)', 'vms-theme' ); ?>
                    </label>
                    <textarea x-model="rejectReason" rows="3"
                        placeholder="<?php esc_attr_e( 'Provide a reason for the rejection...', 'vms-theme' ); ?>"
                        class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="showRejectModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button type="button" @click="confirmReject()" :disabled="rejecting"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-red-600 shadow-sm hover:bg-red-700 rounded-xl disabled:opacity-50">
                        <svg x-show="rejecting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <?php esc_html_e( 'Reject Member', 'vms-theme' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================
	     EDIT MEMBER MODAL
	     ================================================================ -->
    <div x-show="showEditModal" x-cloak class="vms-modal-overlay">
        <div @click="showEditModal = false" class="absolute inset-0"></div>
        <div class="overflow-y-auto vms-modal vms-modal-lg" @click.stop>
            <div class="vms-flex vms-items-center vms-justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php esc_html_e( 'Edit Member', 'vms-theme' ); ?>
                </h3>
                <button @click="showEditModal = false" class="vms-btn vms-btn-sm vms-btn-secondary">&times;</button>

            </div>
            <form @submit.prevent="submitEditMember" class="p-6 flex flex-col gap-4">
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'First Name', 'vms-theme' ); ?> *</label>
                        <input type="text" class="vms-input" x-model="editForm.first_name" required>
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Last Name', 'vms-theme' ); ?> *</label>
                        <input type="text" class="vms-input" x-model="editForm.last_name" required>
                    </div>
                </div>
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Phone Number', 'vms-theme' ); ?></label>
                        <input type="tel" class="vms-input" x-model="editForm.phone_number">
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Email', 'vms-theme' ); ?></label>
                        <input type="email" class="vms-input" x-model="editForm.email">
                    </div>
                </div>
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Member Number', 'vms-theme' ); ?> *</label>
                        <input type="tel" class="vms-input" x-model="editForm.member_number" required>
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Status', 'vms-theme' ); ?></label>
                        <select x-model="editForm.member_status" class="vms-input">
                            <option value="pending"><?php esc_html_e( 'Pending', 'vms-theme' ); ?></option>
                            <option value="approved"><?php esc_html_e( 'Approved', 'vms-theme' ); ?></option>
                            <option value="active"><?php esc_html_e( 'Active', 'vms-theme' ); ?></option>
                            <option value="suspended"><?php esc_html_e( 'Suspended', 'vms-theme' ); ?></option>
                            <option value="banned"><?php esc_html_e( 'Banned', 'vms-theme' ); ?></option>
                        </select>
                    </div>
                </div>
                <div x-show="editError" x-cloak class="p-3 text-sm text-red-600 rounded-xl bg-red-50"
                    x-text="editError"></div>
                <div class="vms-flex vms-justify-between pt-2">
                    <button type="button" @click="showEditModal = false"
                        class="vms-btn vms-btn-secondary"><?php esc_html_e( 'Cancel', 'vms-theme' ); ?></button>
                    <button type="submit" :disabled="editSaving" class="vms-btn vms-btn-primary">
                        <svg x-show="editSaving" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <?php esc_html_e( 'Save Changes', 'vms-theme' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================
	     VIEW MEMBER DETAILS MODAL
	     ================================================================ -->
    <div x-show="showDetailModal" x-cloak class="vms-modal-overlay"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click="showDetailModal = false" class="absolute inset-0"></div>
        <div class="vms-modal vms-modal-lg" @click.stop x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            <!-- Top shine -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 to-transparent rounded-t-2xl"
                aria-hidden="true"></div>

            <!-- Modal header -->
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <div class="flex items-center gap-3" x-show="detailMember">
                    <div class="w-10 h-10 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-sm font-bold text-[var(--vms-primary)]"
                        x-text="detailMember ? getInitials(detailMember) : ''"></div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                            x-text="detailMember ? getFullName(detailMember) : ''"></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400"
                            x-text="detailMember ? detailMember.email : ''"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Status changer for non-pending members -->
                    <template x-if="detailMember && detailMember.member_status !== 'pending'">
                        <select x-model="detailStatusChange"
                            @change="updateMemberStatus(detailMember, detailStatusChange)"
                            class="text-xs px-2.5 py-1.5 bg-(--vms-glass-bg) border border-(--vms-border) rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                            <option value="approved"><?php esc_html_e( 'Approved', 'vms-theme' ); ?></option>
                            <option value="active"><?php esc_html_e( 'Active', 'vms-theme' ); ?></option>
                            <option value="suspended"><?php esc_html_e( 'Suspended', 'vms-theme' ); ?></option>
                            <option value="banned"><?php esc_html_e( 'Banned', 'vms-theme' ); ?></option>
                        </select>
                    </template>
                    <button @click="showDetailModal = false"
                        class="vms-btn vms-btn-sm vms-btn-secondary">&times;</button>

                </div>
            </div>

            <!-- Modal body (scrollable) -->
            <div class="flex-1 p-6 space-y-6 overflow-y-auto">

                <!-- Loading detail -->
                <div x-show="detailLoading" class="py-8 text-center">
                    <svg class="animate-spin h-8 w-8 mx-auto text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <?php esc_html_e( 'Loading member details...', 'vms-theme' ); ?></p>
                </div>

                <div x-show="!detailLoading && detailMember" class="flex flex-col gap-3">

                    <!-- Member info grid -->
                    <div class="p-4 bg-gray-50 dark:bg-(--vms-glass-bg) border border-(--vms-border) rounded-xl">
                        <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                            <?php esc_html_e( 'Member Information', 'vms-theme' ); ?>
                        </h4>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php esc_html_e( 'Full Name', 'vms-theme' ); ?></p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white"
                                    x-text="detailMember ? getFullName(detailMember) : '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php esc_html_e( 'Email', 'vms-theme' ); ?></p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white"
                                    x-text="detailMember ? (detailMember.email || '-') : '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php esc_html_e( 'Phone', 'vms-theme' ); ?></p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white"
                                    x-text="detailMember ? (detailMember.phone || '-') : '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php esc_html_e( 'Role', 'vms-theme' ); ?></p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white"
                                    x-text="detailMember ? formatRole(detailMember) : '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php esc_html_e( 'Status', 'vms-theme' ); ?></p>
                                <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full"
                                    :class="detailMember ? getStatusBadgeClass(detailMember) : ''"
                                    x-text="detailMember ? formatStatus(detailMember) : '-'"></span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php esc_html_e( 'Registered', 'vms-theme' ); ?></p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white"
                                    x-text="detailMember ? formatDate(detailMember.registered) : '-'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Registered Guests -->
                    <div>
                        <h4 class="flex items-center gap-2 mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                            <svg class="w-4 h-4 text-[var(--vms-primary)]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <?php esc_html_e( 'Registered Guests', 'vms-theme' ); ?>
                            <span class="text-xs font-normal text-gray-400"
                                x-text="'(' + (detailGuests?.length || 0) + ')'"></span>
                        </h4>

                        <div x-show="detailGuests && detailGuests.length > 0"
                            class="overflow-x-auto border border-gray-200 rounded-xl dark:border-gray-700">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <th
                                            class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                            <?php esc_html_e( 'Guest Name', 'vms-theme' ); ?></th>
                                        <th
                                            class="hidden px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400 sm:table-cell">
                                            <?php esc_html_e( 'Phone', 'vms-theme' ); ?></th>
                                        <th
                                            class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                            <?php esc_html_e( 'Status', 'vms-theme' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                    <template x-for="guest in detailGuests" :key="guest.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                            <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-white"
                                                x-text="(guest.first_name || '') + ' ' + (guest.last_name || '')"></td>
                                            <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell"
                                                x-text="guest.phone_number || '-'"></td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full"
                                                    :class="{
														'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': guest.guest_status === 'active',
														'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': guest.guest_status === 'suspended',
														'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': guest.guest_status === 'banned'
													}" x-text="guest.guest_status || 'unknown'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="!detailGuests || detailGuests.length === 0"
                            class="py-6 text-center bg-gray-50 dark:bg-gray-700/20 rounded-xl">
                            <p class="text-sm text-gray-400 dark:text-gray-500">
                                <?php esc_html_e( 'No registered guests for this member.', 'vms-theme' ); ?></p>
                        </div>
                    </div>

                    <!-- Visit History -->
                    <div>
                        <h4 class="flex items-center gap-2 mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                            <svg class="w-4 h-4 text-[var(--vms-primary)]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php esc_html_e( 'Visit History', 'vms-theme' ); ?>
                            <span class="text-xs font-normal text-gray-400"
                                x-text="'(' + (detailVisits?.length || 0) + ')'"></span>
                        </h4>

                        <div x-show="detailVisits && detailVisits.length > 0"
                            class="overflow-x-auto border border-gray-200 rounded-xl dark:border-gray-700">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <th
                                            class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                            <?php esc_html_e( 'Guest', 'vms-theme' ); ?></th>
                                        <th
                                            class="hidden px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400 sm:table-cell">
                                            <?php esc_html_e( 'Date', 'vms-theme' ); ?></th>
                                        <th
                                            class="hidden px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400 md:table-cell">
                                            <?php esc_html_e( 'Sign In', 'vms-theme' ); ?></th>
                                        <th
                                            class="hidden px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400 md:table-cell">
                                            <?php esc_html_e( 'Sign Out', 'vms-theme' ); ?></th>
                                        <th
                                            class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                            <?php esc_html_e( 'Status', 'vms-theme' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                    <template x-for="visit in detailVisits" :key="visit.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                            <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-white"
                                                x-text="(visit.first_name || '') + ' ' + (visit.last_name || '')"></td>
                                            <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell"
                                                x-text="visit.visit_date || '-'"></td>
                                            <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400 hidden md:table-cell"
                                                x-text="visit.sign_in_time || '-'"></td>
                                            <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400 hidden md:table-cell"
                                                x-text="visit.sign_out_time || '-'"></td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full"
                                                    :class="{
														'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': visit.status === 'approved',
														'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': visit.status === 'completed',
														'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': visit.status === 'unapproved' || visit.status === 'pending',
														'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': visit.status === 'cancelled' || visit.status === 'rejected'
													}" x-text="visit.status || 'unknown'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="!detailVisits || detailVisits.length === 0"
                            class="py-6 text-center bg-gray-50 dark:bg-gray-700/20 rounded-xl">
                            <p class="text-sm text-gray-400 dark:text-gray-500">
                                <?php esc_html_e( 'No visit history for this member.', 'vms-theme' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div
                class="flex items-center justify-between px-6 py-4 border-t border-gray-200 shrink-0 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <button type="button" @click="detailMember && exportMemberPdf(detailMember)"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-purple-700 transition-colors border border-purple-200 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 dark:border-purple-800 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?php esc_html_e( 'Export Records as PDF', 'vms-theme' ); ?>
                </button>
                <button type="button" @click="showDetailModal = false" class="vms-btn vms-btn-secondary">
                    <?php esc_html_e( 'Close', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function membersPage() {
    return {
        /* ── Data ─────────────────────────────────────────────── */
        members: [],
        loading: true,
        searchTerm: '',
        statusFilter: '',
        roleFilter: '',
        currentPage: 1,
        perPage: 20,
        totalMembers: 0,
        totalPages: 0,
        exportingAll: false,

        /* Status filter tabs */
        statusTabs: [{
                value: '',
                label: '<?php echo esc_js( __( 'All', 'vms-theme' ) ); ?>'
            },
            {
                value: 'pending',
                label: '<?php echo esc_js( __( 'Pending', 'vms-theme' ) ); ?>'
            },
            {
                value: 'approved',
                label: '<?php echo esc_js( __( 'Approved / Active', 'vms-theme' ) ); ?>'
            },
            {
                value: 'suspended',
                label: '<?php echo esc_js( __( 'Suspended', 'vms-theme' ) ); ?>'
            },
            {
                value: 'banned',
                label: '<?php echo esc_js( __( 'Banned', 'vms-theme' ) ); ?>'
            }
        ],

        /* Reject modal */
        showRejectModal: false,
        rejectTarget: null,
        rejectReason: '',
        rejecting: false,

        /* Edit modal */
        showEditModal: false,
        editingMember: null,
        editForm: {},
        editError: '',
        editSaving: false,

        /* Detail / view modal */
        showDetailModal: false,
        detailMember: null,
        detailGuests: [],
        detailVisits: [],
        detailLoading: false,
        detailStatusChange: '',

        /* ── Lifecycle ────────────────────────────────────────── */
        init() {
            this.loadMembers();
        },

        /* ── AJAX: Load members list ─────────────────────────── */
        async loadMembers() {
            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_members_list');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('page', this.currentPage);
                formData.append('per_page', this.perPage);

                if (this.searchTerm) {
                    formData.append('search', this.searchTerm);
                }
                if (this.statusFilter) {
                    formData.append('status', this.statusFilter);
                }
                if (this.roleFilter) {
                    formData.append('role', this.roleFilter);
                }

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.members = data.data.rows || [];
                    this.totalMembers = parseInt(data.data.total) || 0;
                    this.totalPages = parseInt(data.data.pages) || 0;
                } else {
                    this.members = [];
                    this.totalMembers = 0;
                    this.totalPages = 0;
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Failed to load members.', 'vms-theme' ) ); ?>', 'error');
                }
            } catch (e) {
                console.error('Failed to load members:', e);
                window.vmsToast(
                    '<?php echo esc_js( __( 'An error occurred while loading members.', 'vms-theme' ) ); ?>',
                    'error');
            } finally {
                this.loading = false;
            }
        },

        /* ── Pagination ──────────────────────────────────────── */
        goToPage(page) {
            if (page < 1 || page > this.totalPages || page === this.currentPage) return;
            this.currentPage = page;
            this.loadMembers();
        },

        paginationRange() {
            const total = this.totalPages;
            const current = this.currentPage;
            const delta = 2;
            const range = [];
            const rangeWithDots = [];

            for (let i = 1; i <= total; i++) {
                if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
                    range.push(i);
                }
            }

            let prev = null;
            for (const i of range) {
                if (prev !== null) {
                    if (i - prev === 2) {
                        rangeWithDots.push(prev + 1);
                    } else if (i - prev > 2) {
                        rangeWithDots.push('...');
                    }
                }
                rangeWithDots.push(i);
                prev = i;
            }

            return rangeWithDots;
        },

        /* ── Approve member ──────────────────────────────────── */
        openApproveConfirm(member) {
            if (!confirm('<?php echo esc_js( __( 'Are you sure you want to approve this member?', 'vms-theme' ) ); ?>'))
                return;
            this.approveMember(member);
        },

        async approveMember(member) {
            try {
                const formData = new FormData();
                formData.append('action', 'vms_approve_member');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('user_id', member.user_id);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Member approved successfully.', 'vms-theme' ) ); ?>', 'success'
                    );
                    this.loadMembers();
                } else {
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Failed to approve member.', 'vms-theme' ) ); ?>', 'error');
                }
            } catch (e) {
                console.error('Approve failed:', e);
                window.vmsToast('<?php echo esc_js( __( 'An error occurred.', 'vms-theme' ) ); ?>', 'error');
            }
        },

        /* ── Reject member ───────────────────────────────────── */
        openRejectModal(member) {
            this.rejectTarget = member;
            this.rejectReason = '';
            this.showRejectModal = true;
        },

        async confirmReject() {
            if (!this.rejectTarget) return;
            this.rejecting = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_reject_member');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('user_id', this.rejectTarget.user_id);

                if (this.rejectReason) {
                    formData.append('reason', this.rejectReason);
                }

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Member rejected.', 'vms-theme' ) ); ?>', 'success');
                    this.showRejectModal = false;
                    this.rejectTarget = null;
                    this.loadMembers();
                } else {
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Failed to reject member.', 'vms-theme' ) ); ?>', 'error');
                }
            } catch (e) {
                console.error('Reject failed:', e);
                window.vmsToast('<?php echo esc_js( __( 'An error occurred.', 'vms-theme' ) ); ?>', 'error');
            } finally {
                this.rejecting = false;
            }
        },

        /* ── Edit member ─────────────────────────────────────── */
        openEditModal(member) {
            this.editingMember = member;
            this.editForm = {
                first_name: member.first_name || '',
                last_name: member.last_name || '',
                phone: member.phone || '',
                email: member.email || '',
                member_number: member.member_number || '',
                member_status: member.member_status || 'pending'
            };
            this.editError = '';
            this.showEditModal = true;
        },

        async submitEditMember() {
            this.editError = '';
            this.editSaving = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_update_member_profile');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('user_id', this.editingMember.user_id);

                Object.keys(this.editForm).forEach(key => {
                    formData.append(key, this.editForm[key]);
                });

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showEditModal = false;
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Member updated successfully.', 'vms-theme' ) ); ?>', 'success');
                    this.loadMembers();
                } else {
                    this.editError = data.data?.message ||
                        '<?php echo esc_js( __( 'Failed to update member.', 'vms-theme' ) ); ?>';
                }
            } catch (e) {
                console.error('Update failed:', e);
                this.editError = '<?php echo esc_js( __( 'An error occurred.', 'vms-theme' ) ); ?>';
            } finally {
                this.editSaving = false;
            }
        },

        /* ── Update member status (from detail modal) ────────── */
        async updateMemberStatus(member, newStatus) {
            if (!member || !newStatus) return;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_update_member_status');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('user_id', member.user_id);
                formData.append('status', newStatus);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    member.member_status = newStatus;
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Member status updated.', 'vms-theme' ) ); ?>', 'success');
                    this.loadMembers();
                } else {
                    /* revert the dropdown */
                    this.detailStatusChange = member.member_status;
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Failed to update status.', 'vms-theme' ) ); ?>', 'error');
                }
            } catch (e) {
                this.detailStatusChange = member.member_status;
                console.error('Status update failed:', e);
                window.vmsToast('<?php echo esc_js( __( 'An error occurred.', 'vms-theme' ) ); ?>', 'error');
            }
        },

        /* ── View member details ─────────────────────────────── */
        async viewMember(member) {
            this.detailMember = member;
            this.detailStatusChange = member.member_status || 'approved';
            this.detailGuests = [];
            this.detailVisits = [];
            this.detailLoading = true;
            this.showDetailModal = true;

            try {
                /* Load member's guests */
                const guestFd = new FormData();
                guestFd.append('action', 'vms_search_guests');
                guestFd.append('_ajax_nonce', vmsTheme.nonces.guest);
                guestFd.append('member_id', member.user_id);
                guestFd.append('term', '  ');

                /* Load member's visits */
                const visitFd = new FormData();
                visitFd.append('action', 'vms_get_visits');
                visitFd.append('_ajax_nonce', vmsTheme.nonces.guest);
                visitFd.append('member_id', member.user_id);

                const [guestRes, visitRes] = await Promise.all([
                    fetch(vmsTheme.ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: guestFd
                    }),
                    fetch(vmsTheme.ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: visitFd
                    })
                ]);

                const guestData = await guestRes.json();
                const visitData = await visitRes.json();

                if (guestData.success) {
                    this.detailGuests = guestData.data.results || guestData.data.guests || [];
                }
                if (visitData.success) {
                    this.detailVisits = visitData.data.visits || visitData.data.results || [];
                }
            } catch (e) {
                console.error('Failed to load member details:', e);
            } finally {
                this.detailLoading = false;
            }
        },

        /* ── Export single member PDF ────────────────────────── */
        exportMemberPdf(member) {
            if (!member) return;

            const params = new URLSearchParams({
                action: 'vms_export_member_pdf',
                _ajax_nonce: vmsTheme.nonces.guest,
                user_id: member.user_id
            });

            window.open(vmsTheme.ajaxUrl + '?' + params.toString(), '_blank');
        },

        /* ── Export all members PDF ──────────────────────────── */
        async exportAllMembers() {
            this.exportingAll = true;

            try {
                const params = new URLSearchParams({
                    action: 'vms_export_member_pdf',
                    _ajax_nonce: vmsTheme.nonces.guest,
                    all: '1'
                });

                if (this.statusFilter) {
                    params.append('status', this.statusFilter);
                }
                if (this.roleFilter) {
                    params.append('role', this.roleFilter);
                }

                window.open(vmsTheme.ajaxUrl + '?' + params.toString(), '_blank');
                window.vmsToast(
                    '<?php echo esc_js( __( 'PDF export started. Check your downloads.', 'vms-theme' ) ); ?>',
                    'success');
            } catch (e) {
                console.error('Export failed:', e);
                window.vmsToast('<?php echo esc_js( __( 'Export failed.', 'vms-theme' ) ); ?>', 'error');
            } finally {
                /* Short delay so the user sees loading state */
                setTimeout(() => {
                    this.exportingAll = false;
                }, 1500);
            }
        },

        /* ── Helpers ─────────────────────────────────────────── */
        getFullName(member) {
            const first = member.first_name || '';
            const last = member.last_name || '';
            return (first + ' ' + last).trim() || member.display_name || '-';
        },

        getInitials(member) {
            const first = (member.first_name || member.display_name || '')[0] || '';
            const last = (member.last_name || '')[0] || '';
            return (first + last).toUpperCase() || '?';
        },

        formatRole(member) {
            const roles = member.roles || '';
            const role = Array.isArray(roles) ? roles[0] : roles;
            if (!role) return '<?php echo esc_js( __( 'None', 'vms-theme' ) ); ?>';
            return role.charAt(0).toUpperCase() + role.slice(1).replace(/_/g, ' ');
        },

        getRoleBadgeClass(member) {
            const roles = member.roles || '';
            const role = Array.isArray(roles) ? roles[0] : roles;
            if (role === 'chairman') {
                return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
            }
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        },

        formatStatus(member) {
            const status = member.member_status || 'pending';
            return status.charAt(0).toUpperCase() + status.slice(1);
        },

        getStatusBadgeClass(member) {
            const status = member.member_status || 'pending';
            const classes = {
                pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                suspended: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                banned: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
            };
            return classes[status] || classes.pending;
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return dateStr;
                return date.toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (e) {
                return dateStr;
            }
        }
    };
}
</script>

<?php
get_footer();