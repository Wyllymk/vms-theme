<?php
/**
 * Template tag helpers.
 *
 * Reusable functions called from templates to render common UI elements
 * and resolve VMS page URLs.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the permalink for a VMS page by its slug.
 *
 * Falls back to home_url( '/' . $slug . '/' ) if the page doesn't exist.
 *
 * @param string $slug Page slug (e.g. 'dashboard', 'sign-in', 'guests').
 * @return string Absolute URL.
 */
function vms_get_page_url( string $slug ): string {
	$page = get_page_by_path( $slug );

	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	return home_url( '/' . sanitize_title( $slug ) . '/' );
}

/**
 * Check whether the current page is a VMS-managed page.
 *
 * A page is considered VMS-managed if it uses one of the theme's page
 * templates from the page-templates/ directory.
 *
 * @return bool
 */
function vms_is_vms_page(): bool {
	if ( ! is_page() ) {
		return false;
	}

	$template = get_page_template_slug();

	if ( $template && str_starts_with( $template, 'page-templates/' ) ) {
		return true;
	}

	// Also check for front-page.
	if ( is_front_page() ) {
		return true;
	}

	return false;
}

/**
 * Render the toast notification container.
 *
 * Toasts are driven by Alpine.js. JavaScript pushes notifications into
 * the toasts array and they auto-dismiss after a delay.
 *
 * @return void
 */
function vms_render_toast(): void {
	?>
	<div
		x-data="toastManager()"
		@toast.window="addToast($event.detail)"
		class="fixed top-20 right-4 z-[100] space-y-3 w-full max-w-sm pointer-events-none"
		aria-live="polite"
	>
		<template x-for="toast in toasts" :key="toast.id">
			<div
				x-show="toast.visible"
				x-transition:enter="transition ease-out duration-300"
				x-transition:enter-start="opacity-0 translate-x-8"
				x-transition:enter-end="opacity-100 translate-x-0"
				x-transition:leave="transition ease-in duration-200"
				x-transition:leave-start="opacity-100 translate-x-0"
				x-transition:leave-end="opacity-0 translate-x-8"
				class="pointer-events-auto bg-white dark:bg-gray-800 rounded-xl shadow-xl border p-4 flex items-start gap-3"
				:class="{
					'border-green-200 dark:border-green-800': toast.type === 'success',
					'border-red-200 dark:border-red-800': toast.type === 'error',
					'border-blue-200 dark:border-blue-800': toast.type === 'info',
					'border-yellow-200 dark:border-yellow-800': toast.type === 'warning'
				}"
			>
				<!-- Icon -->
				<div class="shrink-0 mt-0.5">
					<!-- Success -->
					<svg x-show="toast.type === 'success'" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
					</svg>
					<!-- Error -->
					<svg x-show="toast.type === 'error'" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
					</svg>
					<!-- Info -->
					<svg x-show="toast.type === 'info'" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
					</svg>
					<!-- Warning -->
					<svg x-show="toast.type === 'warning'" class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
					</svg>
				</div>

				<!-- Message -->
				<div class="flex-1 min-w-0">
					<p class="text-sm font-medium text-gray-900 dark:text-white" x-text="toast.title" x-show="toast.title"></p>
					<p class="text-sm text-gray-600 dark:text-gray-400" x-text="toast.message"></p>
				</div>

				<!-- Close -->
				<button
					@click="removeToast(toast.id)"
					class="shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
				>
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
					</svg>
				</button>
			</div>
		</template>
	</div>
	<?php
}

/**
 * Render the global modal container.
 *
 * Modals are managed via Alpine.js events. Dispatching a 'modal:open'
 * event with { title, body, onConfirm } opens the modal.
 *
 * @return void
 */
function vms_render_modal_container(): void {
	?>
	<div
		x-data="modalManager()"
		@modal-open.window="open($event.detail)"
		@modal-close.window="close()"
		@keydown.escape.window="close()"
	>
		<!-- Backdrop -->
		<div
			x-show="isOpen"
			x-cloak
			x-transition:enter="transition ease-out duration-200"
			x-transition:enter-start="opacity-0"
			x-transition:enter-end="opacity-100"
			x-transition:leave="transition ease-in duration-150"
			x-transition:leave-start="opacity-100"
			x-transition:leave-end="opacity-0"
			class="fixed inset-0 z-[200] bg-black/50 backdrop-blur-sm"
			@click="close()"
		></div>

		<!-- Modal -->
		<div
			x-show="isOpen"
			x-cloak
			x-transition:enter="transition ease-out duration-200"
			x-transition:enter-start="opacity-0 scale-95"
			x-transition:enter-end="opacity-100 scale-100"
			x-transition:leave="transition ease-in duration-150"
			x-transition:leave-start="opacity-100 scale-100"
			x-transition:leave-end="opacity-0 scale-95"
			class="fixed inset-0 z-[201] flex items-center justify-center p-4"
		>
			<div
				class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full overflow-hidden"
				:class="modalSize === 'lg' ? 'max-w-2xl' : (modalSize === 'xl' ? 'max-w-4xl' : 'max-w-md')"
				@click.stop
			>
				<!-- Header -->
				<div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
					<h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
					<button
						@click="close()"
						class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
					>
						<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
						</svg>
					</button>
				</div>

				<!-- Body -->
				<div class="px-6 py-4 max-h-[60vh] overflow-y-auto" x-html="body"></div>

				<!-- Footer -->
				<div x-show="showFooter" class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
					<button
						@click="close()"
						class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
					>
						<?php esc_html_e( 'Cancel', 'vms-theme' ); ?>
					</button>
					<button
						@click="confirm()"
						class="px-4 py-2 text-sm font-medium text-white bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 rounded-xl shadow-sm transition-colors"
						:class="confirmClass"
						x-text="confirmLabel"
					></button>
				</div>
			</div>
		</div>
	</div>
	<?php
}
