<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
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
						 random strings you could use: <kbd>%s</kbd>, <kbd>%s</kbd>, <kbd>%s</kbd>.' ),
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
						<?php _e( 'Mail Server' ) ?>
					</label>
				</th>
				<td>
					<input name="post_by_email_options[mailserver_url]" type="text" id="mailserver_url" value="<?php echo esc_attr( $options[mailserver_url] ); ?>" class="regular-text ltr" />
					<label for="post_by_email_options[mailserver_port]">
						<?php _e( 'Port' ) ?>
					</label>
					<input name="post_by_email_options[mailserver_port]" type="text" id="mailserver_port" value="<?php echo esc_attr( $options[mailserver_port] ); ?>" class="small-text" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[mailserver_login]">
						<?php _e( 'Login Name' ) ?>
					</label>
				</th>
				<td>
					<input name="post_by_email_options[mailserver_login]" type="text" id="mailserver_login" value="<?php echo esc_attr( $options[mailserver_login] ); ?>" class="regular-text ltr" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[mailserver_pass]">
						<?php _e( 'Password' ) ?>
					</label>
				</th>
				<td>
					<input name="post_by_email_options[mailserver_pass]" type="password" id="mailserver_pass" value="<?php echo esc_attr( $options[mailserver_pass] ); ?>" class="regular-text ltr" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="post_by_email_options[default_email_category]">
						<?php _e( 'Default Mail Category' ) ?>
					</label>
				</th>
				<td>
					<?php
						wp_dropdown_categories( array( 'hide_empty' => 0,
													   'name' => 'default_email_category',
													   'orderby' => 'name',
													   'selected' => $options['default_email_category'],
													   'hierarchical' => true
											         ) );
					?>
				</td>
			</tr>
		</table>


		<?php submit_button(); ?>
	</form>
</div>