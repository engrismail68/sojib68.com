<?php
/**
 * GitHub-based theme sync/updater for the Sojib68 theme.
 *
 * Adds an "Appearance > Theme Sync" admin page that shows the currently
 * installed commit, checks GitHub for the latest commit on the deploy
 * branch, and lets an administrator pull the latest code with one click.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_GH_OWNER', 'engrismail68' );
define( 'SOJIB68_GH_REPO', 'sojib68.com' );
define( 'SOJIB68_GH_BRANCH', 'main' );

add_action( 'admin_menu', 'sojib68_register_sync_page' );
function sojib68_register_sync_page() {
	add_theme_page(
		__( 'Theme Sync', 'sojib68' ),
		__( 'Theme Sync', 'sojib68' ),
		'manage_options',
		'sojib68-theme-sync',
		'sojib68_render_sync_page'
	);
}

/**
 * Fetch the latest commit info for the deploy branch from GitHub's API.
 *
 * @return array|WP_Error
 */
function sojib68_get_latest_commit() {
	$url = sprintf(
		'https://api.github.com/repos/%s/%s/commits/%s',
		SOJIB68_GH_OWNER,
		SOJIB68_GH_REPO,
		SOJIB68_GH_BRANCH
	);

	$response = wp_remote_get( $url, array(
		'headers' => array(
			'User-Agent' => 'Sojib68-Theme-Sync/1.0',
			'Accept'     => 'application/vnd.github+json',
		),
		'timeout' => 15,
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return new WP_Error(
			'github_api_error',
			sprintf(
				/* translators: %d: HTTP status code */
				__( 'GitHub API returned status %d.', 'sojib68' ),
				$code
			)
		);
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body['sha'] ) ) {
		return new WP_Error( 'github_api_error', __( 'Unexpected response from GitHub.', 'sojib68' ) );
	}

	return array(
		'sha'     => $body['sha'],
		'message' => isset( $body['commit']['message'] ) ? $body['commit']['message'] : '',
		'date'    => isset( $body['commit']['committer']['date'] ) ? $body['commit']['committer']['date'] : '',
		'author'  => isset( $body['commit']['committer']['name'] ) ? $body['commit']['committer']['name'] : '',
	);
}

function sojib68_render_sync_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$current_commit = get_option( 'sojib68_current_commit', '' );

	$latest = get_transient( 'sojib68_latest_commit' );
	$error  = null;

	if ( ! $latest || isset( $_GET['checked'] ) ) {
		$result = sojib68_get_latest_commit();
		if ( is_wp_error( $result ) ) {
			$error = $result->get_error_message();
		} else {
			$latest = $result;
			set_transient( 'sojib68_latest_commit', $latest, 5 * MINUTE_IN_SECONDS );
		}
	}

	$update_available = $latest && $current_commit !== $latest['sha'];
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Sojib68 Theme Sync', 'sojib68' ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: 1: GitHub owner/repo, 2: branch name */
				esc_html__( 'Syncs this theme from %1$s (branch: %2$s).', 'sojib68' ),
				'<code>' . esc_html( SOJIB68_GH_OWNER . '/' . SOJIB68_GH_REPO ) . '</code>',
				'<code>' . esc_html( SOJIB68_GH_BRANCH ) . '</code>'
			);
			?>
		</p>

		<?php if ( isset( $_GET['synced'] ) ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Theme updated successfully.', 'sojib68' ); ?></p></div>
		<?php endif; ?>

		<?php if ( isset( $_GET['sync_error'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( wp_unslash( rawurldecode( $_GET['sync_error'] ) ) ); ?></p></div>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Currently installed commit', 'sojib68' ); ?></th>
				<td>
					<code><?php echo $current_commit ? esc_html( substr( $current_commit, 0, 7 ) ) : esc_html__( 'Unknown', 'sojib68' ); ?></code>
				</td>
			</tr>
			<?php if ( $latest ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Latest commit on GitHub', 'sojib68' ); ?></th>
				<td>
					<code><?php echo esc_html( substr( $latest['sha'], 0, 7 ) ); ?></code>
					&mdash; <?php echo esc_html( $latest['message'] ); ?>
					<br>
					<small><?php echo esc_html( $latest['author'] ); ?>, <?php echo esc_html( $latest['date'] ); ?></small>
				</td>
			</tr>
			<?php endif; ?>
		</table>

		<p>
			<a href="<?php echo esc_url( add_query_arg( 'checked', '1' ) ); ?>" class="button">
				<?php esc_html_e( 'Check for Updates', 'sojib68' ); ?>
			</a>

			<?php if ( $update_available ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:8px;">
					<?php wp_nonce_field( 'sojib68_sync_theme' ); ?>
					<input type="hidden" name="action" value="sojib68_sync_theme">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Update Now', 'sojib68' ); ?>
					</button>
				</form>
			<?php elseif ( $latest ) : ?>
				<span style="margin-left:8px;color:#46b450;"><?php esc_html_e( 'Theme is up to date.', 'sojib68' ); ?></span>
			<?php endif; ?>
		</p>
	</div>
	<?php
}

add_action( 'admin_post_sojib68_sync_theme', 'sojib68_handle_sync_theme' );
function sojib68_handle_sync_theme() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'sojib68' ) );
	}
	check_admin_referer( 'sojib68_sync_theme' );

	$redirect = admin_url( 'themes.php?page=sojib68-theme-sync' );

	$latest = sojib68_get_latest_commit();
	if ( is_wp_error( $latest ) ) {
		wp_safe_redirect( add_query_arg( 'sync_error', rawurlencode( $latest->get_error_message() ), $redirect ) );
		exit;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	$creds = request_filesystem_credentials( $redirect, '', false, false, null );
	if ( false === $creds || ! WP_Filesystem( $creds ) ) {
		wp_safe_redirect( add_query_arg( 'sync_error', rawurlencode( __( 'Could not access the filesystem. Check file permissions.', 'sojib68' ) ), $redirect ) );
		exit;
	}

	global $wp_filesystem;

	$zip_url = sprintf(
		'https://github.com/%s/%s/archive/refs/heads/%s.zip',
		SOJIB68_GH_OWNER,
		SOJIB68_GH_REPO,
		SOJIB68_GH_BRANCH
	);

	$tmp_file = download_url( $zip_url, 60 );
	if ( is_wp_error( $tmp_file ) ) {
		wp_safe_redirect( add_query_arg( 'sync_error', rawurlencode( $tmp_file->get_error_message() ), $redirect ) );
		exit;
	}

	$tmp_extract_dir = trailingslashit( get_temp_dir() ) . 'sojib68-sync-' . time();
	$unzip_result     = unzip_file( $tmp_file, $tmp_extract_dir );
	@unlink( $tmp_file );

	if ( is_wp_error( $unzip_result ) ) {
		wp_safe_redirect( add_query_arg( 'sync_error', rawurlencode( $unzip_result->get_error_message() ), $redirect ) );
		exit;
	}

	$extracted_items = glob( trailingslashit( $tmp_extract_dir ) . '*', GLOB_ONLYDIR );
	if ( empty( $extracted_items ) ) {
		wp_safe_redirect( add_query_arg( 'sync_error', rawurlencode( __( 'Could not locate extracted files.', 'sojib68' ) ), $redirect ) );
		exit;
	}
	$source_dir = $extracted_items[0];

	$theme_dir = trailingslashit( get_theme_root() ) . get_stylesheet();

	$copy_result = copy_dir( $source_dir, $theme_dir );
	$wp_filesystem->delete( $tmp_extract_dir, true );

	if ( is_wp_error( $copy_result ) ) {
		wp_safe_redirect( add_query_arg( 'sync_error', rawurlencode( $copy_result->get_error_message() ), $redirect ) );
		exit;
	}

	update_option( 'sojib68_current_commit', $latest['sha'] );
	delete_transient( 'sojib68_latest_commit' );

	wp_safe_redirect( add_query_arg( 'synced', '1', $redirect ) );
	exit;
}
