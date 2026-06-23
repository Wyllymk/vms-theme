<?php
/* Template Name: VMS Guests */

/**
 * Guest management page -- list, register, edit, view, filter, export.
 *
 * Replaces both the old guests page and register-guest page. All guest
 * registration is now handled via modals (regular + courtesy).
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

vms_require_module( 'guests' );
vms_require_capability( 'vms_register_guests' );

get_header();

$role = function_exists( 'vms_current_role' ) ? vms_current_role() : '';
$can_courtesy = in_array( $role, array( 'administrator', 'chairman', 'general_manager', 'reception' ), true );
?>

<div class="vms-guests-page" x-data="guestsPage()" x-init="init()">

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card" style="margin-bottom:1.5rem;">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Guests', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php esc_html_e( 'Manage guest records, register new guests, and view visit history.', 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <?php if ( $can_courtesy ) : ?>
                <button class="vms-btn vms-btn-secondary" @click="openCourtesyModal()">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <?php esc_html_e( 'Courtesy Guest', 'vms-theme' ); ?>
                </button>
                <?php endif; ?>
                <button class="vms-btn vms-btn-primary" @click="openRegisterModal()">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?php esc_html_e( 'Register Guest', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ================================================================
	     STATUS FILTER TABS
	     ================================================================ -->
    <div class="vms-card" style="margin-bottom:1.5rem;padding:0.75rem 1rem;">
        <div class="vms-flex vms-items-center vms-gap-2" style="flex-wrap:wrap;">
            <button class="vms-btn vms-btn-sm"
                :class="statusFilter === '' ? 'vms-btn-primary' : 'vms-btn-secondary py-[0.615rem]! px-3.5!'"
                @click="statusFilter = ''; currentPage = 1; fetchGuests();">
                <?php esc_html_e( 'All', 'vms-theme' ); ?>
                <span class="vms-badge vms-badge-neutral" x-show="statusFilter === ''" x-text="totalRecords"
                    style="margin-left:0.25rem;"></span>
            </button>
            <button class="vms-btn vms-btn-sm"
                :class="statusFilter === 'active' ? 'vms-btn-success' : 'vms-btn-secondary py-[0.615rem]! px-3.5!'"
                @click="statusFilter = 'active'; currentPage = 1; fetchGuests();">
                <?php esc_html_e( 'Active', 'vms-theme' ); ?>
                <span class="vms-badge vms-badge-neutral" x-show="statusFilter === 'active'" x-text="totalRecords"
                    style="margin-left:0.25rem;"></span>
            </button>
            <button class="vms-btn vms-btn-sm"
                :class="statusFilter === 'suspended' ? 'vms-btn-warning' : 'vms-btn-secondary py-[0.615rem]! px-3.5!'"
                @click="statusFilter = 'suspended'; currentPage = 1; fetchGuests();"
                style="--btn-warn-bg:rgba(245,158,11,0.15);">
                <?php esc_html_e( 'Suspended', 'vms-theme' ); ?>
                <span class="vms-badge vms-badge-neutral" x-show="statusFilter === 'suspended'" x-text="totalRecords"
                    style="margin-left:0.25rem;"></span>
            </button>
            <button class="vms-btn vms-btn-sm"
                :class="statusFilter === 'banned' ? 'vms-btn-danger' : 'vms-btn-secondary py-[0.615rem]! px-3.5!'"
                @click="statusFilter = 'banned'; currentPage = 1; fetchGuests();">
                <?php esc_html_e( 'Banned', 'vms-theme' ); ?>
                <span class="vms-badge vms-badge-neutral" x-show="statusFilter === 'banned'" x-text="totalRecords"
                    style="margin-left:0.25rem;"></span>
            </button>

            <div style="flex:1;"></div>

            <!-- Export PDF -->
            <button class="vms-btn vms-btn-sm vms-btn-secondary" @click="exportPDF()" :disabled="exporting">
                <template x-if="exporting">
                    <span class="vms-spinner"></span>
                </template>
                <svg x-show="!exporting" style="width:1rem;height:1rem;" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <?php esc_html_e( 'Export PDF', 'vms-theme' ); ?>
            </button>
        </div>
    </div>

    <!-- ================================================================
	     SEARCH & PER-PAGE CONTROLS
	     ================================================================ -->
    <div class="vms-card" style="margin-bottom:1.5rem;padding:0.75rem 1rem;">
        <div class="vms-flex vms-items-center vms-gap-4" style="flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;position:relative;">
                <svg style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);width:1.25rem;height:1.25rem;color:var(--vms-text-muted);pointer-events:none;"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="search" class="vms-input" style="padding-left:2.5rem;" x-model="searchTerm"
                    @input.debounce.400ms="currentPage = 1; fetchGuests();"
                    placeholder="<?php esc_attr_e( 'Search by name, phone, or ID number...', 'vms-theme' ); ?>">
            </div>
            <div class="vms-flex vms-items-center vms-gap-2">
                <label class="vms-label" style="margin-bottom:0;white-space:nowrap;">
                    <?php esc_html_e( 'Per page:', 'vms-theme' ); ?>
                </label>
                <select class="vms-select" style="width:auto;min-width:80px;" x-model.number="perPage"
                    @change="currentPage = 1; fetchGuests();">
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ================================================================
	     GUEST TABLE
	     ================================================================ -->
    <div class="vms-card" style="padding:0;overflow:hidden;">

        <!-- Loading State -->
        <div x-show="loading" class="vms-loading-overlay" style="min-height:300px;">
            <span class="vms-spinner vms-spinner-lg"></span>
            <span class="vms-text-muted"><?php esc_html_e( 'Loading guests...', 'vms-theme' ); ?></span>
        </div>

        <!-- Table -->
        <div x-show="!loading && paginatedGuests.length > 0" style="overflow-x:auto;">
            <table class="vms-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'vms-theme' ); ?></th>
                        <th><?php esc_html_e( 'Phone', 'vms-theme' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'vms-theme' ); ?></th>
                        <th><?php esc_html_e( 'ID Number', 'vms-theme' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'vms-theme' ); ?></th>
                        <th><?php esc_html_e( 'Created', 'vms-theme' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'vms-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="guest in paginatedGuests" :key="guest.id">
                        <tr>
                            <td>
                                <div class="vms-flex vms-items-center vms-gap-2">
                                    <div style="width:2rem;height:2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;background:rgba(var(--vms-primary-rgb),0.1);color:var(--vms-primary);"
                                        x-text="(guest.first_name?.[0] || '') + (guest.last_name?.[0] || '')">
                                    </div>
                                    <span x-text="(guest.first_name || '') + ' ' + (guest.last_name || '')"></span>
                                </div>
                            </td>
                            <td x-text="guest.phone_number || '---'"></td>
                            <td x-text="guest.email || '---'"></td>
                            <td x-text="guest.id_number || '---'"></td>
                            <td>
                                <span class="vms-badge" :class="{
										'vms-badge-success': guest.guest_status === 'active',
										'vms-badge-warning': guest.guest_status === 'suspended',
										'vms-badge-danger':  guest.guest_status === 'banned'
									}" x-text="guest.guest_status || 'unknown'">
                                </span>
                            </td>
                            <td>
                                <span class="vms-text-sm vms-text-muted" x-text="formatDate(guest.created_at)"></span>
                            </td>
                            <td>
                                <div class="vms-flex vms-gap-2">
                                    <button class="vms-btn vms-btn-sm vms-btn-secondary" @click="openViewModal(guest)"
                                        title="<?php esc_attr_e( 'View', 'vms-theme' ); ?>">
                                        <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button class="vms-btn vms-btn-sm vms-btn-secondary" @click="openEditModal(guest)"
                                        title="<?php esc_attr_e( 'Edit', 'vms-theme' ); ?>">
                                        <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
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

        <!-- Empty State -->
        <div x-show="!loading && paginatedGuests.length === 0" class="vms-empty-state">
            <svg style="width:4rem;height:4rem;margin:0 auto 1rem;color:var(--vms-text-muted);" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="vms-text-lg vms-font-bold"><?php esc_html_e( 'No Guests Found', 'vms-theme' ); ?></p>
            <p class="vms-text-sm vms-text-muted"
                x-text="searchTerm ? '<?php echo esc_js( __( 'No guests match your search criteria.', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Start by registering your first guest.', 'vms-theme' ) ); ?>'">
            </p>
            <button x-show="!searchTerm" class="vms-btn vms-btn-primary" style="margin-top:1rem;"
                @click="openRegisterModal()">
                <svg class="w-3! h-3! m-0!" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?php esc_html_e( 'Register Guest', 'vms-theme' ); ?>
            </button>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && totalPages > 1" class="vms-pagination" style="padding:1rem;">
            <button :disabled="currentPage <= 1" @click="goToPage(1)">
                &laquo;
            </button>
            <button :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                &lsaquo;
            </button>

            <template x-for="p in pageNumbers" :key="'page-' + p">
                <button :class="{ 'active': p === currentPage }" @click="goToPage(p)" x-text="p">
                </button>
            </template>

            <button :disabled="currentPage >= totalPages" @click="goToPage(currentPage + 1)">
                &rsaquo;
            </button>
            <button :disabled="currentPage >= totalPages" @click="goToPage(totalPages)">
                &raquo;
            </button>

            <span class="vms-text-sm vms-text-muted" style="margin-left:0.75rem;"
                x-text="'<?php echo esc_js( __( 'Showing', 'vms-theme' ) ); ?> ' + ((currentPage - 1) * perPage + 1) + '-' + Math.min(currentPage * perPage, totalRecords) + ' <?php echo esc_js( __( 'of', 'vms-theme' ) ); ?> ' + totalRecords">
            </span>
        </div>
    </div>

    <!-- ================================================================
	     REGISTER GUEST MODAL
	     ================================================================ -->
    <div x-show="showRegisterModal" x-cloak class="vms-modal-overlay" @click.self="showRegisterModal = false">
        <div class="vms-modal vms-modal-lg">
            <!-- Header -->
            <div class="vms-flex vms-items-center vms-justify-between" style="margin-bottom:1.5rem;">
                <h3 class="vms-text-lg vms-font-bold"><?php esc_html_e( 'Register New Guest', 'vms-theme' ); ?></h3>
                <button @click="showRegisterModal = false" class="vms-btn vms-btn-sm vms-btn-secondary">&times;</button>
            </div>

            <!-- Search Existing -->
            <div class="vms-form-group">
                <label class="vms-label"><?php esc_html_e( 'Search Existing Guest', 'vms-theme' ); ?></label>
                <div style="position:relative;">
                    <svg style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);width:1.25rem;height:1.25rem;color:var(--vms-text-muted);pointer-events:none;"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" class="vms-input" style="padding-left:2.5rem;" x-model="registerSearchTerm"
                        @input.debounce.400ms="searchExistingGuest()"
                        placeholder="<?php esc_attr_e( 'Search by name, phone, or ID...', 'vms-theme' ); ?>">
                </div>

                <!-- Search results dropdown -->
                <div x-show="registerSearchResults.length > 0" x-cloak
                    style="margin-top:0.5rem;border:1px solid var(--vms-border);border-radius:0.75rem;overflow:hidden;max-height:200px;overflow-y:auto;">
                    <template x-for="result in registerSearchResults" :key="'rs-' + result.id">
                        <button type="button" @click="selectExistingGuest(result)"
                            style="display:flex;align-items:center;gap:0.75rem;width:100%;padding:0.625rem 0.75rem;text-align:left;border-bottom:1px solid var(--vms-border);transition:background 0.15s;cursor:pointer;background:transparent;border-left:0;border-right:0;border-top:0;color:var(--vms-text);font-size:0.875rem;"
                            onmouseover="this.style.background='rgba(var(--vms-primary-rgb),0.05)'"
                            onmouseout="this.style.background='transparent'">
                            <div style="width:2rem;height:2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;background:rgba(var(--vms-primary-rgb),0.1);color:var(--vms-primary);"
                                x-text="(result.first_name?.[0] || '') + (result.last_name?.[0] || '')">
                            </div>
                            <div>
                                <div x-text="result.first_name + ' ' + result.last_name" style="font-weight:600;"></div>
                                <div class="vms-text-sm vms-text-muted" x-text="result.phone_number"></div>
                            </div>
                            <span class="vms-badge" style="margin-left:auto;" :class="{
									'vms-badge-success': result.guest_status === 'active',
									'vms-badge-warning': result.guest_status === 'suspended',
									'vms-badge-danger':  result.guest_status === 'banned'
								}" x-text="result.guest_status">
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Selected guest indicator -->
            <div x-show="selectedExistingGuest" x-cloak
                style="padding:0.75rem 1rem;border-radius:0.75rem;background:rgba(var(--vms-primary-rgb),0.05);border:1px solid rgba(var(--vms-primary-rgb),0.2);margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
                <svg style="width:1rem;height:1rem;color:var(--vms-primary);" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="vms-text-sm" style="color:var(--vms-primary);font-weight:600;">
                    <?php esc_html_e( 'Existing guest selected', 'vms-theme' ); ?>
                </span>
                <button type="button" @click="clearSelectedGuest()" class="vms-btn vms-btn-sm vms-btn-secondary"
                    style="margin-left:auto;">
                    <?php esc_html_e( 'Clear', 'vms-theme' ); ?>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitRegisterGuest()">
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'First Name', 'vms-theme' ); ?> *</label>
                        <input type="text" class="vms-input" x-model="registerForm.first_name" required
                            :readonly="!!selectedExistingGuest">
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Last Name', 'vms-theme' ); ?> *</label>
                        <input type="text" class="vms-input" x-model="registerForm.last_name" required
                            :readonly="!!selectedExistingGuest">
                    </div>
                </div>
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Phone Number', 'vms-theme' ); ?> *</label>
                        <input type="tel" class="vms-input" x-model="registerForm.phone_number" required
                            :readonly="!!selectedExistingGuest"
                            placeholder="<?php esc_attr_e( '+254712345678', 'vms-theme' ); ?>">
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Email', 'vms-theme' ); ?></label>
                        <input type="email" class="vms-input" x-model="registerForm.email"
                            :readonly="!!selectedExistingGuest">
                    </div>
                </div>
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Host Member', 'vms-theme' ); ?> *</label>
                        <select class="vms-select" x-model="registerForm.host_member_id" required>
                            <option value=""><?php esc_html_e( '-- Select Host Member --', 'vms-theme' ); ?></option>
                            <template x-for="member in membersList" :key="'hm-' + member.id">
                                <option :value="member.id" x-text="member.display_name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Visit Date', 'vms-theme' ); ?> *</label>
                        <input type="date" class="vms-input" x-model="registerForm.visit_date" :min="today" required>
                    </div>
                </div>

                <div class="vms-form-group">
                    <label class="vms-label"><?php esc_html_e( 'Notes', 'vms-theme' ); ?></label>
                    <textarea class="vms-input" x-model="registerForm.notes" rows="2"
                        placeholder="<?php esc_attr_e( 'Optional notes about this guest...', 'vms-theme' ); ?>"
                        style="resize:vertical;"></textarea>
                </div>

                <!-- Error -->
                <div x-show="registerError" x-cloak
                    style="padding:0.75rem 1rem;border-radius:0.75rem;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);color:#dc2626;font-size:0.875rem;margin-bottom:1rem;"
                    x-text="registerError">
                </div>

                <div class="vms-flex vms-justify-between" style="margin-top:1.5rem;">
                    <button type="button" class="vms-btn vms-btn-secondary" @click="showRegisterModal = false">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button type="submit" class="vms-btn vms-btn-primary" :disabled="registerSaving">
                        <span x-show="registerSaving" class="vms-spinner"></span>
                        <span
                            x-text="registerSaving ? '<?php echo esc_js( __( 'Registering...', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Register Guest', 'vms-theme' ) ); ?>'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================
	     COURTESY GUEST MODAL
	     ================================================================ -->
    <?php if ( $can_courtesy ) : ?>
    <div x-show="showCourtesyModal" x-cloak class="vms-modal-overlay" @click.self="showCourtesyModal = false">
        <div class="vms-modal vms-modal-lg">
            <!-- Header -->
            <div class="vms-flex vms-items-center vms-justify-between" style="margin-bottom:1.5rem;">
                <h3 class="vms-text-lg vms-font-bold"><?php esc_html_e( 'Register Courtesy Guest', 'vms-theme' ); ?>
                </h3>
                <button @click="showCourtesyModal = false" class="vms-btn vms-btn-sm vms-btn-secondary">&times;</button>
            </div>

            <p class="vms-text-sm vms-text-muted" style="margin-bottom:1.25rem;">
                <?php esc_html_e( 'Courtesy guests do not require a host member. Their visit is recorded as courtesy.', 'vms-theme' ); ?>
            </p>

            <form @submit.prevent="submitCourtesyGuest()">
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'First Name', 'vms-theme' ); ?> *</label>
                        <input type="text" class="vms-input" x-model="courtesyForm.first_name" required>
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Last Name', 'vms-theme' ); ?> *</label>
                        <input type="text" class="vms-input" x-model="courtesyForm.last_name" required>
                    </div>
                </div>
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Phone Number', 'vms-theme' ); ?> *</label>
                        <input type="tel" class="vms-input" x-model="courtesyForm.phone_number" required
                            placeholder="<?php esc_attr_e( '+254712345678', 'vms-theme' ); ?>">
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Email', 'vms-theme' ); ?></label>
                        <input type="email" class="vms-input" x-model="courtesyForm.email">
                    </div>
                </div>
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Courtesy', 'vms-theme' ); ?></label>
                        <input type="text" class="vms-input"
                            value="<?php echo esc_attr( __( 'Courtesy', 'vms-theme' ) ); ?>" readonly>
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Visit Date', 'vms-theme' ); ?> *</label>
                        <input type="date" class="vms-input" x-model="courtesyForm.visit_date" :min="today">
                    </div>
                </div>
                <div class="vms-form-group">
                    <label class="vms-label"><?php esc_html_e( 'Notes', 'vms-theme' ); ?></label>
                    <textarea class="vms-input" x-model="courtesyForm.notes" rows="2"
                        placeholder="<?php esc_attr_e( 'Optional notes...', 'vms-theme' ); ?>"
                        style="resize:vertical;"></textarea>
                </div>

                <!-- Error -->
                <div x-show="courtesyError" x-cloak
                    style="padding:0.75rem 1rem;border-radius:0.75rem;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);color:#dc2626;font-size:0.875rem;margin-bottom:1rem;"
                    x-text="courtesyError">
                </div>

                <div class="vms-flex vms-justify-between" style="margin-top:1.5rem;">
                    <button type="button" class="vms-btn vms-btn-secondary" @click="showCourtesyModal = false">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button type="submit" class="vms-btn vms-btn-success" :disabled="courtesySaving">
                        <span x-show="courtesySaving" class="vms-spinner"></span>
                        <span
                            x-text="courtesySaving ? '<?php echo esc_js( __( 'Registering...', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Register Courtesy Guest', 'vms-theme' ) ); ?>'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================================================================
	     EDIT GUEST MODAL
	     ================================================================ -->
    <div x-show="showEditModal" x-cloak class="vms-modal-overlay" @click.self="showEditModal = false">
        <div class="vms-modal vms-modal-lg">
            <!-- Header -->
            <div class="vms-flex vms-items-center vms-justify-between" style="margin-bottom:1.5rem;">
                <h3 class="vms-text-lg vms-font-bold"><?php esc_html_e( 'Edit Guest', 'vms-theme' ); ?></h3>
                <button @click="showEditModal = false" class="vms-btn vms-btn-sm vms-btn-secondary">&times;</button>
            </div>

            <form @submit.prevent="submitEditGuest()">
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
                        <label class="vms-label"><?php esc_html_e( 'Phone Number', 'vms-theme' ); ?> *</label>
                        <input type="tel" class="vms-input" x-model="editForm.phone_number" required>
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Email', 'vms-theme' ); ?></label>
                        <input type="email" class="vms-input" x-model="editForm.email">
                    </div>
                </div>
                <div class="vms-grid vms-grid-2">
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'ID / Passport Number', 'vms-theme' ); ?></label>
                        <input type="text" class="vms-input" x-model="editForm.id_number">
                    </div>
                    <div class="vms-form-group">
                        <label class="vms-label"><?php esc_html_e( 'Status', 'vms-theme' ); ?></label>
                        <select class="vms-input" x-model="editForm.guest_status">
                            <option value="active"><?php esc_html_e( 'Active', 'vms-theme' ); ?></option>
                            <option value="suspended"><?php esc_html_e( 'Suspended', 'vms-theme' ); ?></option>
                            <option value="banned"><?php esc_html_e( 'Banned', 'vms-theme' ); ?></option>
                        </select>
                    </div>
                </div>
                <div class="vms-form-group">
                    <label class="vms-label"><?php esc_html_e( 'Notes', 'vms-theme' ); ?></label>
                    <textarea class="vms-input" x-model="editForm.notes" rows="2" style="resize:vertical;"></textarea>
                </div>

                <!-- Error -->
                <div x-show="editError" x-cloak
                    style="padding:0.75rem 1rem;border-radius:0.75rem;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);color:#dc2626;font-size:0.875rem;margin-bottom:1rem;"
                    x-text="editError">
                </div>

                <div class="vms-flex vms-justify-between" style="margin-top:1.5rem;">
                    <button type="button" class="vms-btn vms-btn-secondary" @click="showEditModal = false">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button type="submit" class="vms-btn vms-btn-primary" :disabled="editSaving">
                        <span x-show="editSaving" class="vms-spinner"></span>
                        <span
                            x-text="editSaving ? '<?php echo esc_js( __( 'Saving...', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Save Changes', 'vms-theme' ) ); ?>'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================
	     VIEW GUEST MODAL (Visit History)
	     ================================================================ -->
    <div x-show="showViewModal" x-cloak class="vms-modal-overlay" @click.self="showViewModal = false">
        <div class="vms-modal vms-modal-lg">
            <!-- Header -->
            <div class="vms-flex vms-items-center vms-justify-between" style="margin-bottom:1.5rem;">
                <div>
                    <h3 class="vms-text-lg vms-font-bold"
                        x-text="viewGuest ? (viewGuest.first_name + ' ' + viewGuest.last_name) : ''"></h3>
                    <p class="vms-text-sm vms-text-muted">
                        <?php esc_html_e( 'Guest Details & Visit History', 'vms-theme' ); ?></p>
                </div>
                <button @click="showViewModal = false" class="vms-btn vms-btn-sm vms-btn-secondary">&times;</button>
            </div>

            <!-- Guest Info Summary -->
            <div x-show="viewGuest"
                style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;padding:1rem;border-radius:0.75rem;background:var(--vms-glass-bg);border:1px solid var(--vms-border);">
                <div>
                    <span class="vms-text-sm vms-text-muted"
                        style="display:block;"><?php esc_html_e( 'Phone', 'vms-theme' ); ?></span>
                    <span class="vms-text-sm vms-font-bold" x-text="viewGuest?.phone_number || '---'"></span>
                </div>
                <div>
                    <span class="vms-text-sm vms-text-muted"
                        style="display:block;"><?php esc_html_e( 'Email', 'vms-theme' ); ?></span>
                    <span class="vms-text-sm vms-font-bold" x-text="viewGuest?.email || '---'"></span>
                </div>
                <div>
                    <span class="vms-text-sm vms-text-muted"
                        style="display:block;"><?php esc_html_e( 'ID Number', 'vms-theme' ); ?></span>
                    <span class="vms-text-sm vms-font-bold" x-text="viewGuest?.id_number || '---'"></span>
                </div>
                <div>
                    <span class="vms-text-sm vms-text-muted"
                        style="display:block;"><?php esc_html_e( 'Status', 'vms-theme' ); ?></span>
                    <span class="vms-badge" :class="{
							'vms-badge-success': viewGuest?.guest_status === 'active',
							'vms-badge-warning': viewGuest?.guest_status === 'suspended',
							'vms-badge-danger':  viewGuest?.guest_status === 'banned'
						}" x-text="viewGuest?.guest_status || '---'">
                    </span>
                </div>
                <div x-show="viewGuest?.notes">
                    <span class="vms-text-sm vms-text-muted"
                        style="display:block;"><?php esc_html_e( 'Notes', 'vms-theme' ); ?></span>
                    <span class="vms-text-sm" x-text="viewGuest?.notes"></span>
                </div>
            </div>

            <!-- Visit History -->
            <h4 class="vms-font-bold" style="margin-bottom:0.75rem;">
                <?php esc_html_e( 'Visit History', 'vms-theme' ); ?></h4>

            <!-- Loading visits -->
            <div x-show="viewLoading" class="vms-loading-overlay" style="min-height:100px;">
                <span class="vms-spinner"></span>
                <span
                    class="vms-text-sm vms-text-muted"><?php esc_html_e( 'Loading visit history...', 'vms-theme' ); ?></span>
            </div>

            <!-- Visits table -->
            <div x-show="!viewLoading && viewVisits.length > 0" style="overflow-x:auto;">
                <table class="vms-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Visit Date', 'vms-theme' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'vms-theme' ); ?></th>
                            <th><?php esc_html_e( 'Sign In', 'vms-theme' ); ?></th>
                            <th><?php esc_html_e( 'Sign Out', 'vms-theme' ); ?></th>
                            <th><?php esc_html_e( 'Courtesy', 'vms-theme' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="visit in viewVisits" :key="'v-' + visit.id">
                            <tr>
                                <td x-text="formatDate(visit.visit_date)"></td>
                                <td>
                                    <span class="vms-badge" :class="{
											'vms-badge-success': visit.status === 'signed_in' || visit.status === 'signed_out' || visit.status === 'approved',
											'vms-badge-warning': visit.status === 'unapproved' || visit.status === 'pending',
											'vms-badge-danger':  visit.status === 'cancelled' || visit.status === 'denied',
											'vms-badge-info':    visit.status === 'completed'
										}" x-text="(visit.status || '').replace(/_/g, ' ')">
                                    </span>
                                </td>
                                <td class="vms-text-sm"
                                    x-text="visit.sign_in_time ? formatTime(visit.sign_in_time) : '---'"></td>
                                <td class="vms-text-sm"
                                    x-text="visit.sign_out_time ? formatTime(visit.sign_out_time) : '---'"></td>
                                <td class="vms-text-sm" x-text="visit.courtesy || '---'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- No visits -->
            <div x-show="!viewLoading && viewVisits.length === 0" class="vms-empty-state" style="padding:2rem 1rem;">
                <p class="vms-text-sm vms-text-muted">
                    <?php esc_html_e( 'No visit history found for this guest.', 'vms-theme' ); ?></p>
            </div>

            <div class="vms-flex vms-justify-between" style="margin-top:1.5rem;">
                <button type="button" class="vms-btn vms-btn-secondary" @click="showViewModal = false">
                    <?php esc_html_e( 'Close', 'vms-theme' ); ?>
                </button>
                <button type="button" class="vms-btn vms-btn-primary"
                    @click="showViewModal = false; openEditModal(viewGuest);">
                    <?php esc_html_e( 'Edit Guest', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>

</div>

<!-- ====================================================================
     JAVASCRIPT
     ==================================================================== -->
<script>
function guestsPage() {
    return {
        /* ── List state ─────────────────────────────────────────── */
        allGuests: [],
        loading: true,
        searchTerm: '',
        statusFilter: '',
        exporting: false,

        /* ── Pagination ─────────────────────────────────────────── */
        perPage: 20,
        currentPage: 1,
        totalPages: 0,
        totalRecords: 0,

        /* ── Members list (for host dropdown) ───────────────────── */
        membersList: [],

        /* ── Register modal ─────────────────────────────────────── */
        showRegisterModal: false,
        registerSearchTerm: '',
        registerSearchResults: [],
        selectedExistingGuest: null,
        registerForm: {},
        registerError: '',
        registerSaving: false,

        /* ── Courtesy modal ─────────────────────────────────────── */
        showCourtesyModal: false,
        courtesyForm: {},
        courtesyError: '',
        courtesySaving: false,

        /* ── Edit modal ─────────────────────────────────────────── */
        showEditModal: false,
        editingGuest: null,
        editForm: {},
        editError: '',
        editSaving: false,

        /* ── View modal ─────────────────────────────────────────── */
        showViewModal: false,
        viewGuest: null,
        viewVisits: [],
        viewLoading: false,

        /* ── Helpers ─────────────────────────────────────────────── */
        today: new Date().toISOString().split('T')[0],

        /* ── Computed: filtered + paginated guests ──────────────── */
        get filteredGuests() {
            let results = this.allGuests;

            if (this.statusFilter) {
                results = results.filter(g => g.guest_status === this.statusFilter);
                console.log(`[filteredGuests] Status filter "${this.statusFilter}" → ${results.length} guests`);
            }

            if (this.searchTerm && this.searchTerm.length >= 2) {
                const term = this.searchTerm.toLowerCase();
                results = results.filter(g => {
                    const fullName = ((g.first_name || '') + ' ' + (g.last_name || '')).toLowerCase();
                    const phone = (g.phone_number || '').toLowerCase();
                    const idNum = (g.id_number || '').toLowerCase();
                    return fullName.includes(term) || phone.includes(term) || idNum.includes(term);
                });
                console.log(`[filteredGuests] Search "${this.searchTerm}" → ${results.length} matches`);
            }

            return results;
        },

        get paginatedGuests() {
            const filtered = this.filteredGuests;
            this.totalRecords = filtered.length;
            this.totalPages = Math.max(1, Math.ceil(filtered.length / this.perPage));

            if (this.currentPage > this.totalPages) {
                console.warn(
                    `[paginatedGuests] currentPage (${this.currentPage}) exceeds totalPages (${this.totalPages}), resetting to ${this.totalPages}`
                );
                this.currentPage = this.totalPages;
            }

            const start = (this.currentPage - 1) * this.perPage;
            const page = filtered.slice(start, start + this.perPage);
            console.log(
                `[paginatedGuests] Page ${this.currentPage}/${this.totalPages} — showing records ${start + 1}–${start + page.length} of ${this.totalRecords}`
            );
            return page;
        },

        get pageNumbers() {
            const pages = [];
            const total = this.totalPages;
            const current = this.currentPage;
            const maxVisible = 7;

            if (total <= maxVisible) {
                for (let i = 1; i <= total; i++) pages.push(i);
            } else {
                pages.push(1);
                let start = Math.max(2, current - 2);
                let end = Math.min(total - 1, current + 2);

                if (current <= 3) {
                    end = Math.min(total - 1, 5);
                }
                if (current >= total - 2) {
                    start = Math.max(2, total - 4);
                }

                for (let i = start; i <= end; i++) pages.push(i);
                if (pages[pages.length - 1] < total) pages.push(total);
            }

            console.log('[pageNumbers] Visible pages:', pages, `(current: ${current}, total: ${total})`);
            return pages;
        },

        /* ── Init ───────────────────────────────────────────────── */
        init() {
            console.log('[init] guestsPage initialising');
            this.resetRegisterForm();
            this.resetCourtesyForm();
            this.fetchGuests();
            this.fetchMembers();
            console.log('[init] today:', this.today);
        },

        /* ── Fetch all guests ───────────────────────────────────── */
        async fetchGuests() {
            console.log('[fetchGuests] Starting — searchTerm:', this.searchTerm || '(none)');
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('action', 'vms_search_guests');
                formData.append('nonce', vmsTheme.nonces.guest);
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                const searchTerm = (this.searchTerm || '').trim();
                if (searchTerm.length >= 2) {
                    formData.append('term', searchTerm);
                    console.log('[fetchGuests] Mode: search term =', searchTerm);
                } else {
                    formData.append('list_all', '1');
                    console.log('[fetchGuests] Mode: list_all');
                }

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                const data = await response.json();
                console.log('[fetchGuests] Raw response:', data);

                if (data.success) {
                    this.allGuests = data.data.results || [];
                    console.log(`[fetchGuests] Loaded ${this.allGuests.length} guests`);
                } else {
                    console.warn('[fetchGuests] Server returned failure:', data.data);
                    this.allGuests = [];
                    this.showToast(data.data?.message || vmsTheme.i18n.error, 'error');
                }
            } catch (e) {
                console.error('[fetchGuests] Exception:', e);
                this.allGuests = [];
                this.showToast(vmsTheme.i18n.error, 'error');
            } finally {
                this.loading = false;
                console.log('[fetchGuests] Done');
            }
        },

        /* ── Fetch members list (for host dropdown) ─────────────── */
        async fetchMembers() {
            console.log('[fetchMembers] Fetching members list');
            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_members_list');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('per_page', 500);
                formData.append('page', 1);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                const data = await response.json();
                console.log('[fetchMembers] Raw response:', data);

                if (data.success) {
                    this.membersList = data.data.rows || data.data.members || data.data || [];
                    console.log(`[fetchMembers] Loaded ${this.membersList.length} members`);
                } else {
                    console.warn('[fetchMembers] Server returned failure:', data.data);
                }
            } catch (e) {
                console.error('[fetchMembers] Exception:', e);
            }
        },

        /* ── Pagination ─────────────────────────────────────────── */
        goToPage(page) {
            console.log(`[goToPage] Requested page ${page} (totalPages: ${this.totalPages})`);
            if (page < 1 || page > this.totalPages) {
                console.warn(`[goToPage] Page ${page} out of range, ignoring`);
                return;
            }
            this.currentPage = page;
            console.log(`[goToPage] currentPage set to ${this.currentPage}`);
        },

        /* ── Register Guest Modal ───────────────────────────────── */
        resetRegisterForm() {
            this.registerForm = {
                first_name: '',
                last_name: '',
                phone_number: '',
                email: '',
                notes: '',
                visit_date: this.today,
                host_member_id: ''
            };
            this.registerError = '';
            this.registerSearchTerm = '';
            this.registerSearchResults = [];
            this.selectedExistingGuest = null;
            console.log('[resetRegisterForm] Form reset');
        },

        openRegisterModal() {
            console.log('[openRegisterModal] Opening register modal');
            this.resetRegisterForm();
            this.showRegisterModal = true;
        },

        async searchExistingGuest() {
            if (this.registerSearchTerm.length < 2) {
                this.registerSearchResults = [];
                console.log('[searchExistingGuest] Term too short, cleared results');
                return;
            }
            console.log('[searchExistingGuest] Searching for:', this.registerSearchTerm);
            try {
                const formData = new FormData();
                formData.append('action', 'vms_search_guests');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('term', this.registerSearchTerm);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                const data = await response.json();
                console.log('[searchExistingGuest] Raw response:', data);

                this.registerSearchResults = data.success ? (data.data.results || []) : [];
                console.log(`[searchExistingGuest] ${this.registerSearchResults.length} result(s) found`);
            } catch (e) {
                console.error('[searchExistingGuest] Exception:', e);
                this.registerSearchResults = [];
            }
        },

        selectExistingGuest(guest) {
            console.log('[selectExistingGuest] Selected guest:', guest);
            this.selectedExistingGuest = guest;
            this.registerForm.first_name = guest.first_name;
            this.registerForm.last_name = guest.last_name;
            this.registerForm.phone_number = guest.phone_number;
            this.registerForm.email = guest.email || '';
            this.registerSearchResults = [];
            this.registerSearchTerm = '';
        },

        clearSelectedGuest() {
            console.log('[clearSelectedGuest] Clearing selected guest');
            this.selectedExistingGuest = null;
            this.registerForm.first_name = '';
            this.registerForm.last_name = '';
            this.registerForm.phone_number = '';
            this.registerForm.email = '';
        },

        async submitRegisterGuest() {
            console.log('[submitRegisterGuest] Submitting — form:', this.registerForm, '| selectedExistingGuest:',
                this.selectedExistingGuest);
            this.registerError = '';
            this.registerSaving = true;

            try {
                let guestId = this.selectedExistingGuest?.id;
                console.log('[submitRegisterGuest] Initial guestId:', guestId || '(none — will create new)');

                /* Step 1: Create guest if new */
                if (!guestId) {
                    console.log('[submitRegisterGuest] Step 1: Creating new guest');
                    const guestData = new FormData();
                    guestData.append('action', 'vms_register_guest');
                    guestData.append('nonce', vmsTheme.nonces.guest);
                    guestData.append('_ajax_nonce', vmsTheme.nonces.guest);
                    guestData.append('first_name', this.registerForm.first_name);
                    guestData.append('last_name', this.registerForm.last_name);
                    guestData.append('phone_number', this.registerForm.phone_number);
                    guestData.append('email', this.registerForm.email);
                    guestData.append('notes', this.registerForm.notes);

                    const guestRes = await fetch(vmsTheme.ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: guestData
                    });
                    const guestJson = await guestRes.json();
                    console.log('[submitRegisterGuest] Step 1 response:', guestJson);

                    if (!guestJson.success) {
                        /* If duplicate, extract existing ID and continue */
                        if (guestJson.data?.code === 'duplicate_phone' && guestJson.data?.data?.existing_id) {
                            guestId = guestJson.data.data.existing_id;
                            console.warn('[submitRegisterGuest] Duplicate phone — reusing existing guest ID:',
                                guestId);
                        } else {
                            console.error('[submitRegisterGuest] Step 1 failed:', guestJson.data);
                            this.registerError = guestJson.data?.message || vmsTheme.i18n.error;
                            return;
                        }
                    } else {
                        guestId = guestJson.data.guest.id;
                        console.log('[submitRegisterGuest] New guest created with ID:', guestId);
                    }
                }

                /* Step 2: Register visit if date provided or host selected */
                if (this.registerForm.visit_date || this.registerForm.host_member_id) {
                    console.log('[submitRegisterGuest] Step 2: Registering visit for guest ID:', guestId);
                    const visitData = new FormData();
                    visitData.append('action', 'vms_register_visit');
                    visitData.append('_ajax_nonce', vmsTheme.nonces.guest);
                    visitData.append('guest_id', guestId);
                    visitData.append('visit_date', this.registerForm.visit_date || this.today);
                    visitData.append('host_id', this.registerForm.host_member_id || vmsTheme.currentUser.id);

                    const visitRes = await fetch(vmsTheme.ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: visitData
                    });
                    const visitJson = await visitRes.json();
                    console.log('[submitRegisterGuest] Step 2 response:', visitJson);

                    if (!visitJson.success) {
                        console.error('[submitRegisterGuest] Step 2 failed:', visitJson.data);
                        this.registerError = visitJson.data?.message || vmsTheme.i18n.error;
                        return;
                    }
                } else {
                    console.log('[submitRegisterGuest] Step 2 skipped — no visit_date or host_member_id');
                }

                console.log('[submitRegisterGuest] Complete — closing modal and refreshing');
                this.showRegisterModal = false;
                this.showToast('<?php echo esc_js( __( 'Guest registered successfully.', 'vms-theme' ) ); ?>',
                    'success');
                this.fetchGuests();
            } catch (e) {
                console.error('[submitRegisterGuest] Exception:', e);
                this.registerError = vmsTheme.i18n.error;
            } finally {
                this.registerSaving = false;
                console.log('[submitRegisterGuest] Done');
            }
        },

        /* ── Courtesy Guest Modal ───────────────────────────────── */
        resetCourtesyForm() {
            this.courtesyForm = {
                first_name: '',
                last_name: '',
                phone_number: '',
                email: '',
                notes: '',
                visit_date: this.today,
                courtesy: '<?php echo esc_js( __( 'Courtesy', 'vms-theme' ) ); ?>'
            };
            this.courtesyError = '';
            console.log('[resetCourtesyForm] Form reset');
        },

        openCourtesyModal() {
            console.log('[openCourtesyModal] Opening courtesy modal');
            this.resetCourtesyForm();
            this.showCourtesyModal = true;
        },

        async submitCourtesyGuest() {
            console.log('[submitCourtesyGuest] Submitting — form:', this.courtesyForm);
            this.courtesyError = '';
            this.courtesySaving = true;

            try {
                /* Step 1: Register the guest */
                console.log('[submitCourtesyGuest] Step 1: Registering guest');
                const guestData = new FormData();
                guestData.append('action', 'vms_register_guest');
                guestData.append('nonce', vmsTheme.nonces.guest);
                guestData.append('_ajax_nonce', vmsTheme.nonces.guest);
                guestData.append('first_name', this.courtesyForm.first_name);
                guestData.append('last_name', this.courtesyForm.last_name);
                guestData.append('phone_number', this.courtesyForm.phone_number);
                guestData.append('email', this.courtesyForm.email);
                guestData.append('notes', this.courtesyForm.notes);

                const guestRes = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: guestData
                });
                const guestJson = await guestRes.json();
                console.log('[submitCourtesyGuest] Step 1 response:', guestJson);

                let guestId;
                if (!guestJson.success) {
                    /* If duplicate, try to extract existing ID */
                    if (guestJson.data?.code === 'duplicate_phone' && guestJson.data?.data?.existing_id) {
                        guestId = guestJson.data.data.existing_id;
                        console.warn('[submitCourtesyGuest] Duplicate phone — reusing existing guest ID:', guestId);
                    } else {
                        console.error('[submitCourtesyGuest] Step 1 failed:', guestJson.data);
                        this.courtesyError = guestJson.data?.message || vmsTheme.i18n.error;
                        return;
                    }
                } else {
                    guestId = guestJson.data.guest.id;
                    console.log('[submitCourtesyGuest] Guest created with ID:', guestId);
                }

                /* Step 2: Register courtesy visit */
                console.log('[submitCourtesyGuest] Step 2: Registering courtesy visit for guest ID:', guestId);
                const visitData = new FormData();
                visitData.append('action', 'vms_register_visit');
                visitData.append('_ajax_nonce', vmsTheme.nonces.guest);
                visitData.append('guest_id', guestId);
                visitData.append('visit_date', this.courtesyForm.visit_date || this.today);
                visitData.append('courtesy', this.courtesyForm.courtesy);

                const visitRes = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: visitData
                });
                const visitJson = await visitRes.json();
                console.log('[submitCourtesyGuest] Step 2 response:', visitJson);

                if (!visitJson.success) {
                    console.error('[submitCourtesyGuest] Step 2 failed:', visitJson.data);
                    this.courtesyError = visitJson.data?.message || vmsTheme.i18n.error;
                    return;
                }

                console.log('[submitCourtesyGuest] Complete — closing modal and refreshing');
                this.showCourtesyModal = false;
                this.showToast(
                    '<?php echo esc_js( __( 'Courtesy guest registered successfully.', 'vms-theme' ) ); ?>',
                    'success');
                this.fetchGuests();
            } catch (e) {
                console.error('[submitCourtesyGuest] Exception:', e);
                this.courtesyError = vmsTheme.i18n.error;
            } finally {
                this.courtesySaving = false;
                console.log('[submitCourtesyGuest] Done');
            }
        },

        /* ── Edit Guest Modal ───────────────────────────────────── */
        openEditModal(guest) {
            console.log('[openEditModal] Opening edit modal for guest:', guest);
            this.editingGuest = guest;
            this.editForm = {
                first_name: guest.first_name || '',
                last_name: guest.last_name || '',
                phone_number: guest.phone_number || '',
                email: guest.email || '',
                id_number: guest.id_number || '',
                notes: guest.notes || '',
                guest_status: guest.guest_status || 'active'
            };
            this.editError = '';
            this.showEditModal = true;
            console.log('[openEditModal] editForm populated:', this.editForm);
        },

        async submitEditGuest() {
            console.log('[submitEditGuest] Submitting edit for guest ID:', this.editingGuest?.id, '| form:', this
                .editForm);
            this.editError = '';
            this.editSaving = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_update_guest');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('guest_id', this.editingGuest.id);
                formData.append('first_name', this.editForm.first_name);
                formData.append('last_name', this.editForm.last_name);
                formData.append('phone_number', this.editForm.phone_number);
                formData.append('email', this.editForm.email);
                formData.append('id_number', this.editForm.id_number);
                formData.append('notes', this.editForm.notes);
                formData.append('guest_status', this.editForm.guest_status);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                const data = await response.json();
                console.log('[submitEditGuest] Raw response:', data);

                if (data.success) {
                    console.log('[submitEditGuest] Update successful — closing modal and refreshing');
                    this.showEditModal = false;
                    this.showToast(data.data.message ||
                        '<?php echo esc_js( __( 'Guest updated.', 'vms-theme' ) ); ?>', 'success');
                    this.fetchGuests();
                } else {
                    console.warn('[submitEditGuest] Update failed:', data.data);
                    this.editError = data.data?.message || vmsTheme.i18n.error;
                }
            } catch (e) {
                console.error('[submitEditGuest] Exception:', e);
                this.editError = vmsTheme.i18n.error;
            } finally {
                this.editSaving = false;
                console.log('[submitEditGuest] Done');
            }
        },

        /* ── View Guest Modal (Visit History) ───────────────────── */
        async openViewModal(guest) {
            console.log('[openViewModal] Opening view modal for guest:', guest);
            this.viewGuest = guest;
            this.viewVisits = [];
            this.viewLoading = true;
            this.showViewModal = true;

            try {
                /* Fetch full guest data with usage */
                const guestData = new FormData();
                guestData.append('action', 'vms_get_guest');
                guestData.append('_ajax_nonce', vmsTheme.nonces.guest);
                guestData.append('guest_id', guest.id);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: guestData
                });
                const data = await response.json();
                console.log('[openViewModal] Raw response:', data);

                if (data.success) {
                    /* Update guest data with full record */
                    if (data.data.guest) {
                        this.viewGuest = data.data.guest;
                        console.log('[openViewModal] viewGuest updated:', this.viewGuest);
                    }
                    /* usage.visits is the visit history */
                    if (data.data.usage && data.data.usage.visits) {
                        this.viewVisits = data.data.usage.visits;
                        console.log(`[openViewModal] Loaded ${this.viewVisits.length} visits from usage.visits`);
                    } else if (data.data.usage && Array.isArray(data.data.usage)) {
                        this.viewVisits = data.data.usage;
                        console.log(`[openViewModal] Loaded ${this.viewVisits.length} visits from usage (array)`);
                    } else {
                        console.warn('[openViewModal] No visits found in response — usage:', data.data.usage);
                    }
                } else {
                    console.warn('[openViewModal] Server returned failure:', data.data);
                    this.showToast(data.data?.message || vmsTheme.i18n.error, 'error');
                }
            } catch (e) {
                console.error('[openViewModal] Exception:', e);
                this.showToast(vmsTheme.i18n.error, 'error');
            } finally {
                this.viewLoading = false;
                console.log('[openViewModal] Done — viewVisits count:', this.viewVisits.length);
            }
        },

        /* ── Export PDF ──────────────────────────────────────────── */
        async exportPDF() {
            console.log('[exportPDF] Starting export — statusFilter:', this.statusFilter || '(none)',
                '| searchTerm:', this.searchTerm || '(none)');
            this.exporting = true;
            try {
                const formData = new FormData();
                formData.append('action', 'vms_export_guests_pdf');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                if (this.statusFilter) {
                    formData.append('status', this.statusFilter);
                }
                if (this.searchTerm) {
                    formData.append('search', this.searchTerm);
                }

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                /* Check if response is a file download (binary) */
                const contentType = response.headers.get('content-type') || '';
                console.log('[exportPDF] Response content-type:', contentType);

                if (contentType.includes('application/pdf') || contentType.includes('application/octet-stream')) {
                    console.log('[exportPDF] Binary PDF response — triggering download');
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'guests-export.pdf';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                    this.showToast('<?php echo esc_js( __( 'PDF exported successfully.', 'vms-theme' ) ); ?>',
                        'success');
                } else {
                    /* JSON response — may contain fallback HTML or an error */
                    const data = await response.json();
                    console.log('[exportPDF] JSON response:', data);

                    if (data.success && data.data?.html) {
                        console.log('[exportPDF] Opening HTML fallback in new window');
                        const win = window.open();
                        if (win) {
                            win.document.write(data.data.html);
                            win.document.close();
                        } else {
                            console.warn('[exportPDF] window.open() was blocked');
                        }
                        this.showToast('<?php echo esc_js( __( 'PDF exported successfully.', 'vms-theme' ) ); ?>',
                            'success');
                    } else if (data.success && data.data?.url) {
                        console.log('[exportPDF] Opening PDF URL:', data.data.url);
                        window.open(data.data.url, '_blank');
                        this.showToast('<?php echo esc_js( __( 'PDF exported successfully.', 'vms-theme' ) ); ?>',
                            'success');
                    } else {
                        console.error('[exportPDF] Export failed:', data.data);
                        this.showToast(data.data?.message ||
                            '<?php echo esc_js( __( 'Export failed.', 'vms-theme' ) ); ?>', 'error');
                    }
                }
            } catch (e) {
                console.error('[exportPDF] Exception:', e);
                this.showToast(vmsTheme.i18n.error, 'error');
            } finally {
                this.exporting = false;
                console.log('[exportPDF] Done');
            }
        },

        /* ── Utilities ──────────────────────────────────────────── */
        showToast(message, type) {
            console.log(`[showToast] type="${type}" message="${message}"`);
            if (typeof window.vmsToast === 'function') {
                window.vmsToast(message, type);
            } else {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        type: type,
                        message: message
                    }
                }));
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '---';
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) {
                    console.warn('[formatDate] Invalid date string:', dateStr);
                    return dateStr;
                }
                return d.toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (e) {
                console.error('[formatDate] Exception for input:', dateStr, e);
                return dateStr;
            }
        },

        formatTime(timeStr) {
            if (!timeStr) return '---';
            try {
                /* timeStr may be "HH:MM:SS" or full datetime */
                if (timeStr.includes('T') || timeStr.includes(' ')) {
                    const d = new Date(timeStr);
                    if (!isNaN(d.getTime())) {
                        return d.toLocaleTimeString(undefined, {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                }
                /* Assume HH:MM:SS format */
                const parts = timeStr.split(':');
                if (parts.length >= 2) {
                    const h = parseInt(parts[0], 10);
                    const m = parts[1];
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const h12 = h % 12 || 12;
                    return h12 + ':' + m + ' ' + ampm;
                }
                console.warn('[formatTime] Unrecognised time format:', timeStr);
                return timeStr;
            } catch (e) {
                console.error('[formatTime] Exception for input:', timeStr, e);
                return timeStr;
            }
        }
    };
}
</script>

<?php
get_footer();