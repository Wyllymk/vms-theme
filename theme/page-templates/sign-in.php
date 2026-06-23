<?php
/* Template Name: VMS Sign-In Desk */

/**
 * Sign-in desk for reception — view today's visits and sign guests in/out.
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
vms_require_capability( 'vms_signin_guests' );

get_header();
?>

<div class="flex flex-col gap-6" x-data="signInPage()" x-init="init()">	

	<!-- ================================================================
	     PAGE HEADER
	     ================================================================ -->
    <div class="vms-card">
        <div class="vms-flex vms-items-center vms-justify-between" style="flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="vms-text-xl vms-font-bold" style="margin:0;">
                    <?php esc_html_e( 'Sign-In Desk', 'vms-theme' ); ?>
                </h1>
                <p class="vms-text-sm vms-text-muted" style="margin:0.25rem 0 0;">
					<?php esc_html_e( "Manage today's guest sign-ins and sign-outs.", 'vms-theme' ); ?>
                </p>
            </div>
            <div class="vms-flex vms-gap-2" style="flex-wrap:wrap;">
                <button
					@click="loadVisits()"
					class="flex items-center gap-2 vms-btn vms-btn-secondary"
				>
					<svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
					</svg>
					<?php esc_html_e( 'Refresh', 'vms-theme' ); ?>
				</button>
            </div>
        </div>
    </div>

	<!-- Summary Cards -->
	<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
		<div class="vms-card">
			<p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="visits.length">0</p>
			<p class="text-xs text-gray-500 dark:text-gray-400"><?php esc_html_e( 'Total Today', 'vms-theme' ); ?></p>
		</div>
		<div class="vms-card">
			<p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="visits.filter(v => v.status === 'approved' && !v.sign_in_time).length">0</p>
			<p class="text-xs text-gray-500 dark:text-gray-400"><?php esc_html_e( 'Expected', 'vms-theme' ); ?></p>
		</div>
		<div class="vms-card">
			<p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="visits.filter(v => v.sign_in_time && !v.sign_out_time).length">0</p>
			<p class="text-xs text-gray-500 dark:text-gray-400"><?php esc_html_e( 'Signed In', 'vms-theme' ); ?></p>
		</div>
		<div class="vms-card">
			<p class="text-2xl font-bold text-gray-600 dark:text-gray-400" x-text="visits.filter(v => v.sign_out_time).length">0</p>
			<p class="text-xs text-gray-500 dark:text-gray-400"><?php esc_html_e( 'Signed Out', 'vms-theme' ); ?></p>
		</div>
	</div>

	<!-- Filter Tabs -->
	<div class="flex gap-2 py-2 overflow-x-auto">
		<template x-for="tab in filterTabs" :key="tab.value">
			<button
				@click="currentFilter = tab.value"
				class="vms-btn vms-btn-sm"
				:class="currentFilter === tab.value
					? 'vms-btn-primary'
					: 'vms-btn-secondary'"
				x-text="tab.label"
			></button>
		</template>
	</div>

	<!-- Visits List -->
	<div class="flex flex-col gap-3">
		<!-- Loading -->
		<div x-show="loading" class="vms-card">
			<svg class="animate-spin h-8 w-8 mx-auto text-[var(--vms-primary)]" fill="none" viewBox="0 0 24 24">
				<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
				<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
			</svg>
		</div>

		<template x-for="visit in filteredVisits" :key="visit.id">
			<div class="vms-card">
				<div class="flex flex-col gap-4 md:flex-row md:items-center">
					<!-- Guest Info -->
					<div class="flex items-center flex-1 min-w-0 gap-3">
						<div class="flex items-center justify-center w-12 h-12 text-sm font-bold rounded-full shrink-0"
							:class="{
								'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': visit.sign_in_time && !visit.sign_out_time,
								'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': !visit.sign_in_time && visit.status === 'approved',
								'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400': visit.sign_out_time,
								'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': visit.status === 'unapproved'
							}"
							x-text="(visit.first_name?.[0] || '') + (visit.last_name?.[0] || '')"
						></div>
						<div class="min-w-0">
							<p class="text-base font-semibold text-gray-900 truncate dark:text-white" x-text="visit.first_name + ' ' + visit.last_name"></p>
							<div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
								<span x-text="visit.phone_number"></span>
								<span x-show="visit.id_number">&middot;</span>
								<span x-show="visit.id_number" x-text="'ID: ' + visit.id_number"></span>
							</div>
						</div>
					</div>

					<!-- Status Badge -->
					<div class="flex items-center gap-3">
						<span class="inline-flex px-3 py-1 text-xs font-medium rounded-full"
							:class="{
								'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': visit.sign_in_time && !visit.sign_out_time,
								'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': !visit.sign_in_time && visit.status === 'approved',
								'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400': visit.sign_out_time,
								'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': visit.status === 'unapproved',
								'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': visit.status === 'cancelled'
							}"
						>
							<span x-show="visit.sign_out_time"><?php esc_html_e( 'Signed Out', 'vms-theme' ); ?></span>
							<span x-show="visit.sign_in_time && !visit.sign_out_time"><?php esc_html_e( 'Signed In', 'vms-theme' ); ?></span>
							<span x-show="!visit.sign_in_time && visit.status === 'approved'"><?php esc_html_e( 'Expected', 'vms-theme' ); ?></span>
							<span x-show="visit.status === 'unapproved'"><?php esc_html_e( 'Pending', 'vms-theme' ); ?></span>
							<span x-show="visit.status === 'cancelled'"><?php esc_html_e( 'Cancelled', 'vms-theme' ); ?></span>
						</span>

						<div x-show="visit.sign_in_time && !visit.sign_out_time" class="text-xs text-gray-500 dark:text-gray-400">
							<span><?php esc_html_e( 'In:', 'vms-theme' ); ?></span>
							<span x-text="formatTime(visit.sign_in_time)"></span>
						</div>
					</div>

					<!-- Actions -->
					<div class="flex items-center gap-2 shrink-0">
						<!-- Sign In Button -->
						<template x-if="!visit.sign_in_time && visit.status === 'approved'">
							<button
								@click="openSignIn(visit)"
								class="px-4 py-2 text-sm font-medium text-white transition-colors bg-green-600 shadow-sm hover:bg-green-700 rounded-xl"
							>
								<?php esc_html_e( 'Sign In', 'vms-theme' ); ?>
							</button>
						</template>

						<!-- Sign Out Button -->
						<template x-if="visit.sign_in_time && !visit.sign_out_time">
							<button
								@click="handleSignOut(visit)"
								class="px-4 py-2 text-sm font-medium text-white transition-colors bg-red-600 shadow-sm hover:bg-red-700 rounded-xl"
							>
								<?php esc_html_e( 'Sign Out', 'vms-theme' ); ?>
							</button>
						</template>

						<!-- Cancel Button -->
						<template x-if="!visit.sign_in_time && visit.status !== 'cancelled'">
							<button
								@click="handleCancel(visit)"
								class="px-3 py-2 text-sm font-medium text-gray-600 transition-colors dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400"
							>
								<?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
							</button>
						</template>
					</div>
				</div>
			</div>
		</template>

		<!-- Empty State -->
		<div x-show="!loading && filteredVisits.length === 0" class="text-center vms-card">
			<svg class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
			</svg>
			<h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
				<?php esc_html_e( 'No Visits', 'vms-theme' ); ?>
			</h3>
			<p class="text-gray-500 dark:text-gray-400">
				<?php esc_html_e( 'There are no visits matching the current filter.', 'vms-theme' ); ?>
			</p>
		</div>
	</div>

	<!-- Sign-In Modal -->
	<div x-show="showSignInModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
		<div @click="showSignInModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
		<div class="relative w-full max-w-md bg-white border border-gray-200 shadow-2xl dark:bg-gray-800 rounded-2xl dark:border-gray-700" @click.stop>
			<div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
				<h3 class="text-lg font-semibold text-gray-900 dark:text-white">
					<?php esc_html_e( 'Sign In Guest', 'vms-theme' ); ?>
				</h3>
				<button @click="showSignInModal = false" class="p-1 text-gray-400 rounded-lg hover:text-gray-600 dark:hover:text-gray-300">
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
					</svg>
				</button>
			</div>

			<form @submit.prevent="handleSignIn" class="flex flex-col gap-4 p-6">
				<div class="mb-4 text-center">
					<p class="text-lg font-medium text-gray-900 dark:text-white" x-text="signingInVisit?.first_name + ' ' + signingInVisit?.last_name"></p>
					<p class="text-sm text-gray-500 dark:text-gray-400" x-text="signingInVisit?.phone_number"></p>
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
						<?php esc_html_e( 'ID / Passport Number', 'vms-theme' ); ?> <span class="text-red-500">*</span>
					</label>
					<input type="text" x-model="signInIdNumber" required autofocus
						class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-center text-lg tracking-wider focus:ring-2 focus:ring-[var(--vms-primary)] focus:border-transparent"
						placeholder="<?php esc_attr_e( 'Enter ID number to verify', 'vms-theme' ); ?>">
				</div>

				<div x-show="signInError" x-cloak
					class="p-3 text-sm text-red-600 border border-red-200 rounded-xl bg-red-50 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400"
					x-text="signInError"
				></div>

				<button type="submit" :disabled="signingIn"
					class="w-full py-2.5 px-4 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2">
					<svg x-show="signingIn" x-cloak class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
					</svg>
					<?php esc_html_e( 'Confirm Sign In', 'vms-theme' ); ?>
				</button>
			</form>
		</div>
	</div>
</div>

<script>
function signInPage() {
	return {
		visits: [],
		loading: true,
		refreshing: false,
		currentFilter: 'all',
		showSignInModal: false,
		signingInVisit: null,
		signInIdNumber: '',
		signInError: '',
		signingIn: false,

		filterTabs: [
			{ value: 'all', label: '<?php echo esc_js( __( 'All', 'vms-theme' ) ); ?>' },
			{ value: 'expected', label: '<?php echo esc_js( __( 'Expected', 'vms-theme' ) ); ?>' },
			{ value: 'signed_in', label: '<?php echo esc_js( __( 'Signed In', 'vms-theme' ) ); ?>' },
			{ value: 'signed_out', label: '<?php echo esc_js( __( 'Signed Out', 'vms-theme' ) ); ?>' },
			{ value: 'pending', label: '<?php echo esc_js( __( 'Pending', 'vms-theme' ) ); ?>' }
		],

		get filteredVisits() {
			return this.visits.filter(v => {
				switch (this.currentFilter) {
					case 'expected': return !v.sign_in_time && v.status === 'approved';
					case 'signed_in': return v.sign_in_time && !v.sign_out_time;
					case 'signed_out': return !!v.sign_out_time;
					case 'pending': return v.status === 'unapproved';
					default: return v.status !== 'cancelled';
				}
			});
		},

		init() {
			this.loadVisits();
			// Auto-refresh every 30 seconds.
			setInterval(() => this.loadVisits(), 30000);
		},

		async loadVisits() {
			if (!this.loading) this.refreshing = true;

			try {
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
					this.visits = data.data.visits || [];
				}
			} catch (e) {
				console.error('Failed to load visits:', e);
			} finally {
				this.loading = false;
				this.refreshing = false;
			}
		},

		formatTime(datetime) {
			if (!datetime) return '';
			const d = new Date(datetime.replace(' ', 'T'));
			return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
		},

		openSignIn(visit) {
			this.signingInVisit = visit;
			this.signInIdNumber = '';
			this.signInError = '';
			this.showSignInModal = true;
		},

		async handleSignIn() {
			this.signInError = '';
			this.signingIn = true;

			try {
				const formData = new FormData();
				formData.append('action', 'vms_signin_guest');
				formData.append('_ajax_nonce', vmsTheme.nonces.guest);
				formData.append('visit_id', this.signingInVisit.id);
				formData.append('id_number', this.signInIdNumber);

				const response = await fetch(vmsTheme.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				});

				const data = await response.json();

				if (data.success) {
					this.showSignInModal = false;
					this.loadVisits();
					window.dispatchEvent(new CustomEvent('toast', {
						detail: { type: 'success', message: data.data.message }
					}));
				} else {
					this.signInError = data.data?.message || vmsTheme.i18n.error;
				}
			} catch (e) {
				this.signInError = vmsTheme.i18n.error;
			} finally {
				this.signingIn = false;
			}
		},

		async handleSignOut(visit) {
			if (!confirm(vmsTheme.i18n.confirm)) return;

			try {
				const formData = new FormData();
				formData.append('action', 'vms_signout_guest');
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
					window.dispatchEvent(new CustomEvent('toast', {
						detail: { type: 'success', message: data.data.message }
					}));
				} else {
					window.dispatchEvent(new CustomEvent('toast', {
						detail: { type: 'error', message: data.data?.message || vmsTheme.i18n.error }
					}));
				}
			} catch (e) {
				window.dispatchEvent(new CustomEvent('toast', {
					detail: { type: 'error', message: vmsTheme.i18n.error }
				}));
			}
		},

		async handleCancel(visit) {
			if (!confirm(vmsTheme.i18n.confirm)) return;

			try {
				const formData = new FormData();
				formData.append('action', 'vms_cancel_visit');
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
					window.dispatchEvent(new CustomEvent('toast', {
						detail: { type: 'success', message: data.data.message }
					}));
				} else {
					window.dispatchEvent(new CustomEvent('toast', {
						detail: { type: 'error', message: data.data?.message || vmsTheme.i18n.error }
					}));
				}
			} catch (e) {
				window.dispatchEvent(new CustomEvent('toast', {
					detail: { type: 'error', message: vmsTheme.i18n.error }
				}));
			}
		}
	};
}
</script>

<?php
get_footer();
