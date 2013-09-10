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

	<?php if( isset( $_GET['settings-updated'] ) ) : ?>
		<div class='updated'>
			<p><?php _e( 'Your settings have been saved.', 'post-by-email' ); ?></p>
		</div>
	<?php endif; ?>

	<?php $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'main'; ?>

	<h2 class="nav-tab-wrapper">
		<a id="nav-main" href="<?php echo admin_url('tools.php?page='.$_GET['page']); ?>" class="nav-tab <?php if( 'main' == $tab ) { echo 'nav-tab-active'; } ?>">
			<?php _e( 'Basic Settings', 'post-by-email' ); ?>
		</a>
		<a id="nav-connection" href="<?php echo admin_url('tools.php?page='.$_GET['page'].'&tab=connection'); ?>" class="nav-tab <?php if( 'connection' == $tab ) { echo 'nav-tab-active'; } ?>">
			<?php _e( 'Mailbox Details', 'post-by-email' ); ?>
		</a>
		<a id="nav-log" href="<?php echo admin_url('tools.php?page='.$_GET['page'].'&tab=log'); ?>" class="nav-tab <?php if( 'log' == $tab ) { echo 'nav-tab-active'; } ?>">
			<?php _e( 'Activity Log', 'post-by-email' ); ?>
		</a>
	</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'post_by_email_options' ); ?>

		<?php $options = get_option( 'post_by_email_options' ); ?>

		<div class='tab-content' id='tab-main' <?php if( 'main' != $tab ) { echo 'style="display:none;"'; } ?>>
			<p>
				<?php
					printf( __( 'To post to WordPress by e-mail you must set up a secret e-mail account
								 with IMAP or POP3 access. Any mail received at this address will be posted, so
								 it&#8217;s a good idea to keep this address very secret. Here are three
								 random strings you could use: <kbd>%s</kbd>, <kbd>%s</kbd>, <kbd>%s</kbd>.',
								 'post-by-email' ),
							wp_generate_password( 8, false ),
							wp_generate_password( 8, false ),
							wp_generate_password( 8, false ) )
				?>
			</p>

			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="post_by_email_options[mailserver_url]">
							<?php _e( 'Mail Server', 'post-by-email' ); ?>
						</label>
					</th>
					<td>
						<input name="post_by_email_options[mailserver_url]" type="text" id="mailserver_url" value="<?php echo esc_attr( $options['mailserver_url'] ); ?>" class="regular-text ltr" />
						<p class="description">
							<?php _e( 'The address of the incoming mail server (IMAP or POP3).', 'post-by-email'); ?>
						</p>
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
			</table>


			<?php submit_button(); ?>
		</div>

		<div class='tab-content' id='tab-connection' <?php if( 'connection' != $tab ) { echo 'style="display:none;"'; } ?>>

			<p><?php _e( "Configure the details of your mailbox connection.  The default settings should work with most email accounts, but if your mail server differs, you can enter it here.", 'post-by-email' ); ?></p>

			<table class="form-table">
				<tr valign="top">
					<th>
						<label for="post_by_email_options[mailserver_protocol]">
							<?php _e( 'Protocol', 'post-by-email' ); ?>
						</label>
					</th>
					<td>
						<select name="post_by_email_options[mailserver_protocol]" id="post_by_email_options[mailserver_protocol]">
							<option value="POP3" <?php if( 'POP3' == $options['mailserver_protocol'] ) { echo 'selected'; } ?>>POP3</option>
							<option value="IMAP" <?php if( 'IMAP' == $options['mailserver_protocol'] ) { echo 'selected'; } ?>>IMAP</option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th>
						<label for="post_by_email_options[mailserver_port]">
							<?php _e( 'Port', 'post-by-email' ); ?>
						</label>
					</th>
					<td>
						<input name="post_by_email_options[mailserver_port]" id="post_by_email_options[mailserver_port]" type="text" id="mailserver_port" value="<?php echo esc_attr( $options['mailserver_port'] ); ?>" class="small-text" />
						<p class="description">
							<?php _e( "Common port numbers: 143 (IMAP), 993 (IMAP/SSL), 110 (POP3), 995 (POP3/SSL).", 'post-by-email' ); ?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="post_by_email_options[ssl]">
							<?php _e( 'Always use secure connection (SSL)?', 'post-by-email' ); ?>
						</label>
					</th>
					<td>
						<input name="post_by_email_options[ssl]" id="post_by_email_options[ssl]" type="checkbox" id="ssl" <?php if( $options['ssl'] ) { echo 'checked="checked"'; } ?> />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="post_by_email_options[delete_messages]" >
							<?php _e( 'Delete messages after posting?', 'post-by-email' ); ?>
						</label>
					</th>
					<td>
						<input name="post_by_email_options[delete_messages]" id="post_by_email_options[delete_messages]" type="checkbox" id="delete_messages" <?php if( $options['delete_messages'] ) { echo 'checked="checked"'; } ?> />
						<p class="description"><?php _e( 'Uncheck this box to mark messages as read instead of deleting (requires IMAP).', 'post-by-email' ); ?></p>
					</td>
				</tr>
			</table>

			<br />
			<input type="button" id="resetButton" class="button-secondary" value="<?php _e( 'Reset to Defaults', 'post-by-email'); ?>" />
			<?php submit_button(); ?>
		</div>
	</form>

	<div class='tab-content' id='tab-log' <?php if( 'log' != $tab ) { echo 'style="display:none;"'; } ?>>

		<?php
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
</div>