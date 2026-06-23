<?php
/* Template Name: VMS Register Guest */

/**
 * Guest + visit registration form.
 *
 * Allows creating a new guest and optionally scheduling a visit
 * in a single flow, all submitted via AJAX.
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

$role = vms_current_role();
?>

<div class="max-w-3xl mx-auto space-y-6" x-data="registerGuestPage()" x-init="init()">
	<!-- Page Header -->
	<div>
		<h1 class="text-2xl font-bold text-gray-900 dark:text-white">
			<?php esc_html_e( 'Register Guest', 'vms-theme' ); ?>
		</h1>
		<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
			<?php esc_html_e( 'Create a new guest record and optionally register a visit.', 'vms-theme' ); ?>
		</p>
	</div>

	<!-- Search Existing Guest -->
	<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
		<h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
			<?php esc_html_e( 'Search Existing Guest', 'vms-theme' ); ?>
		</h2>
		<div class="relative">
			<svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
			</svg>
			<input
				type="search"
				x-model.debounce.400ms="searchTerm"
				@input="searchExisting()"
				placeholder="<?php esc_attr_e( 'Search by name, phone, or ID number...', 'vms-theme' ); ?>"
				class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent"
			>
		</div>

		<!-- Search Results -->
		<div x-show="searchResults.length > 0" x-cloak class="mt-3 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
			<template x-for="result in searchResults" :key="result.id">
				<button
					@click="selectExistingGuest(result)"
					class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700/50 last:border-0 transition-colors"
				>
					<div class="w-8 h-8 rounded-full bg-[var(--vms-primary)]/10 flex items-center justify-center text-xs font-medium text-[var(--vms-primary)]"
						x-text="(result.first_name?.[0] || '') + (result.last_name?.[0] || '')"
					></div>
					<div>
						<p class="text-sm font-medium text-gray-900 dark:text-white" x-text="result.first_name + ' ' + result.last_name"></p>
						<p class="text-xs text-gray-500 dark:text-gray-400" x-text="result.phone_number"></p>
					</div>
					<span class="ml-auto text-xs px-2 py-0.5 rounded-full"
						:class="{
							'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': result.guest_status === 'active',
							'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': result.guest_status === 'suspended',
							'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': result.guest_status === 'banned'
						}"
						x-text="result.guest_status"
					></span>
				</button>
			</template>
		</div>
	</div>

	<!-- Registration Form -->
	<form @submit.prevent="handleSubmit" class="space-y-6">
		<!-- Guest Details -->
		<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-lg font-semibold text-gray-900 dark:text-white">
					<?php esc_html_e( 'Guest Details', 'vms-theme' ); ?>
				</h2>
				<span x-show="selectedGuest" x-cloak class="text-sm text-[var(--vms-primary)] font-medium">
					<?php esc_html_e( 'Existing guest selected', 'vms-theme' ); ?>
					<button type="button" @click="clearSelection()" class="ml-1 underline"><?php esc_html_e( 'Clear', 'vms-theme' ); ?></button>
				</span>
			</div>

			<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
						<?php esc_html_e( 'First Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
					</label>
					<input type="text" x-model="form.first_name" required :readonly="!!selectedGuest"
						class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent read-only:opacity-60">
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
						<?php esc_html_e( 'Last Name', 'vms-theme' ); ?> <span class="text-red-500">*</span>
					</label>
					<input type="text" x-model="form.last_name" required :readonly="!!selectedGuest"
						class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent read-only:opacity-60">
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
						<?php esc_html_e( 'Phone Number', 'vms-theme' ); ?> <span class="text-red-500">*</span>
					</label>
					<input type="tel" x-model="form.phone_number" required :readonly="!!selectedGuest"
						class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent read-only:opacity-60"
						placeholder="<?php esc_attr_e( '+254712345678', 'vms-theme' ); ?>">
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
						<?php esc_html_e( 'Email', 'vms-theme' ); ?>
					</label>
					<input type="email" x-model="form.email" :readonly="!!selectedGuest"
						class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent read-only:opacity-60">
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
						<?php esc_html_e( 'ID / Passport Number', 'vms-theme' ); ?>
					</label>
					<input type="text" x-model="form.id_number"
						class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
				</div>
				<div class="flex items-end gap-4">
					<label class="flex items-center gap-2 cursor-pointer">
						<input type="checkbox" x-model="form.receive_emails" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-[var(--vms-primary)] focus:ring-[var(--vms-primary)]">
						<span class="text-sm text-gray-700 dark:text-gray-300"><?php esc_html_e( 'Email notifications', 'vms-theme' ); ?></span>
					</label>
					<label class="flex items-center gap-2 cursor-pointer">
						<input type="checkbox" x-model="form.receive_messages" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-[var(--vms-primary)] focus:ring-[var(--vms-primary)]">
						<span class="text-sm text-gray-700 dark:text-gray-300"><?php esc_html_e( 'SMS notifications', 'vms-theme' ); ?></span>
					</label>
				</div>
			</div>

			<div class="mt-4">
				<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
					<?php esc_html_e( 'Notes', 'vms-theme' ); ?>
				</label>
				<textarea x-model="form.notes" rows="2"
					class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent"
					placeholder="<?php esc_attr_e( 'Optional notes about this guest...', 'vms-theme' ); ?>"></textarea>
			</div>
		</div>

		<!-- Visit Details -->
		<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
			<div class="flex items-center gap-3 mb-4">
				<label class="flex items-center gap-2 cursor-pointer">
					<input type="checkbox" x-model="registerVisit" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-[var(--vms-primary)] focus:ring-[var(--vms-primary)]">
					<span class="text-lg font-semibold text-gray-900 dark:text-white"><?php esc_html_e( 'Register Visit', 'vms-theme' ); ?></span>
				</label>
			</div>

			<div x-show="registerVisit" x-collapse class="grid grid-cols-1 sm:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
						<?php esc_html_e( 'Visit Date', 'vms-theme' ); ?> <span class="text-red-500">*</span>
					</label>
					<input type="date" x-model="visitForm.visit_date" :min="today"
						class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent">
				</div>

				<?php if ( in_array( $role, array( 'administrator', 'chairman', 'general_manager', 'reception' ), true ) ) : ?>
					<div>
						<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
							<?php esc_html_e( 'Courtesy Designation', 'vms-theme' ); ?>
						</label>
						<input type="text" x-model="visitForm.courtesy"
							class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent"
							placeholder="<?php esc_attr_e( 'e.g. Chairman\'s guest', 'vms-theme' ); ?>">
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Error -->
		<div x-show="error" x-cloak
			class="p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
			x-text="error"
		></div>

		<!-- Submit -->
		<div class="flex justify-end gap-3">
			<a href="<?php echo esc_url( vms_get_page_url( 'guests' ) ); ?>"
				class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
				<?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
			</a>
			<button type="submit" :disabled="loading"
				class="px-6 py-2.5 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-lg shadow-[var(--vms-primary)]/25 transition-all duration-200 disabled:opacity-50 flex items-center gap-2">
				<svg x-show="loading" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
					<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
				</svg>
				<span x-text="loading ? '<?php echo esc_js( __( 'Registering...', 'vms-theme' ) ); ?>' : (registerVisit ? '<?php echo esc_js( __( 'Register Guest & Visit', 'vms-theme' ) ); ?>' : '<?php echo esc_js( __( 'Register Guest', 'vms-theme' ) ); ?>')"></span>
			</button>
		</div>
	</form>
</div>

<script>
function registerGuestPage() {
	return {
		searchTerm: '',
		searchResults: [],
		selectedGuest: null,
		registerVisit: true,
		loading: false,
		error: '',
		today: new Date().toISOString().split('T')[0],
		form: {
			first_name: '',
			last_name: '',
			phone_number: '',
			email: '',
			id_number: '',
			receive_emails: true,
			receive_messages: true,
			notes: ''
		},
		visitForm: {
			visit_date: new Date().toISOString().split('T')[0],
			courtesy: ''
		},

		init() {},

		async searchExisting() {
			if (this.searchTerm.length < 2) {
				this.searchResults = [];
				return;
			}

			try {
				const formData = new FormData();
				formData.append('action', 'vms_search_guests');
				formData.append('_ajax_nonce', vmsTheme.nonces.guest);
				formData.append('term', this.searchTerm);

				const response = await fetch(vmsTheme.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				});

				const data = await response.json();
				this.searchResults = data.success ? (data.data.results || []) : [];
			} catch (e) {
				this.searchResults = [];
			}
		},

		selectExistingGuest(guest) {
			this.selectedGuest = guest;
			this.form.first_name = guest.first_name;
			this.form.last_name = guest.last_name;
			this.form.phone_number = guest.phone_number;
			this.form.email = guest.email || '';
			this.form.id_number = guest.id_number || '';
			this.searchResults = [];
			this.searchTerm = '';
		},

		clearSelection() {
			this.selectedGuest = null;
			this.form = {
				first_name: '', last_name: '', phone_number: '', email: '',
				id_number: '', receive_emails: true, receive_messages: true, notes: ''
			};
		},

		async handleSubmit() {
			this.error = '';
			this.loading = true;

			try {
				let guestId = this.selectedGuest?.id;

				// Step 1: Register guest if new.
				if (!guestId) {
					const guestData = new FormData();
					guestData.append('action', 'vms_register_guest');
					guestData.append('_ajax_nonce', vmsTheme.nonces.guest);
					guestData.append('first_name', this.form.first_name);
					guestData.append('last_name', this.form.last_name);
					guestData.append('phone_number', this.form.phone_number);
					guestData.append('email', this.form.email);
					guestData.append('id_number', this.form.id_number);
					guestData.append('receive_emails', this.form.receive_emails ? '1' : '0');
					guestData.append('receive_messages', this.form.receive_messages ? '1' : '0');
					guestData.append('notes', this.form.notes);

					const guestRes = await fetch(vmsTheme.ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						body: guestData
					});

					const guestJson = await guestRes.json();

					if (!guestJson.success) {
						this.error = guestJson.data?.message || vmsTheme.i18n.error;
						return;
					}

					guestId = guestJson.data.guest.id;
				}

				// Step 2: Register visit if requested.
				if (this.registerVisit) {
					const visitData = new FormData();
					visitData.append('action', 'vms_register_visit');
					visitData.append('_ajax_nonce', vmsTheme.nonces.guest);
					visitData.append('guest_id', guestId);
					visitData.append('visit_date', this.visitForm.visit_date);
					visitData.append('host_id', vmsTheme.currentUser.id);
					if (this.visitForm.courtesy) {
						visitData.append('courtesy', this.visitForm.courtesy);
					}

					const visitRes = await fetch(vmsTheme.ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						body: visitData
					});

					const visitJson = await visitRes.json();

					if (!visitJson.success) {
						this.error = visitJson.data?.message || vmsTheme.i18n.error;
						return;
					}
				}

				window.dispatchEvent(new CustomEvent('toast', {
					detail: {
						type: 'success',
						message: this.registerVisit
							? '<?php echo esc_js( __( 'Guest and visit registered successfully.', 'vms-theme' ) ); ?>'
							: '<?php echo esc_js( __( 'Guest registered successfully.', 'vms-theme' ) ); ?>'
					}
				}));

				// Reset form.
				this.clearSelection();
				this.visitForm = { visit_date: this.today, courtesy: '' };

			} catch (e) {
				this.error = vmsTheme.i18n.error;
			} finally {
				this.loading = false;
			}
		}
	};
}
</script>

<?php
get_footer();
