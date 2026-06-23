<?php
/**
 * Custom 404 page.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="min-h-[60vh] flex items-center justify-center p-4">
	<div class="text-center max-w-md">
		<!-- 404 Illustration -->
		<div class="relative mb-8">
			<span class="text-9xl font-black text-gray-200 dark:text-gray-800 select-none">404</span>
			<div class="absolute inset-0 flex items-center justify-center">
				<svg class="w-24 h-24 text-[var(--vms-primary)]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
				</svg>
			</div>
		</div>

		<h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
			<?php esc_html_e( 'Page Not Found', 'vms-theme' ); ?>
		</h1>
		<p class="text-gray-500 dark:text-gray-400 mb-8">
			<?php esc_html_e( 'Sorry, the page you are looking for does not exist or has been moved.', 'vms-theme' ); ?>
		</p>

		<div class="flex flex-col sm:flex-row items-center justify-center gap-3">
			<?php if ( is_user_logged_in() ) : ?>
				<a
					href="<?php echo esc_url( vms_get_dashboard_url_for_role( vms_current_role() ) ); ?>"
					class="inline-flex items-center gap-2 px-6 py-2.5 bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 text-white font-medium rounded-xl shadow-lg shadow-[var(--vms-primary)]/25 transition-all duration-200"
				>
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
					</svg>
					<?php esc_html_e( 'Go to Dashboard', 'vms-theme' ); ?>
				</a>
			<?php else : ?>
				<a
					href="<?php echo esc_url( home_url( '/' ) ); ?>"
					class="inline-flex items-center gap-2 px-6 py-2.5 bg-[var(--vms-primary)] hover:bg-[var(--vms-primary)]/90 text-white font-medium rounded-xl shadow-lg shadow-[var(--vms-primary)]/25 transition-all duration-200"
				>
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
					</svg>
					<?php esc_html_e( 'Go Home', 'vms-theme' ); ?>
				</a>
			<?php endif; ?>

			<button
				onclick="history.back()"
				class="inline-flex items-center gap-2 px-6 py-2.5 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
			>
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
				</svg>
				<?php esc_html_e( 'Go Back', 'vms-theme' ); ?>
			</button>
		</div>
	</div>
</div>

<?php
get_footer();
