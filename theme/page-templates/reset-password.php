<?php
/**
 * Template Name: VMS Reset Password
 *
 * Custom password reset page. Handles both the "enter new password" form
 * (when URL has ?key=&login= params) and a generic reset link page.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If user is already logged in, redirect to dashboard.
if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/vms-dashboard/' ) );
	exit;
}

// Check for reset key in URL.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$reset_key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$reset_login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
$has_reset   = ! empty( $reset_key ) && ! empty( $reset_login );

get_header();
?>

<div class="vms-login-page" x-data="resetPasswordApp()">
	<div class="vms-login-card" style="max-width:28rem;">
		<!-- Logo -->
		<div class="vms-login-logo">
			<?php
			$branding = vms_get_branding();
			if ( ! empty( $branding['club_logo_url'] ) ) :
				?>
				<img src="<?php echo esc_url( $branding['club_logo_url'] ); ?>" alt="<?php echo esc_attr( $branding['club_name'] ); ?>">
			<?php endif; ?>
			<h1><?php echo esc_html( $branding['club_name'] ); ?></h1>
		</div>

		<?php if ( $has_reset ) : ?>
		<!-- New Password Form -->
		<div>
			<h2 class="vms-text-lg vms-font-bold vms-text-center vms-mb-4"><?php esc_html_e( 'Set New Password', 'vms-theme' ); ?></h2>
			<p class="vms-text-sm vms-text-muted vms-text-center vms-mb-4">
				<?php esc_html_e( 'Enter your new password below.', 'vms-theme' ); ?>
			</p>

			<!-- Error/Success Messages -->
			<div x-show="message" x-cloak
				 :class="success ? 'vms-toast-success' : 'vms-toast-error'"
				 style="padding:0.75rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">
				<span x-text="message"></span>
			</div>

			<form @submit.prevent="submitNewPassword()">
				<input type="hidden" x-model="resetKey" value="<?php echo esc_attr( $reset_key ); ?>">
				<input type="hidden" x-model="resetLogin" value="<?php echo esc_attr( $reset_login ); ?>">

				<div class="vms-form-group">
					<label class="vms-label"><?php esc_html_e( 'New Password', 'vms-theme' ); ?></label>
					<div style="position:relative;">
						<input :type="showPassword ? 'text' : 'password'" class="vms-input" x-model="newPassword" required minlength="8"
							   placeholder="<?php esc_attr_e( 'Minimum 8 characters', 'vms-theme' ); ?>">
						<button type="button" @click="showPassword = !showPassword"
								style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--vms-text-muted);">
							<span x-text="showPassword ? '&#128065;' : '&#128064;'"></span>
						</button>
					</div>
				</div>

				<div class="vms-form-group">
					<label class="vms-label"><?php esc_html_e( 'Confirm Password', 'vms-theme' ); ?></label>
					<input :type="showPassword ? 'text' : 'password'" class="vms-input" x-model="confirmPassword" required>
				</div>

				<!-- Password Strength Indicator -->
				<div class="vms-mb-4" x-show="newPassword.length > 0">
					<div style="height:4px;border-radius:2px;background:var(--vms-border);overflow:hidden;">
						<div :style="'width:' + passwordStrength + '%;background:' + strengthColor" style="height:100%;transition:all 0.3s;"></div>
					</div>
					<span class="vms-text-xs" :style="'color:' + strengthColor" x-text="strengthLabel"></span>
				</div>

				<button type="submit" class="vms-btn vms-btn-primary vms-btn-block vms-btn-lg" :disabled="loading">
					<span x-show="loading" class="vms-spinner"></span>
					<span x-text="loading ? '<?php esc_attr_e( 'Resetting...', 'vms-theme' ); ?>' : '<?php esc_attr_e( 'Reset Password', 'vms-theme' ); ?>'"></span>
				</button>
			</form>

			<p class="vms-text-center vms-mt-4">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="vms-text-sm">
					<?php esc_html_e( 'Back to Login', 'vms-theme' ); ?>
				</a>
			</p>
		</div>

		<?php else : ?>
		<!-- Request Reset Form (fallback if no key) -->
		<div>
			<h2 class="vms-text-lg vms-font-bold vms-text-center vms-mb-4"><?php esc_html_e( 'Reset Your Password', 'vms-theme' ); ?></h2>
			<p class="vms-text-sm vms-text-muted vms-text-center vms-mb-4">
				<?php esc_html_e( 'Enter your email or username and we\'ll send you a reset link.', 'vms-theme' ); ?>
			</p>

			<div x-show="message" x-cloak
				 :class="success ? 'vms-toast-success' : 'vms-toast-error'"
				 style="padding:0.75rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">
				<span x-text="message"></span>
			</div>

			<form @submit.prevent="requestReset()">
				<div class="vms-form-group">
					<label class="vms-label"><?php esc_html_e( 'Email or Username', 'vms-theme' ); ?></label>
					<input type="text" class="vms-input" x-model="userLogin" required
						   placeholder="<?php esc_attr_e( 'Enter your email or username', 'vms-theme' ); ?>">
				</div>

				<button type="submit" class="vms-btn vms-btn-primary vms-btn-block vms-btn-lg" :disabled="loading">
					<span x-show="loading" class="vms-spinner"></span>
					<span x-text="loading ? '<?php esc_attr_e( 'Sending...', 'vms-theme' ); ?>' : '<?php esc_attr_e( 'Send Reset Link', 'vms-theme' ); ?>'"></span>
				</button>
			</form>

			<p class="vms-text-center vms-mt-4">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="vms-text-sm">
					<?php esc_html_e( 'Back to Login', 'vms-theme' ); ?>
				</a>
			</p>
		</div>
		<?php endif; ?>

		<!-- Theme Toggle -->
		<div class="vms-text-center vms-mt-6" x-data="themeManager()">
			<div class="vms-theme-toggle" style="display:inline-flex;">
				<button @click="setTheme('light')" :class="{ 'active': theme === 'light' }" title="<?php esc_attr_e( 'Light', 'vms-theme' ); ?>">&#9728;</button>
				<button @click="setTheme('dark')" :class="{ 'active': theme === 'dark' }" title="<?php esc_attr_e( 'Dark', 'vms-theme' ); ?>">&#9790;</button>
				<button @click="setTheme('system')" :class="{ 'active': theme === 'system' }" title="<?php esc_attr_e( 'System', 'vms-theme' ); ?>">&#9881;</button>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>
