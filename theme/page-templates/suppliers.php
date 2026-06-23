<?php
/* Template Name: VMS Suppliers */

/**
 * Supplier management page.
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

vms_require_module( 'suppliers' );
vms_require_capability( 'vms_manage_suppliers' );

get_header();
?>

<div class="flex flex-col gap-6" x-data="suppliersPage()" x-init="init()">

    <!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Suppliers', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
                    <?php esc_html_e( 'Manage supplier access and deliveries.', 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <button @click="showAddModal = true" class="vms-btn vms-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?php esc_html_e( 'Add Supplier', 'vms-theme' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="vms-card">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="search" x-model="searchTerm"
                placeholder="<?php esc_attr_e( 'Search suppliers...', 'vms-theme' ); ?>" class="vms-input">
        </div>
    </div>

    <!-- Suppliers Grid -->
    <div x-show="!loading && filteredSuppliers.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="supplier in filteredSuppliers" :key="supplier.id">
            <div class="vms-card">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl bg-[var(--vms-primary)]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[var(--vms-primary)]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full capitalize" :class="{
							'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': supplier.supplier_status === 'active',
							'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': supplier.supplier_status === 'suspended',
							'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': supplier.supplier_status === 'banned'
						}" x-text="supplier.supplier_status || 'unknown'"></span>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1" x-text="supplier.name"></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1" x-text="supplier.contact_person || ''"></p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3" x-text="supplier.phone || ''"></p>
                <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-700/50">
                    <button @click="editSupplier(supplier)"
                        class="flex-1 py-1.5 text-sm font-medium text-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/10 rounded-lg transition-colors">
                        <?php esc_html_e( 'Edit', 'vms-theme' ); ?>
                    </button>
                    <button @click="deleteSupplier(supplier)"
                        class="flex-1 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        <?php esc_html_e( 'Delete', 'vms-theme' ); ?>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="vms-card text-center">
        <svg class="animate-spin h-8 w-8 mx-auto text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            <?php esc_html_e( 'Loading suppliers...', 'vms-theme' ); ?></p>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && filteredSuppliers.length === 0" class="vms-card text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            <?php esc_html_e( 'No Suppliers Found', 'vms-theme' ); ?>
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
            <?php esc_html_e( 'Add your first supplier to get started.', 'vms-theme' ); ?>
        </p>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div @click="showAddModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg"
            @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                    x-text="editingSupplier ? '<?php echo esc_js( __( 'Edit Supplier', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Add Supplier', 'vms-theme' ) ); ?>'">
                </h3>
                <button @click="showAddModal = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form @submit.prevent="saveSupplier" class="p-6 flex flex-col gap-4">
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Company Name', 'vms-theme' ); ?>
                        <span class="text-red-500">*</span></label>
                    <input type="text" x-model="supplierForm.company_name" required
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Contact First Name', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="text" x-model="supplierForm.contact_first_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Contact Last Name', 'vms-theme' ); ?>
                            <span class="text-red-500">*</span></label>
                        <input type="text" x-model="supplierForm.contact_last_name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Phone', 'vms-theme' ); ?></label>
                        <input type="tel" x-model="supplierForm.phone_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Email', 'vms-theme' ); ?></label>
                        <input type="email" x-model="supplierForm.email"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'ID Number', 'vms-theme' ); ?></label>
                        <input type="text" x-model="supplierForm.id_number"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Vehicle Registration', 'vms-theme' ); ?></label>
                        <input type="text" x-model="supplierForm.vehicle_reg"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?php esc_html_e( 'Status', 'vms-theme' ); ?></label>
                    <select x-model="supplierForm.status"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
                        <option value="active"><?php esc_html_e( 'Active', 'vms-theme' ); ?></option>
                        <option value="suspended"><?php esc_html_e( 'Suspended', 'vms-theme' ); ?></option>
                        <option value="banned"><?php esc_html_e( 'Banned', 'vms-theme' ); ?></option>
                    </select>
                </div>
                <div x-show="supplierError" x-cloak
                    class="p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
                    x-text="supplierError"></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAddModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
                    </button>
                    <button type="submit" :disabled="saving"
                        class="px-4 py-2 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-sm transition-colors disabled:opacity-50">
                        <?php esc_html_e( 'Save', 'vms-theme' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function suppliersPage() {
    return {
        suppliers: [],
        searchTerm: '',
        loading: true,
        showAddModal: false,
        editingSupplier: null,
        saving: false,
        supplierError: '',
        supplierForm: {
            company_name: '',
            contact_first_name: '',
            contact_last_name: '',
            phone_number: '',
            email: '',
            id_number: '',
            vehicle_reg: '',
            status: 'active'
        },

        get filteredSuppliers() {
            if (!this.searchTerm) return this.suppliers;
            const term = this.searchTerm.toLowerCase();
            return this.suppliers.filter(s =>
                (s.company_name && s.company_name.toLowerCase().includes(term)) ||
                (s.contact_first_name && s.contact_first_name.toLowerCase().includes(term)) ||
                (s.contact_last_name && s.contact_last_name.toLowerCase().includes(term)) ||
                (s.phone_number && s.phone_number.toLowerCase().includes(term))
            );
        },

        init() {
            this.loadSuppliers();
        },

        async loadSuppliers() {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('action', 'vms_get_suppliers');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    let fetched = data.data && data.data.rows ? data.data.rows : (data.data || []);
                    this.suppliers = fetched.map(s => ({
                        ...s,
                        name: s.company_name,
                        contact_person: s.contact_first_name + ' ' + s.contact_last_name,
                        phone: s.phone_number,
                        active: s.supplier_status === 'active'
                    }));
                }
            } catch (e) {
                console.error('Failed to load suppliers:', e);
            } finally {
                this.loading = false;
            }
        },

        editSupplier(supplier) {
            this.editingSupplier = supplier;
            this.supplierForm = {
                company_name: supplier.company_name || '',
                contact_first_name: supplier.contact_first_name || '',
                contact_last_name: supplier.contact_last_name || '',
                phone_number: supplier.phone_number || '',
                email: supplier.email || '',
                id_number: supplier.id_number || '',
                vehicle_reg: supplier.vehicle_reg || '',
                status: supplier.supplier_status || 'active'
            };
            this.supplierError = '';
            this.showAddModal = true;
        },

        async saveSupplier() {
            this.supplierError = '';
            this.saving = true;

            try {
                const formData = new FormData();
                formData.append('action', this.editingSupplier ? 'vms_update_supplier' : 'vms_register_supplier');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);

                if (this.editingSupplier) {
                    formData.append('supplier_id', this.editingSupplier.id);
                }

                Object.keys(this.supplierForm).forEach(key => {
                    let apiValue = this.supplierForm[key];
                    if (key === 'status') {
                        formData.append('supplier_status', apiValue);
                    } else {
                        formData.append(key, apiValue);
                    }
                });

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showAddModal = false;
                    this.loadSuppliers();
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Supplier saved successfully.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    this.supplierError = data.data?.message || vmsTheme.i18n.error;
                }
            } catch (e) {
                console.error(e);
                this.supplierError = `${vmsTheme.i18n.error} [${e.message}]`;
            } finally {
                this.saving = false;
            }
        },

        async deleteSupplier(supplier) {
            if (!confirm(vmsTheme.i18n.confirm)) return;

            try {
                const formData = new FormData();
                formData.append('action', 'vms_delete_supplier');
                formData.append('_ajax_nonce', vmsTheme.nonces.guest);
                formData.append('supplier_id', supplier.id);

                const response = await fetch(vmsTheme.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.loadSuppliers();
                    window.vmsToast(data.data?.message ||
                        '<?php echo esc_js( __( 'Supplier removed.', 'vms-theme' ) ); ?>', 'success');
                } else {
                    window.vmsToast(data.data?.message || vmsTheme.i18n.error, 'error');
                }
            } catch (e) {
                window.vmsToast(vmsTheme.i18n.error, 'error');
            }
        }
    };
}
</script>

<?php
get_footer();