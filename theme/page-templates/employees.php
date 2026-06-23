<?php
/**
 * Template Name: VMS Employees
 *
 * Staff management page — list, register, edit, view details, and export staff records.
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

vms_require_module( 'employees' );
vms_require_capability( 'vms_manage_employees' );

get_header();
?>

<div class="flex flex-col gap-6" x-data="staffPage()" x-init="init()">

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Staff', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php esc_html_e( 'Manage staff members, roles, and activity.', 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <button @click="exportAllStaffPdf()" :disabled="exporting" class="vms-btn vms-btn-secondary">
                    <svg x-show="!exporting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <svg x-show="exporting" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                    <?php esc_html_e( 'Export All PDF', 'vms-theme' ); ?>
                </button>
                <button @click="openRegisterModal()" class="vms-btn vms-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?php esc_html_e( 'Register Staff', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ──────────────────── Search, Filters & Pagination ──────────────────── -->
    <div class="vms-card flex flex-col gap-4">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-700 pb-3 overflow-x-auto">
            <template x-for="tab in statusTabs" :key="tab.value">
                <button @click="statusFilter = tab.value; currentPage = 1; fetchStaff()"
                    class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors"
                    :class="statusFilter === tab.value
						? 'bg-[var(--vms-primary)] text-white shadow-sm'
						: 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'"
                    x-text="tab.label + (tab.value === '' ? ' (' + totalRecords + ')' : '')"></button>
            </template>
        </div>

        <!-- Search + Department/Role Filter + Per-Page -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="search" x-model="searchTerm" @input.debounce.400ms="currentPage = 1; fetchStaff()"
                    placeholder="<?php esc_attr_e( 'Search by name, employee # or department...', 'vms-theme' ); ?>"
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
            </div>

            <select x-model="roleFilter" @change="currentPage = 1; fetchStaff()"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                <option value=""><?php esc_html_e( 'All Roles', 'vms-theme' ); ?></option>
                <option value="gate"><?php esc_html_e( 'Gate', 'vms-theme' ); ?></option>
                <option value="reception"><?php esc_html_e( 'Receptionist', 'vms-theme' ); ?></option>
                <option value="general_manager"><?php esc_html_e( 'General Manager', 'vms-theme' ); ?></option>
            </select>

            <select x-model="perPage" @change="currentPage = 1; fetchStaff()"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                <option value="20">20 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
                <option value="50">50 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
                <option value="100">100 <?php esc_html_e( 'per page', 'vms-theme' ); ?></option>
            </select>
        </div>
    </div>

    <!-- ──────────────────────── Staff Table ──────────────────────── -->
    <div class="vms-card">

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <svg class="animate-spin h-8 w-8 mx-auto text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Loading staff...', 'vms-theme' ); ?></p>
        </div>

        <!-- Table -->
        <div x-show="!loading && paginatedStaff.length > 0" class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th
                            class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?php esc_html_e( 'Name', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                            <?php esc_html_e( 'Email', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">
                            <?php esc_html_e( 'Phone', 'vms-theme' ); ?>
                        </th>
                        <th
                            class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                            <?php esc_html_e( 'Position / Role', 'vms-theme' ); ?>
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
                    <template x-for="staff in paginatedStaff" :key="staff.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <!-- Name with avatar -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-sm font-semibold text-[var(--vms-primary)] shrink-0"
                                        x-text="(staff.first_name?.[0] || '') + (staff.last_name?.[0] || '')"></div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                            x-text="staff.first_name + ' ' + staff.last_name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 sm:hidden"
                                            x-text="'#' + (staff.employee_number || '-')"></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="staff.email || '-'"></span>
                            </td>

                            <!-- Phone -->
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="staff.phone_number || '-'"></span>
                            </td>

                            <!-- Position / Role -->
                            <td class="px-6 py-4 hidden md:table-cell">
                                <span
                                    class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full bg-[var(--vms-primary)]/10 text-[var(--vms-primary)]"
                                    x-text="formatPosition(staff.position)"></span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full capitalize"
                                    :class="{
										'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': staff.employee_status === 'active',
										'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': staff.employee_status === 'suspended',
										'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': staff.employee_status === 'terminated'
									}" x-text="staff.employee_status || 'unknown'"></span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- View -->
                                    <button @click="openViewModal(staff)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/10 transition-colors"
                                        title="<?php esc_attr_e( 'View Details', 'vms-theme' ); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- Edit -->
                                    <button @click="openEditModal(staff)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                        title="<?php esc_attr_e( 'Edit', 'vms-theme' ); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Export PDF -->
                                    <button @click="exportStaffPdf(staff)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors"
                                        title="<?php esc_attr_e( 'Export PDF', 'vms-theme' ); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </button>

                                    <!-- Delete -->
                                    <button @click="confirmDelete(staff)"
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

        <!-- Empty State -->
        <div x-show="!loading && paginatedStaff.length === 0" x-cloak class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                <?php esc_html_e( 'No Staff Found', 'vms-theme' ); ?>
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4" x-text="searchTerm || statusFilter || roleFilter
					? '<?php echo esc_js( __( 'No staff members match your current filters.', 'vms-theme' ) ); ?>'
					: '<?php echo esc_js( __( 'Register your first staff member to get started.', 'vms-theme' ) ); ?>'"></p>
            <button x-show="!searchTerm && !statusFilter && !roleFilter" @click="openRegisterModal()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-[var(--vms-primary)] text-white rounded-xl hover:bg-[var(--vms-primary)]/90 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?php esc_html_e( 'Register Staff', 'vms-theme' ); ?>
            </button>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && totalPages > 1"
            class="flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?php esc_html_e( 'Showing', 'vms-theme' ); ?>
                <span class="font-medium text-gray-900 dark:text-white"
                    x-text="((currentPage - 1) * perPage) + 1"></span>
                <?php esc_html_e( 'to', 'vms-theme' ); ?>
                <span class="font-medium text-gray-900 dark:text-white"
                    x-text="Math.min(currentPage * perPage, totalRecords)"></span>
                <?php esc_html_e( 'of', 'vms-theme' ); ?>
                <span class="font-medium text-gray-900 dark:text-white" x-text="totalRecords"></span>
                <?php esc_html_e( 'staff', 'vms-theme' ); ?>
            </p>
            <div class="flex items-center gap-1">
                <button @click="goToPage(1)" :disabled="currentPage <= 1"
                    class="px-2.5 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">&laquo;</button>
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1"
                    class="px-2.5 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">&lsaquo;</button>

                <template x-for="p in visiblePages" :key="p">
                    <button @click="typeof p === 'number' && goToPage(p)"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors" :class="p === currentPage
							? 'bg-[var(--vms-primary)] text-white shadow-sm'
							: typeof p === 'number'
								? 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
								: 'text-gray-400 cursor-default'" x-text="p" :disabled="typeof p !== 'number'"></button>
                </template>

                <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages"
                    class="px-2.5 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">&rsaquo;</button>
                <button @click="goToPage(totalPages)" :disabled="currentPage >= totalPages"
                    class="px-2.5 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">&raquo;</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════ REGISTER STAFF MODAL ═══════════════════════ -->
    <div x-show="showRegisterModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click="showRegisterModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl max-h-[90vh] flex flex-col"
            @click.stop x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            <!-- Header -->
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php esc_html_e( 'Register New Staff', 'vms-theme' ); ?>
                </h3>
                <button @click="showRegisterModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="registerStaff()" class="p-6 flex flex-col gap-4 overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'First Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="registerForm.first_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Last Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="registerForm.last_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Email', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="email" x-model="registerForm.email" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Phone Number', 'vms-theme' ); ?>
                        </label>
                        <input type="tel" x-model="registerForm.phone_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Employee Number', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="registerForm.employee_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'ID Number', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="registerForm.id_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Department', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="registerForm.department"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Position', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <select x-model="registerForm.position" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                            <option value=""><?php esc_html_e( 'Select position...', 'vms-theme' ); ?></option>
                            <option value="gate"><?php esc_html_e( 'Gate', 'vms-theme' ); ?></option>
                            <option value="reception"><?php esc_html_e( 'Receptionist', 'vms-theme' ); ?></option>
                            <option value="general_manager"><?php esc_html_e( 'General Manager', 'vms-theme' ); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php esc_html_e( 'Hire Date', 'vms-theme' ); ?>
                    </label>
                    <input type="date" x-model="registerForm.hire_date"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                </div>

                <!-- Error -->
                <div x-show="registerError" x-cloak
                    class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
                    x-text="registerError"></div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showRegisterModal = false"
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
                        <?php esc_html_e( 'Register Staff', 'vms-theme' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════ EDIT STAFF MODAL ═══════════════════════ -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click="showEditModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl max-h-[90vh] flex flex-col"
            @click.stop x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            <!-- Header -->
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php esc_html_e( 'Edit Staff Member', 'vms-theme' ); ?>
                </h3>
                <button @click="showEditModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="updateStaff()" class="p-6 flex flex-col gap-4 overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'First Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="editForm.first_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Last Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="editForm.last_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Email', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="email" x-model="editForm.email" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Phone Number', 'vms-theme' ); ?>
                        </label>
                        <input type="tel" x-model="editForm.phone_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Employee Number', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="editForm.employee_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'ID Number', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="editForm.id_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Department', 'vms-theme' ); ?>
                        </label>
                        <input type="text" x-model="editForm.department"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Position', 'vms-theme' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <select x-model="editForm.position" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                            <option value=""><?php esc_html_e( 'Select position...', 'vms-theme' ); ?></option>
                            <option value="gate"><?php esc_html_e( 'Gate', 'vms-theme' ); ?></option>
                            <option value="reception"><?php esc_html_e( 'Receptionist', 'vms-theme' ); ?></option>
                            <option value="general_manager"><?php esc_html_e( 'General Manager', 'vms-theme' ); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Hire Date', 'vms-theme' ); ?>
                        </label>
                        <input type="date" x-model="editForm.hire_date"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?php esc_html_e( 'Status', 'vms-theme' ); ?>
                        </label>
                        <select x-model="editForm.employee_status"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent transition-colors">
                            <option value="active"><?php esc_html_e( 'Active', 'vms-theme' ); ?></option>
                            <option value="suspended"><?php esc_html_e( 'Suspended', 'vms-theme' ); ?></option>
                            <option value="terminated"><?php esc_html_e( 'Terminated', 'vms-theme' ); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Error -->
                <div x-show="editError" x-cloak
                    class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
                    x-text="editError"></div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showEditModal = false"
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
                        <?php esc_html_e( 'Save Changes', 'vms-theme' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════ VIEW STAFF DETAILS MODAL ═══════════════════════ -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click="showViewModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-3xl max-h-[90vh] flex flex-col"
            @click.stop x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            <!-- Header -->
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-sm font-semibold text-[var(--vms-primary)]"
                        x-text="viewingStaff ? (viewingStaff.first_name?.[0] || '') + (viewingStaff.last_name?.[0] || '') : ''">
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                            x-text="viewingStaff ? viewingStaff.first_name + ' ' + viewingStaff.last_name : ''"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400"
                            x-text="viewingStaff ? formatPosition(viewingStaff.position) : ''"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="viewingStaff && exportStaffPdf(viewingStaff)"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors"
                        title="<?php esc_attr_e( 'Export PDF', 'vms-theme' ); ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </button>
                    <button @click="showViewModal = false"
                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="overflow-y-auto p-6 flex flex-col gap-6">

                <!-- Tabs -->
                <div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-700">
                    <button @click="viewTab = 'info'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                        :class="viewTab === 'info'
							? 'border-[var(--vms-primary)] text-[var(--vms-primary)]'
							: 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"><?php esc_html_e( 'Details', 'vms-theme' ); ?></button>
                    <button @click="viewTab = 'guests'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                        :class="viewTab === 'guests'
							? 'border-[var(--vms-primary)] text-[var(--vms-primary)]'
							: 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"><?php esc_html_e( 'Guests', 'vms-theme' ); ?></button>
                    <button @click="viewTab = 'activity'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                        :class="viewTab === 'activity'
							? 'border-[var(--vms-primary)] text-[var(--vms-primary)]'
							: 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"><?php esc_html_e( 'Activity Log', 'vms-theme' ); ?></button>
                </div>

                <!-- Tab: Staff Information -->
                <div x-show="viewTab === 'info'" class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'Full Name', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="viewingStaff ? viewingStaff.first_name + ' ' + viewingStaff.last_name : '-'">
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'Employee Number', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white font-mono"
                                x-text="viewingStaff?.employee_number || '-'"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'Email', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="viewingStaff?.email || '-'"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'Phone', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="viewingStaff?.phone_number || '-'"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'ID Number', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="viewingStaff?.id_number || '-'"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'Department', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="viewingStaff?.department || '-'"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'Position / Role', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="viewingStaff ? formatPosition(viewingStaff.position) : '-'"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'Status', 'vms-theme' ); ?></p>
                            <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full capitalize" :class="{
									'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': viewingStaff?.employee_status === 'active',
									'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': viewingStaff?.employee_status === 'suspended',
									'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': viewingStaff?.employee_status === 'terminated'
								}" x-text="viewingStaff?.employee_status || '-'"></span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'Hire Date', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="viewingStaff?.hire_date ? formatDate(viewingStaff.hire_date) : '-'"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                <?php esc_html_e( 'WordPress User ID', 'vms-theme' ); ?></p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="viewingStaff?.wp_user_id || '-'"></p>
                        </div>
                    </div>
                </div>

                <!-- Tab: Guests (signed in / registered by this staff member) -->
                <div x-show="viewTab === 'guests'" class="flex flex-col gap-3">
                    <div x-show="viewLoadingGuests" class="py-8 text-center">
                        <svg class="animate-spin h-6 w-6 mx-auto text-[var(--vms-primary)]" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <?php esc_html_e( 'Loading guests...', 'vms-theme' ); ?></p>
                    </div>

                    <div x-show="!viewLoadingGuests && viewGuests.length === 0" class="py-8 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500 mb-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?php esc_html_e( 'No guests registered by this staff member yet.', 'vms-theme' ); ?></p>
                    </div>

                    <template x-for="guest in viewGuests" :key="guest.id">
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/30 rounded-xl p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-xs font-medium text-blue-600 dark:text-blue-400"
                                    x-text="(guest.first_name?.[0] || '') + (guest.last_name?.[0] || '')"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"
                                        x-text="guest.first_name + ' ' + guest.last_name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="guest.phone_number || guest.email || ''"></p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400 dark:text-gray-500"
                                x-text="guest.visit_date ? formatDate(guest.visit_date) : ''"></span>
                        </div>
                    </template>
                </div>

                <!-- Tab: Activity Log -->
                <div x-show="viewTab === 'activity'" class="flex flex-col gap-3">
                    <div x-show="viewLoadingActivity" class="py-8 text-center">
                        <svg class="animate-spin h-6 w-6 mx-auto text-[var(--vms-primary)]" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <?php esc_html_e( 'Loading activity...', 'vms-theme' ); ?></p>
                    </div>

                    <div x-show="!viewLoadingActivity && viewActivity.length === 0" class="py-8 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500 mb-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?php esc_html_e( 'No activity recorded for this staff member yet.', 'vms-theme' ); ?></p>
                    </div>

                    <template x-for="(entry, idx) in viewActivity" :key="idx">
                        <div class="flex items-start gap-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl p-3">
                            <div class="mt-0.5 w-2 h-2 rounded-full shrink-0" :class="{
									'bg-green-500': entry.type === 'login',
									'bg-blue-500': entry.type === 'action',
									'bg-yellow-500': entry.type === 'update',
									'bg-gray-400': !['login','action','update'].includes(entry.type)
								}"></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-900 dark:text-white" x-text="entry.description"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                    x-text="entry.date ? formatDate(entry.date) : ''"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 shrink-0">
                <button @click="viewingStaff && openEditModal(viewingStaff); showViewModal = false"
                    class="px-4 py-2 text-sm font-medium text-[var(--vms-primary)] bg-[var(--vms-primary)]/10 rounded-xl hover:bg-[var(--vms-primary)]/20 transition-colors">
                    <?php esc_html_e( 'Edit Staff', 'vms-theme' ); ?>
                </button>
                <button @click="showViewModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <?php esc_html_e( 'Close', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════ ALPINE.JS COMPONENT ═══════════════════════ -->
<script>
function staffPage() {
    return {
        /* ── State ───────────────────────────────────────── */
        allStaff: [],
        loading: true,
        saving: false,
        exporting: false,
        searchTerm: '',
        statusFilter: '',
        roleFilter: '',
        perPage: 20,
        currentPage: 1,
        totalRecords: 0,
        totalPages: 1,

        /* Status tabs */
        statusTabs: [{
                value: '',
                label: '<?php echo esc_js( __( 'All', 'vms-theme' ) ); ?>'
            },
            {
                value: 'active',
                label: '<?php echo esc_js( __( 'Active', 'vms-theme' ) ); ?>'
            },
            {
                value: 'suspended',
                label: '<?php echo esc_js( __( 'Suspended', 'vms-theme' ) ); ?>'
            },
            {
                value: 'terminated',
                label: '<?php echo esc_js( __( 'Terminated', 'vms-theme' ) ); ?>'
            }
        ],

        /* Register modal */
        showRegisterModal: false,
        registerForm: {
            first_name: '',
            last_name: '',
            email: '',
            phone_number: '',
            employee_number: '',
            id_number: '',
            department: '',
            position: '',
            hire_date: ''
        },
        registerError: '',

        /* Edit modal */
        showEditModal: false,
        editForm: {},
        editError: '',

        /* View modal */
        showViewModal: false,
        viewingStaff: null,
        viewTab: 'info',
        viewGuests: [],
        viewActivity: [],
        viewLoadingGuests: false,
        viewLoadingActivity: false,

        /* ── Computed ────────────────────────────────────── */
        get filteredStaff() {
            let result = this.allStaff;

            /* Status filter */
            if (this.statusFilter) {
                result = result.filter(s => s.employee_status === this.statusFilter);
            }

            /* Role filter */
            if (this.roleFilter) {
                result = result.filter(s => s.position === this.roleFilter);
            }

            /* Search */
            if (this.searchTerm) {
                const term = this.searchTerm.toLowerCase();
                result = result.filter(s =>
                    (s.first_name + ' ' + s.last_name).toLowerCase().includes(term) ||
                    (s.employee_number || '').toLowerCase().includes(term) ||
                    (s.department || '').toLowerCase().includes(term)
                );
            }

            return result;
        },

        get paginatedStaff() {
            const filtered = this.filteredStaff;
            this.totalRecords = filtered.length;
            this.totalPages = Math.max(1, Math.ceil(filtered.length / this.perPage));
            if (this.currentPage > this.totalPages) this.currentPage = this.totalPages;
            const start = (this.currentPage - 1) * this.perPage;
            return filtered.slice(start, start + parseInt(this.perPage));
        },

        get visiblePages() {
            const pages = [];
            const total = this.totalPages;
            const current = this.currentPage;

            if (total <= 7) {
                for (let i = 1; i <= total; i++) pages.push(i);
            } else {
                pages.push(1);
                if (current > 3) pages.push('...');
                const start = Math.max(2, current - 1);
                const end = Math.min(total - 1, current + 1);
                for (let i = start; i <= end; i++) pages.push(i);
                if (current < total - 2) pages.push('...');
                pages.push(total);
            }

            return pages;
        },

        /* ── Init ────────────────────────────────────────── */
        init() {
            this.fetchStaff();
        },

        /* ── API Helpers ─────────────────────────────────── */
        async ajaxPost(action, extra = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('_ajax_nonce', vmsTheme.nonces.guest);
            Object.entries(extra).forEach(([k, v]) => {
                if (v !== null && v !== undefined) formData.append(k, v);
            });
            const response = await fetch(vmsTheme.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            return response.json();
        },

        /* ── Fetch Staff ─────────────────────────────────── */
        async fetchStaff() {
            this.loading = true;
            try {
                const data = await this.ajaxPost('vms_get_employees');
                if (data.success) {
                    this.allStaff = data.data.rows || data.data || [];
                } else {
                    this.allStaff = [];
                }
            } catch (e) {
                console.error('Failed to load staff:', e);
                this.allStaff = [];
            } finally {
                this.loading = false;
            }
        },

        /* ── Register Staff ──────────────────────────────── */
        openRegisterModal() {
            this.registerForm = {
                first_name: '',
                last_name: '',
                email: '',
                phone_number: '',
                employee_number: '',
                id_number: '',
                department: '',
                position: '',
                hire_date: ''
            };
            this.registerError = '';
            this.showRegisterModal = true;
        },

        async registerStaff() {
            this.registerError = '';
            this.saving = true;
            try {
                const data = await this.ajaxPost('vms_register_employee', this.registerForm);
                if (data.success) {
                    this.showRegisterModal = false;
                    this.fetchStaff();
                    window.vmsToast?.(data.data?.message ||
                        '<?php echo esc_js( __( 'Staff member registered successfully.', 'vms-theme' ) ); ?>',
                        'success');
                } else {
                    this.registerError = data.data?.message ||
                        '<?php echo esc_js( __( 'Registration failed. Please try again.', 'vms-theme' ) ); ?>';
                }
            } catch (e) {
                this.registerError = '<?php echo esc_js( __( 'An unexpected error occurred.', 'vms-theme' ) ); ?>';
            } finally {
                this.saving = false;
            }
        },

        /* ── Edit Staff ──────────────────────────────────── */
        openEditModal(staff) {
            this.editForm = {
                ...staff
            };
            this.editError = '';
            this.showEditModal = true;
        },

        async updateStaff() {
            this.editError = '';
            this.saving = true;
            try {
                const payload = {
                    ...this.editForm
                };
                payload.employee_id = payload.id;
                const data = await this.ajaxPost('vms_update_employee', payload);
                if (data.success) {
                    this.showEditModal = false;
                    this.fetchStaff();
                    window.vmsToast?.(data.data?.message ||
                        '<?php echo esc_js( __( 'Staff member updated successfully.', 'vms-theme' ) ); ?>',
                        'success');
                } else {
                    this.editError = data.data?.message ||
                        '<?php echo esc_js( __( 'Update failed. Please try again.', 'vms-theme' ) ); ?>';
                }
            } catch (e) {
                this.editError = '<?php echo esc_js( __( 'An unexpected error occurred.', 'vms-theme' ) ); ?>';
            } finally {
                this.saving = false;
            }
        },

        /* ── Delete Staff ────────────────────────────────── */
        async confirmDelete(staff) {
            if (!confirm(
                    '<?php echo esc_js( __( 'Are you sure you want to delete this staff member? This action cannot be undone.', 'vms-theme' ) ); ?>'
                )) return;
            try {
                const data = await this.ajaxPost('vms_delete_employee', {
                    employee_id: staff.id
                });
                if (data.success) {
                    this.fetchStaff();
                    window.vmsToast?.(data.data?.message ||
                        '<?php echo esc_js( __( 'Staff member deleted.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    window.vmsToast?.(data.data?.message ||
                        '<?php echo esc_js( __( 'Delete failed.', 'vms-theme' ) ); ?>', 'error');
                }
            } catch (e) {
                window.vmsToast?.('<?php echo esc_js( __( 'An unexpected error occurred.', 'vms-theme' ) ); ?>',
                    'error');
            }
        },

        /* ── View Staff Details ──────────────────────────── */
        openViewModal(staff) {
            this.viewingStaff = staff;
            this.viewTab = 'info';
            this.viewGuests = [];
            this.viewActivity = [];
            this.viewLoadingGuests = false;
            this.viewLoadingActivity = false;
            this.showViewModal = true;

            /* Pre-load guests and activity data in background */
            this.loadStaffGuests(staff);
            this.loadStaffActivity(staff);
        },

        async loadStaffGuests(staff) {
            this.viewLoadingGuests = true;
            try {
                const data = await this.ajaxPost('vms_search_employees', {
                    employee_id: staff.id,
                    context: 'guests'
                });
                if (data.success) {
                    this.viewGuests = data.data?.guests || data.data?.results || [];
                }
            } catch (e) {
                console.error('Failed to load staff guests:', e);
            } finally {
                this.viewLoadingGuests = false;
            }
        },

        async loadStaffActivity(staff) {
            this.viewLoadingActivity = true;
            try {
                const data = await this.ajaxPost('vms_search_employees', {
                    employee_id: staff.id,
                    context: 'activity'
                });
                if (data.success) {
                    this.viewActivity = data.data?.activity || data.data?.results || [];
                }
            } catch (e) {
                console.error('Failed to load staff activity:', e);
            } finally {
                this.viewLoadingActivity = false;
            }
        },

        /* ── PDF Export ──────────────────────────────────── */
        async exportStaffPdf(staff) {
            try {
                const data = await this.ajaxPost('vms_export_staff_pdf', {
                    employee_id: staff.id
                });
                if (data.success && data.data?.url) {
                    window.open(data.data.url, '_blank');
                    window.vmsToast?.('<?php echo esc_js( __( 'PDF exported successfully.', 'vms-theme' ) ); ?>',
                        'success');
                } else {
                    window.vmsToast?.(data.data?.message ||
                        '<?php echo esc_js( __( 'PDF export failed.', 'vms-theme' ) ); ?>', 'error');
                }
            } catch (e) {
                window.vmsToast?.('<?php echo esc_js( __( 'An unexpected error occurred.', 'vms-theme' ) ); ?>',
                    'error');
            }
        },

        async exportAllStaffPdf() {
            this.exporting = true;
            try {
                const data = await this.ajaxPost('vms_export_staff_pdf', {
                    export_all: 1
                });
                if (data.success && data.data?.url) {
                    window.open(data.data.url, '_blank');
                    window.vmsToast?.(
                        '<?php echo esc_js( __( 'Staff list exported successfully.', 'vms-theme' ) ); ?>',
                        'success');
                } else {
                    window.vmsToast?.(data.data?.message ||
                        '<?php echo esc_js( __( 'Export failed.', 'vms-theme' ) ); ?>', 'error');
                }
            } catch (e) {
                window.vmsToast?.('<?php echo esc_js( __( 'An unexpected error occurred.', 'vms-theme' ) ); ?>',
                    'error');
            } finally {
                this.exporting = false;
            }
        },

        /* ── Pagination ──────────────────────────────────── */
        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
        },

        /* ── Helpers ─────────────────────────────────────── */
        formatPosition(pos) {
            const map = {
                gate: '<?php echo esc_js( __( 'Gate', 'vms-theme' ) ); ?>',
                reception: '<?php echo esc_js( __( 'Receptionist', 'vms-theme' ) ); ?>',
                general_manager: '<?php echo esc_js( __( 'General Manager', 'vms-theme' ) ); ?>'
            };
            return map[pos] || (pos || '-').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                return d.toLocaleDateString(undefined, {
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