<?php
/**
 * Default template — fallback for any content type.
 *
 * @package VMS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="max-w-4xl mx-auto">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 md:p-8 mb-6' ); ?>>
				<header class="mb-6">
					<h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
						<?php the_title(); ?>
					</h1>
					<?php if ( is_singular() && 'page' !== get_post_type() ) : ?>
						<div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
							<?php
							printf(
								/* translators: %s: post date */
								esc_html__( 'Published on %s', 'vms-theme' ),
								esc_html( get_the_date() )
							);
							?>
						</div>
					<?php endif; ?>
				</header>

				<div class="prose dark:prose-invert max-w-none">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>

		<?php if ( ! is_singular() ) : ?>
			<nav class="flex items-center justify-between mt-6">
				<div class="text-sm">
					<?php previous_posts_link( __( '&larr; Newer Posts', 'vms-theme' ) ); ?>
				</div>
				<div class="text-sm">
					<?php next_posts_link( __( 'Older Posts &rarr;', 'vms-theme' ) ); ?>
				</div>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-8 text-center">
			<svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
			</svg>
			<h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
				<?php esc_html_e( 'No Content Found', 'vms-theme' ); ?>
			</h2>
			<p class="text-gray-500 dark:text-gray-400">
				<?php esc_html_e( 'There is nothing to display here yet.', 'vms-theme' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
