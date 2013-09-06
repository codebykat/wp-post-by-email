<?php
/**
 * The view for the administration dashboard.
 *
 * This includes the options, log messages, and button to check for new mail.
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      http://codebykat.wordpress.com
 * @copyright 2013 Kat Hagan
 */
?>
<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<p>
		<?php
			printf( __( 'To post to WordPress by e-mail you must set up a secret e-mail account
						 with POP3 access. Any mail received at this address will be posted, so
						 it&#8217;s a good idea to keep this address very secret. Here are three
						 random strings you could use: <kbd>%s</kbd>, <kbd>%s</kbd>, <kbd>%s</kbd>.',
						 'post-by-email' ),
					wp_generate_password( 8, false ),
					wp_generate_password( 8, false ),
					wp_generate_password( 8, false ) )
		?>
	</p>

	<form method="post" action="options.php">
		<?php settings_fields( 'post_by_email_options' ); ?>

		<?php $options = get_option( 'post_by_email_options' ); ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[mailserver_url]">
						<?php _e( 'Mail Server', 'post-by-email' ); ?>
					</label>
				</th>
				<td>
					<input name="post_by_email_options[mailserver_url]" type="text" id="mailserver_url" value="<?php echo esc_attr( $options['mailserver_url'] ); ?>" class="regular-text ltr" />
					<label for="post_by_email_options[mailserver_port]">
						<?php _e( 'Port', 'post-by-email' ); ?>
					</label>
					<input name="post_by_email_options[mailserver_port]" type="text" id="mailserver_port" value="<?php echo esc_attr( $options['mailserver_port'] ); ?>" class="small-text" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[ssl]">
						<?php _e( 'Always use secure connection (SSL)?', 'post-by-email' ); ?>
					</label>
				</th>
				<td>
					<input name="post_by_email_options[ssl]" type="checkbox" id="ssl" <?php if( $options['ssl'] ) { echo 'checked="checked"'; } ?> />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[mailserver_login]">
						<?php _e( 'Login Name', 'post-by-email' ); ?>
					</label>
				</th>
				<td>
					<input name="post_by_email_options[mailserver_login]" type="text" id="mailserver_login" value="<?php echo esc_attr( $options['mailserver_login'] ); ?>" class="regular-text ltr" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[mailserver_pass]">
						<?php _e( 'Password', 'post-by-email' ); ?>
					</label>
				</th>
				<td>
					<input name="post_by_email_options[mailserver_pass]" type="password" id="mailserver_pass" value="<?php echo esc_attr( $options['mailserver_pass'] ); ?>" class="regular-text ltr" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[default_email_category]">
						<?php _e( 'Default Mail Category', 'post-by-email' ); ?>
					</label>
				</th>
				<td>
					<?php
						wp_dropdown_categories( array(	'hide_empty' => 0,
														'name' => 'post_by_email_options[default_email_category]',
														'orderby' => 'name',
														'selected' => $options['default_email_category'],
														'hierarchical' => true
													) );
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[delete_messages]">
						<?php _e( 'Delete messages after posting?', 'post-by-email' ); ?>
					</label>
				</th>
				<td>
					<input name="post_by_email_options[delete_messages]" type="checkbox" id="delete_messages" <?php if( $options['delete_messages'] ) { echo 'checked="checked"'; } ?> />
				</td>
			</tr>
		</table>


		<?php submit_button(); ?>
	</form>

	<h3><?php _e( 'Activity Log', 'post-by-email' ); ?></h3>
	<?php
		$options = get_option( 'post_by_email_options' );
		$log = get_option( 'post_by_email_log' );
	?>
	<p>
		<?php _e( 'Last checked for new mail:', 'post-by-email' ); ?>
		<?php
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
		?>
		<?php if( isset( $options['last_checked'] ) ) : ?>
			<?php echo date_i18n( "$date_format, $time_format", $options['last_checked'] ); ?>
		<?php else: ?>
			<?php _e( 'Never', 'post-by-email' ); ?>
		<?php endif; ?>
		<br />
		<?php _e( 'Next scheduled check:', 'post-by-email' ); ?>
		<?php
			$next = wp_next_scheduled( 'post-by-email-wp-mail.php' );
			echo get_date_from_gmt( date( 'Y-m-d H:i:s', $next ) , "$date_format, $time_format" );
		?>
	</p>
	<p>
		<a href="<?php echo site_url( 'wp-mail.php' ); ?>" class="button-secondary">
			<?php _e( 'Check now', 'post-by-email' ); ?>
		</a>
	</p>
	<?php if( $log && sizeof($log) > 0 ) : ?>

		<p>
			<a href="" id="clearLog" ><?php _e('Clear Log', 'post-by-email' ); ?></a>

			<script type="text/javascript" >
			jQuery('a#clearLog').click(function(e) {

				var data = {
					action: 'post_by_email_clear_log',
					security: '<?php echo wp_create_nonce("post-by-email-clear-log"); ?>'
				};

				jQuery.post(ajaxurl, data, function(response) {
					jQuery('table#logTable').hide();
					jQuery('a#clearLog').hide();
				});

				e.preventDefault();

			});
			</script>

		</p>

		<table id="logTable" class="widefat fixed" cellspacing="0">
			<thead>
				<tr>
					<th colspan='2'><?php _e('Log Messages', 'post-by-email' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $log as $entry ) : ?>
					<tr class="alternate">
						<td><?php echo date_i18n( "$date_format, $time_format", $entry['timestamp'] ); ?></td>
						<td><?php echo $entry['message']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>