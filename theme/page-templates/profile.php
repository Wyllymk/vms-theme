<?php
/**
 * Template Name: VMS Profile
 *
 * User profile management page.
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

get_header();
?>

<div class="vms-layout" x-data="memberProfileApp()">
	<?php get_sidebar(); ?>

	<div class="vms-main">
		<div class="vms-navbar">
			<h1 class="vms-text-xl vms-font-bold"><?php esc_html_e( 'My Profile', 'vms-theme' ); ?></h1>
		</div>

		<div class="vms-grid vms-grid-2 vms-mt-4" style="max-width:56rem;">
			<!-- Profile Information -->
			<div class="vms-card">
				<div class="vms-card-header">
					<h2 class="vms-text-lg vms-font-bold"><?php esc_html_e( 'Profile Information', 'vms-theme' ); ?></h2>
				</div>

				<form @submit.prevent="updateProfile()">
					<div class="vms-form-group">
						<label class="vms-label"><?php esc_html_e( 'Display Name', 'vms-theme' ); ?></label>
						<input type="text" class="vms-input" x-model="profile.display_name" required>
					</div>

					<div class="vms-form-group">
						<label class="vms-label"><?php esc_html_e( 'First Name', 'vms-theme' ); ?></label>
						<input type="text" class="vms-input" x-model="profile.first_name">
					</div>

					<div class="vms-form-group">
						<label class="vms-label"><?php esc_html_e( 'Last Name', 'vms-theme' ); ?></label>
						<input type="text" class="vms-input" x-model="profile.last_name">
					</div>

					<div class="vms-form-group">
						<label class="vms-label"><?php esc_html_e( 'Email', 'vms-theme' ); ?></label>
						<input type="email" class="vms-input" x-model="profile.email" required>
					</div>

					<div class="vms-form-group">
						<label class="vms-label"><?php esc_html_e( 'Phone Number', 'vms-theme' ); ?></label>
						<input type="tel" class="vms-input" x-model="profile.phone" placeholder="+254...">
					</div>

					<div class="vms-form-group">
						<label class="vms-flex vms-items-center vms-gap-2">
							<input type="checkbox" x-model="profile.receive_sms">
							<span class="vms-text-sm"><?php esc_html_e( 'Receive SMS notifications', 'vms-theme' ); ?></span>
						</label>
					</div>

					<div class="vms-form-group">
						<label class="vms-flex vms-items-center vms-gap-2">
							<input type="checkbox" x-model="profile.receive_email">
							<span class="vms-text-sm"><?php esc_html_e( 'Receive email notifications', 'vms-theme' ); ?></span>
						</label>
					</div>

					<button type="submit" class="vms-btn vms-btn-primary" :disabled="saving">
						<span x-show="saving" class="vms-spinner"></span>
						<span x-text="saving ? '<?php esc_attr_e( 'Saving...', 'vms-theme' ); ?>' : '<?php esc_attr_e( 'Update Profile', 'vms-theme' ); ?>'"></span>
					</button>
				</form>
			</div>

			<!-- Change Password -->
			<div class="vms-card">
				<div class="vms-card-header">
					<h2 class="vms-text-lg vms-font-bold"><?php esc_html_e( 'Change Password', 'vms-theme' ); ?></h2>
				</div>

				<form @submit.prevent="changePassword()">
					<div class="vms-form-group">
						<label class="vms-label"><?php esc_html_e( 'Current Password', 'vms-theme' ); ?></label>
						<input type="password" class="vms-input" x-model="passwords.current" required>
					</div>

					<div class="vms-form-group">
						<label class="vms-label"><?php esc_html_e( 'New Password', 'vms-theme' ); ?></label>
						<input type="password" class="vms-input" x-model="passwords.new_password" required minlength="8">
					</div>

					<div class="vms-form-group">
						<label class="vms-label"><?php esc_html_e( 'Confirm New Password', 'vms-theme' ); ?></label>
						<input type="password" class="vms-input" x-model="passwords.confirm" required>
					</div>

					<button type="submit" class="vms-btn vms-btn-primary" :disabled="changingPassword">
						<span x-show="changingPassword" class="vms-spinner"></span>
						<span x-text="changingPassword ? '<?php esc_attr_e( 'Changing...', 'vms-theme' ); ?>' : '<?php esc_attr_e( 'Change Password', 'vms-theme' ); ?>'"></span>
					</button>
				</form>
			</div>
		</div>

		<!-- Account Info -->
		<div class="vms-card vms-mt-4" style="max-width:56rem;">
			<div class="vms-card-header">
				<h2 class="vms-text-lg vms-font-bold"><?php esc_html_e( 'Account Information', 'vms-theme' ); ?></h2>
			</div>
			<table class="vms-table">
				<tbody>
					<tr>
						<td class="vms-font-bold" style="width:200px;"><?php esc_html_e( 'Username', 'vms-theme' ); ?></td>
						<td><?php echo esc_html( wp_get_current_user()->user_login ); ?></td>
					</tr>
					<tr>
						<td class="vms-font-bold"><?php esc_html_e( 'Role', 'vms-theme' ); ?></td>
						<td><span class="vms-badge vms-badge-info"><?php echo esc_html( ucwords( str_replace( '_', ' ', vms_current_role() ?? 'None' ) ) ); ?></span></td>
					</tr>
					<tr>
						<td class="vms-font-bold"><?php esc_html_e( 'Member Since', 'vms-theme' ); ?></td>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( wp_get_current_user()->user_registered ) ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php get_footer(); ?>
