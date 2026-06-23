<?php
/* Template Name: VMS Accommodation */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/' ) ); exit; }
vms_require_module( 'accommodation' );
vms_require_capability( 'vms_manage_accommodation' );
get_header();
?>
<div class="flex flex-col gap-6" x-data="accommodationPage()" x-init="init()">

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Accommodation', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php esc_html_e( 'Manage rooms, and accommodation bookings.', 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <button @click="openAddModal()" class="vms-btn vms-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?php esc_html_e( 'Add Booking', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-2">
        <template x-for="tab in tabs" :key="tab.value">
            <button @click="currentTab = tab.value; currentPage = 1"
                class="px-4 py-2 text-sm font-medium rounded-xl whitespace-nowrap transition-colors"
                :class="currentTab === tab.value ? 'bg-[var(--vms-primary)] text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
                x-text="tab.label"></button>
        </template>
    </div>

    <div x-show="loading" class="vms-card text-center">
        <svg class="animate-spin h-8 w-8 mx-auto text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    <div x-show="!loading && filteredBookings.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="b in paginatedBookings" :key="b.id">
            <div class="vms-card">
                <div class="flex items-start justify-between mb-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full" :class="{
							'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': b.visit_status === 'checked_in',
							'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': b.visit_status === 'pending',
							'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': b.visit_status === 'confirmed',
							'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': b.visit_status === 'checked_out'
						}" x-text="b.visit_status || '<?php echo esc_js( __( 'pending', 'vms-theme' ) ); ?>'"></span>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white"
                    x-text="(b.first_name || '') + ' ' + (b.last_name || '')"></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                    x-text="b.room_number ? '<?php echo esc_js( __( 'Room', 'vms-theme' ) ); ?>: ' + b.room_number : '<?php echo esc_js( __( 'No room assigned', 'vms-theme' ) ); ?>'">
                </p>
                <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-text="b.check_in_date + (b.check_out_date ? ' → ' + b.check_out_date : '')"></span>
                </div>
                <div class="flex gap-2 mt-4">
                    <template x-if="b.visit_status === 'confirmed' || b.visit_status === 'pending'">
                        <button @click="handleCheckin(b)"
                            class="flex-1 px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"><?php esc_html_e( 'Check In', 'vms-theme' ); ?></button>
                    </template>
                    <template x-if="b.visit_status === 'checked_in'">
                        <button @click="handleCheckout(b)"
                            class="flex-1 px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"><?php esc_html_e( 'Check Out', 'vms-theme' ); ?></button>
                    </template>
                    <button @click="openEditModal(b)"
                        class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                        title="<?php esc_html_e( 'Edit Guest', 'vms-theme' ); ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="!loading && filteredBookings.length === 0" class="vms-card text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            <?php esc_html_e( 'No Bookings', 'vms-theme' ); ?></h3>
        <p class="text-gray-500 dark:text-gray-400">
            <?php esc_html_e( 'No bookings found for the selected filter. Click "Add Booking" to get started.', 'vms-theme' ); ?>
        </p>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && totalPages > 1" class="flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <?php esc_html_e( 'Page', 'vms-theme' ); ?> <span class="font-medium" x-text="currentPage"></span>
            <?php esc_html_e( 'of', 'vms-theme' ); ?> <span class="font-medium" x-text="totalPages"></span>
        </p>
        <div class="flex gap-2">
            <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage <= 1"
                class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700">&lsaquo;</button>
            <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage >= totalPages"
                class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700">&rsaquo;</button>
        </div>
    </div>

    <!-- Add Booking Modal -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div @click="showAddModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto"
            @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php esc_html_e( 'New Accommodation Booking', 'vms-theme' ); ?></h3>
                <button @click="showAddModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form @submit.prevent="saveBooking" class="p-6 flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'First Name', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.first_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Last Name', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.last_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Phone', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="tel" x-model="form.phone_number" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Email', 'vms-theme' ); ?></label>
                        <input type="email" x-model="form.email"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'ID / Passport Number', 'vms-theme' ); ?></label>
                    <input type="text" x-model="form.id_number"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                </div>
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Room / Cottage Number', 'vms-theme' ); ?></label>
                    <input type="text" x-model="form.room_number"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Check-In Date', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="date" x-model="form.check_in_date" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Check-Out Date', 'vms-theme' ); ?></label>
                        <input type="date" x-model="form.check_out_date"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div x-show="formError" x-cloak
                    class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
                    x-text="formError"></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAddModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"><?php esc_html_e( 'Cancel', 'vms-theme' ); ?></button>
                    <button type="submit" :disabled="saving"
                        class="px-4 py-2 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="saving" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <?php esc_html_e( 'Save Booking', 'vms-theme' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Guest Modal -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div @click="showEditModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto"
            @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?php esc_html_e( 'Edit Accommodation Guest', 'vms-theme' ); ?></h3>
                <button @click="showEditModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form @submit.prevent="submitEditGuest" class="p-6 flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'First Name', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="text" x-model="editForm.first_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Last Name', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="text" x-model="editForm.last_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Phone', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="tel" x-model="editForm.phone_number" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Email', 'vms-theme' ); ?></label>
                        <input type="email" x-model="editForm.email"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'ID / Passport Number', 'vms-theme' ); ?></label>
                        <input type="text" x-model="editForm.id_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Status', 'vms-theme' ); ?></label>
                        <select x-model="editForm.guest_status"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)]">
                            <option value="active"><?php esc_html_e( 'Active', 'vms-theme' ); ?></option>
                            <option value="suspended"><?php esc_html_e( 'Suspended', 'vms-theme' ); ?></option>
                            <option value="banned"><?php esc_html_e( 'Banned', 'vms-theme' ); ?></option>
                        </select>
                    </div>
                </div>
                <div x-show="editError" x-cloak
                    class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
                    x-text="editError"></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showEditModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"><?php esc_html_e( 'Cancel', 'vms-theme' ); ?></button>
                    <button type="submit" :disabled="editSaving"
                        class="px-4 py-2 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="editSaving" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
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
</div>

<script>
function accommodationPage() {
    return {
        bookings: [],
        loading: true,
        saving: false,
        showAddModal: false,
        currentTab: 'all',
        currentPage: 1,
        perPage: 12,
        formError: '',
        form: {
            first_name: '',
            last_name: '',
            phone_number: '',
            email: '',
            id_number: '',
            room_number: '',
            check_in_date: '',
            check_out_date: ''
        },
        showEditModal: false,
        editingGuest: null,
        editForm: {},
        editError: '',
        editSaving: false,

        tabs: [{
                value: 'all',
                label: '<?php echo esc_js( __( 'All', 'vms-theme' ) ); ?>'
            },
            {
                value: 'pending',
                label: '<?php echo esc_js( __( 'Pending', 'vms-theme' ) ); ?>'
            },
            {
                value: 'confirmed',
                label: '<?php echo esc_js( __( 'Confirmed', 'vms-theme' ) ); ?>'
            },
            {
                value: 'checked_in',
                label: '<?php echo esc_js( __( 'Checked In', 'vms-theme' ) ); ?>'
            },
            {
                value: 'checked_out',
                label: '<?php echo esc_js( __( 'Checked Out', 'vms-theme' ) ); ?>'
            }
        ],

        get filteredBookings() {
            if (this.currentTab === 'all') return this.bookings;
            return this.bookings.filter(b => b.visit_status === this.currentTab);
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredBookings.length / this.perPage));
        },
        get paginatedBookings() {
            const s = (this.currentPage - 1) * this.perPage;
            return this.filteredBookings.slice(s, s + this.perPage);
        },

        init() {
            this.loadBookings();
        },

        async loadBookings() {
            this.loading = true;
            try {
                const fd = new FormData();
                fd.append('action', 'vms_get_accom_visits');
                fd.append('_ajax_nonce', vmsTheme.nonces.guest);
                fd.append('per_page', 200);
                const r = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                const d = await r.json();
                if (d.success) {
                    const rows = d.data?.rows || d.data?.visits || d.data || [];
                    this.bookings = Array.isArray(rows) ? rows : [];
                }
            } catch (e) {
                console.error('Accommodation load error:', e);
            } finally {
                this.loading = false;
            }
        },

        openAddModal() {
            this.form = {
                first_name: '',
                last_name: '',
                phone_number: '',
                email: '',
                id_number: '',
                room_number: '',
                check_in_date: new Date().toISOString().split('T')[0],
                check_out_date: ''
            };
            this.formError = '';
            this.showAddModal = true;
        },

        async saveBooking() {
            this.formError = '';
            this.saving = true;
            try {
                // Step 1: register guest
                const gfd = new FormData();
                gfd.append('action', 'vms_register_accom_guest');
                gfd.append('_ajax_nonce', vmsTheme.nonces.guest);
                Object.entries(this.form).forEach(([k, v]) => {
                    if (!['room_number', 'check_in_date', 'check_out_date'].includes(k)) gfd.append(k, v);
                });
                const gr = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: gfd
                });
                const gd = await gr.json();

                let guestId = null;
                if (gd.success) {
                    guestId = gd.data?.guest?.id;
                } else if (gd.data?.data?.existing_id) {
                    // Guest already exists — reuse them
                    guestId = gd.data.data.existing_id;
                } else {
                    this.formError = gd.data?.message || vmsTheme.i18n.error;
                    return;
                }

                // Step 2: register visit
                const vfd = new FormData();
                vfd.append('action', 'vms_register_accom_visit');
                vfd.append('_ajax_nonce', vmsTheme.nonces.guest);
                vfd.append('guest_id', guestId);
                vfd.append('check_in_date', this.form.check_in_date);
                vfd.append('check_out_date', this.form.check_out_date);
                vfd.append('room_number', this.form.room_number);
                const vr = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: vfd
                });
                const vd = await vr.json();

                if (vd.success) {
                    this.showAddModal = false;
                    this.loadBookings();
                    window.vmsToast && window.vmsToast(
                        '<?php echo esc_js( __( 'Booking saved successfully.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    this.formError = vd.data?.message || vmsTheme.i18n.error;
                }
            } catch (e) {
                this.formError = vmsTheme.i18n.error + ' [' + e.message + ']';
            } finally {
                this.saving = false;
            }
        },

        async handleCheckin(booking) {
            try {
                const fd = new FormData();
                fd.append('action', 'vms_checkin_accom');
                fd.append('_ajax_nonce', vmsTheme.nonces.guest);
                fd.append('visit_id', booking.id);
                const r = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                const d = await r.json();
                if (d.success) {
                    this.loadBookings();
                    window.vmsToast && window.vmsToast(
                        '<?php echo esc_js( __( 'Guest checked in.', 'vms-theme' ) ); ?>', 'success');
                } else window.vmsToast && window.vmsToast(d.data?.message || vmsTheme.i18n.error, 'error');
            } catch (e) {
                window.vmsToast && window.vmsToast(vmsTheme.i18n.error, 'error');
            }
        },

        async handleCheckout(booking) {
            try {
                const fd = new FormData();
                fd.append('action', 'vms_checkout_accom');
                fd.append('_ajax_nonce', vmsTheme.nonces.guest);
                fd.append('visit_id', booking.id);
                const r = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                const d = await r.json();
                if (d.success) {
                    this.loadBookings();
                    window.vmsToast && window.vmsToast(
                        '<?php echo esc_js( __( 'Guest checked out.', 'vms-theme' ) ); ?>', 'success');
                } else window.vmsToast && window.vmsToast(d.data?.message || vmsTheme.i18n.error, 'error');
            } catch (e) {
                window.vmsToast && window.vmsToast(vmsTheme.i18n.error, 'error');
            }
        },

        openEditModal(booking) {
            this.editingGuest = booking;
            this.editForm = {
                first_name: booking.first_name || '',
                last_name: booking.last_name || '',
                phone_number: booking.phone_number || '',
                email: booking.email || '',
                id_number: booking.id_number || '',
                guest_status: booking.guest_status || 'active'
            };
            this.editError = '';
            this.showEditModal = true;
        },

        async submitEditGuest() {
            this.editError = '';
            this.editSaving = true;
            try {
                const fd = new FormData();
                fd.append('action', 'vms_update_accom_guest');
                fd.append('_ajax_nonce', vmsTheme.nonces.guest);
                // The guest_id is stored in the visit object under 'guest_id', not 'id' (which is the visit id)
                fd.append('guest_id', this.editingGuest.guest_id);
                Object.entries(this.editForm).forEach(([k, v]) => fd.append(k, v));

                const r = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                const d = await r.json();

                if (d.success) {
                    this.showEditModal = false;
                    this.loadBookings();
                    window.vmsToast && window.vmsToast(d.data?.message ||
                        '<?php echo esc_js( __( 'Guest updated successfully.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    this.editError = d.data?.message || vmsTheme.i18n.error;
                }
            } catch (e) {
                this.editError = vmsTheme.i18n.error;
            } finally {
                this.editSaving = false;
            }
        }
    };
}
</script>
<?php get_footer(); ?>