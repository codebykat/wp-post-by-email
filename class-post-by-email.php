<?php
/**
 * Post By Email
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      http://codebykat.wordpress.com
 * @copyright 2013 Kat Hagan
 */

/**
 * Plugin class.
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 */
class Post_By_Email {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.9.0
	 *
	 * @var     string
	 */
	protected $version = '0.9.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    0.9.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'post-by-email';

	/**
	 * Instance of this class.
	 *
	 * @since    0.9.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.9.0
	 */
	private function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Add the options page and menu item.
		add_action( 'admin_init', array( $this, 'add_plugin_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// add hook to check for mail
		add_action( 'wp-mail.php', array( 'Post_By_Email', 'check_email' ) );

		// disable post by email settings on Settings->Writing page
		// NOTE: this requires the check be removed from wp-mail.php
		add_filter( 'enable_post_by_email_configuration', '__return_false' );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.9.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.9.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	function activate( $network_wide ) {
		// set up plugin options
		$options = get_option( 'post_by_email_options' );

		if( ! $options ) {
			$options = Array();

			// if old global options exist, copy them into plugin options
			// WP_MAIL_INTERVAL - interval to check new messages

			$plugin_options = Array(
				'mailserver_url',
				'mailserver_port',
				'mailserver_login',
				'mailserver_pass',
				'default_email_category'
			);

			foreach( $plugin_options as $optname ) {
				$options[ $optname ] = get_option( $optname );
				//delete_option( $optname );			
			}

			update_option( 'post_by_email_options', $options );
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.9.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		remove_filter( 'enable_post_by_email_configuration', '__return_false' );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.9.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Register the settings.
	 *
	 * @since    0.9.0
	 */
	public function add_plugin_settings() {
		register_setting( 'post_by_email_options', 'post_by_email_options', array( $this, 'post_by_email_validate' ) );
	}

	public function post_by_email_validate($input) {
		// TODO
		return $input;
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.9.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_plugins_page(
			__( 'Post By Email', $this->plugin_slug ),
			__( 'Post By Email', $this->plugin_slug ),
			'read',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.9.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Check for new messages and post them to the blog.
	 *
	 * @since    0.9.0
	 */
	public function check_email() {

		/** Get the POP3 class with which to access the mailbox. */
		require_once( ABSPATH . WPINC . '/class-pop3.php' );

		/** Only check at this interval for new messages. */
		if ( ! defined( 'WP_MAIL_INTERVAL' ) )
			define( 'WP_MAIL_INTERVAL', 300 ); // 5 minutes

		$last_checked = get_transient( 'mailserver_last_checked' );

		if ( $last_checked )
			wp_die( __( 'Slow down cowboy, no need to check for new mails so often!' ) );

		set_transient( 'mailserver_last_checked', true, WP_MAIL_INTERVAL );

		$options = get_option( 'post_by_email_options' );
		/* TODO validate that options are set */

		$log = array();
		$log['last_checked'] = current_time( 'mysql' );
		$log['messages'] = array();

		$time_difference = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

		$phone_delim = '::';

		$pop3 = new POP3();

		if ( ! $pop3->connect( $options['mailserver_url'], $options['mailserver_port'] ) || ! $pop3->user( $options['mailserver_login'] ) )
			self::save_log_and_die( __( 'An error occurred: ') . esc_html( $pop3->ERROR ), $log );

		$count = $pop3->pass( $options['mailserver_pass'] );

		if( false === $count )
			self::save_log_and_die( __( 'An error occurred: ') . esc_html( $pop3->ERROR ), $log );

		if( 0 === $count ) {
			$pop3->quit();
			self::save_log_and_die( __( 'There doesn&#8217;t seem to be any new mail.' ), $log );
		}

		for ( $i = 1; $i <= $count; $i++ ) {

			$message = $pop3->get( $i );

			$bodysignal = false;
			$boundary = '';
			$charset = '';
			$content = '';
			$content_type = '';
			$content_transfer_encoding = '';
			$post_author = 1;
			$author_found = false;
			$dmonths = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
			foreach ( $message as $line ) {
				// body signal
				if ( strlen( $line ) < 3 )
					$bodysignal = true;
				if ( $bodysignal ) {
					$content .= $line;
				} else {
					if ( preg_match( '/Content-Type: /i', $line ) ) {
						$content_type = trim( $line );
						$content_type = substr( $content_type, 14, strlen( $content_type ) - 14 );
						$content_type = explode( ';', $content_type );
						print_r($content_type);
						if ( ! empty( $content_type[1] ) ) {
							$charset = explode( '=', $content_type[1] );
							$charset = ( ! empty( $charset[1] ) ) ? trim( $charset[1] ) : '';
						}
						$content_type = $content_type[0];
					}
					if ( preg_match( '/Content-Transfer-Encoding: /i', $line ) ) {
						$content_transfer_encoding = trim( $line );
						$content_transfer_encoding = substr( $content_transfer_encoding, 27, strlen( $content_transfer_encoding ) - 27 );
						$content_transfer_encoding = explode( ';', $content_transfer_encoding );
						$content_transfer_encoding = $content_transfer_encoding[0];
					}
					if ( ( 'multipart/alternative' == $content_type ) && ( false !== strpos( $line, 'boundary="' ) ) && ( '' == $boundary ) ) {
						$boundary = trim( $line );
						$boundary = explode( '"', $boundary );
						$boundary = $boundary[1];
					}
					if ( preg_match( '/Subject: /i', $line ) ) {
						$subject = trim( $line );
						$subject = substr( $subject, 9, strlen( $subject ) - 9 );
						// Captures any text in the subject before $phone_delim as the subject
						if ( function_exists( 'iconv_mime_decode' ) ) {
							$subject = iconv_mime_decode( $subject, 2, get_option( 'blog_charset' ) );
						} else {
							$subject = wp_iso_descrambler( $subject );
						}
						$subject = explode( $phone_delim, $subject );
						$subject = $subject[0];
					}

					// Set the author using the email address (From or Reply-To, the last used)
					// otherwise use the site admin
					if ( ! $author_found && preg_match( '/^(From|Reply-To): /', $line ) ) {
						if ( preg_match( '|[a-z0-9_.-]+@[a-z0-9_.-]+(?!.*<)|i', $line, $matches ) )
							$author = $matches[0];
						else
							$author = trim( $line );
						$author = sanitize_email( $author );
						if ( is_email( $author ) ) {
							$log['messages'][] = '<p>' . sprintf( __( 'Author is %s' ), $author ) . '</p>';
							$userdata = get_user_by( 'email', $author );
							if ( ! empty( $userdata ) ) {
								$post_author = $userdata->ID;
								$author_found = true;
							}
						}
					}

					if ( preg_match( '/Date: /i', $line ) ) { // of the form '20 Mar 2002 20:32:37'
						$ddate = trim( $line );
						$ddate = str_replace( 'Date: ', '', $ddate );
						if ( strpos( $ddate, ',' ) ) {
							$ddate = trim( substr( $ddate, strpos( $ddate, ',' ) + 1, strlen( $ddate ) ) );
						}
						$date_arr = explode(' ', $ddate);
						$date_time = explode( ':', $date_arr[3] );

						$ddate_H = $date_time[0];
						$ddate_i = $date_time[1];
						$ddate_s = $date_time[2];

						$ddate_m = $date_arr[1];
						$ddate_d = $date_arr[0];
						$ddate_Y = $date_arr[2];
						for ( $j = 0; $j < 12; $j++ ) {
							if ( $ddate_m == $dmonths[$j] ) {
								$ddate_m = $j+1;
							}
						}

						$time_zn = intval( $date_arr[4] ) * 36;
						$ddate_U = gmmktime( $ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y );
						$ddate_U = $ddate_U - $time_zn;
						$post_date = gmdate( 'Y-m-d H:i:s', $ddate_U + $time_difference );
						$post_date_gmt = gmdate( 'Y-m-d H:i:s', $ddate_U );
					}
				}
			}

			// Set $post_status based on $author_found and on author's publish_posts capability
			if ( $author_found ) {
				$user = new WP_User( $post_author );
				$post_status = ( $user->has_cap( 'publish_posts' ) ) ? 'publish' : 'pending';
			} else {
				// Author not found in DB, set status to pending. Author already set to admin.
				$post_status = 'pending';
			}

			$subject = trim( $subject );

			if ( 'multipart/alternative' == $content_type ) {
				$content = explode( '--'.$boundary, $content );
				$content = $content[2];
				// match case-insensitive content-transfer-encoding
				if ( preg_match( '/Content-Transfer-Encoding: quoted-printable/i', $content, $delim ) ) {
					$content = explode( $delim[0], $content );
					$content = $content[1];
				}
				if ( preg_match( '/Content-Transfer-Encoding: 7bit/i', $content, $delim ) ) {
					$content = explode( $delim[0], $content );
					$content = $content[1];
				}
				$content = strip_tags( $content, '<img><p><br><i><b><u><em><strong><strike><font><span><div>' );
			}
			$content = trim( $content );

			//Give Post-By-Email extending plugins full access to the content
			//Either the raw content or the content of the last quoted-printable section
			$content = apply_filters( 'wp_mail_original_content', $content );

			if ( false !== stripos( $content_transfer_encoding, "quoted-printable" ) ) {
				$content = quoted_printable_decode( $content );
			}

			// if ( function_exists( 'iconv' ) && ! empty( $charset ) ) {
			// 	$content = iconv( $charset, get_option( 'blog_charset' ), $content );
			// }

			// Captures any text in the body after $phone_delim as the body
			$content = explode( $phone_delim, $content );
			$content = empty( $content[1] ) ? $content[0] : $content[1];

			$content = trim( $content );

			$post_content = apply_filters( 'phone_content' , $content );

			$post_title = xmlrpc_getposttitle( $content );

			if ( '' == $post_title )
				$post_title = $subject;

			$post_category = array( $options['default_email_category'] );

			$post_data = compact( 'post_content','post_title','post_date','post_date_gmt','post_author','post_category', 'post_status' );
			$post_data = wp_slash( $post_data );

			$post_ID = wp_insert_post( $post_data );
			if ( is_wp_error( $post_ID ) )
				$log['messages'][] = "\n" . $post_ID->get_error_message();

			// We couldn't post, for whatever reason. Better move forward to the next email.
			if ( empty( $post_ID ) )
				continue;

			do_action( 'publish_phone', $post_ID );

			$log['messages'][] = "\n<p>" . sprintf( __( '<strong>Author:</strong> %s' ), esc_html( $post_author ) ) . '</p>';
			$log['messages'][] = "\n<p>" . sprintf( __( '<strong>Posted title:</strong> %s' ), esc_html( $post_title ) ) . '</p>';

			if( ! $pop3->delete( $i ) ) {
				$log['messages'][] = '<p>' . sprintf( __( 'Oops: %s' ), esc_html( $pop3->ERROR ) ) . '</p>';
				$pop3->reset();
				exit;
			} else {
				$log['messages'][] = '<p>' . sprintf( __( 'Mission complete. Message <strong>%s</strong> deleted.' ), $i ) . '</p>';
			}

		}
		$pop3->quit();

		foreach( $log['messages'] as $message ) { echo $message; }
		update_option( 'post_by_email_log', $log );
	}

	protected function save_log_and_die( $error, $log ) {
		$messages[] = $error;
		$log['messages'] = $messages;
		update_option( 'post_by_email_log', $log );
		wp_die( $status );
	}
}