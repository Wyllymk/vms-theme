<?php
/**
 * Theme footer.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branding = vms_get_branding();
?>
		</main><!-- /.main-content -->

	<?php if ( is_user_logged_in() ) : ?>
	</div><!-- /.pt-16 flex -->
	<?php endif; ?>

	<?php vms_render_modal_container(); ?>

	<!-- Footer -->
	<footer class="<?php echo is_user_logged_in() ? 'lg:ml-64' : ''; ?> border-t border-gray-200 dark:border-gray-700/50 bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm">
		<div class="px-4 py-4 md:px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
			<p>
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
				<?php echo esc_html( $branding['club_name'] ); ?>.
				<?php esc_html_e( 'All rights reserved.', 'vms-theme' ); ?>
			</p>
			<p class="text-xs">
				<?php
				printf(
					/* translators: %s: VMS Theme version */
					esc_html__( 'VMS Theme v%s', 'vms-theme' ),
					esc_html( VMS_THEME_VERSION )
				);
				?>
			</p>
		</div>
	</footer>

	<?php wp_footer(); ?>
</body>
</html>
