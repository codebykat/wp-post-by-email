<?php
/**
 * Post By Email
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @copyright 2013-2015 Kat Hagan / Automattic
 * @link      https://github.com/codebykat/wp-post-by-email/
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
	protected $version = '1.1';

	/**
	 * Unique identifier for the plugin.
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
	 * Instance of the mailserver utility class.
	 *
	 * @since    1.0.5
	 *
	 * @var      object
	 */
	protected static $mailserver = null;

	/**
	 * Plugin include path (used for autoloading libraries).
	 *
	 * @since    0.9.7
	 *
	 * @var      string
	 */
	public static $path;

	/**
	 * Default settings.
	 *
	 * @since    0.9.8
	 *
	 * @var      array
	 */
	public static $default_options = array(
		'mailserver_url'            => 'mail.example.com',
		'mailserver_login'          => 'login@example.com',
		'mailserver_pass'           => '',
		'mailserver_protocol'       => 'IMAP',
		'mailserver_port'           => 993,
		'ssl'                       => true,
		'default_email_category'    => '',
		'delete_messages'           => true,
		'status'                    => 'unconfigured',
		'pin_required'              => false,
		'pin'                       => '',
		'discard_pending'           => false,
		'registered_pending'        => false,
		'send_response'             => false,
		'last_checked'              => 0,
	);

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.9.0
	 * @codeCoverageIgnore
	 */
	private function __construct() {
		// Load plugin text domain
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Enable autoloading
		add_action( 'plugins_loaded', array( $this, 'load' ) );

		// add hooks to check for mail
		add_action( 'wp-mail.php', array( $this, 'manual_check_email' ) );
		add_action( 'post-by-email-wp-mail.php', array( $this, 'check_email' ) );

		// disable wp-mail.php
		add_filter( 'enable_post_by_email_configuration', '__return_false' );

		// @todo: check PHP version & whether IMAP extension is installed
		self::$mailserver = new Post_By_Email_Mailserver_Horde();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.9.0
	 *
	 * @return    object    A single instance of this class.
	 *
	 * @codeCoverageIgnore
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
	 * @param    boolean    $network_wide    True if multisite superadmin uses "Network Activate" action, false if multisite is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		// set up plugin options
		$plugin_options = get_option( 'post_by_email_options' );

		$options = self::$default_options;

		// if old global options exist, copy them into plugin options
		foreach ( array_keys( self::$default_options ) as $optname ) {
			if ( isset( $plugin_options[$optname] ) ) {
				$options[$optname] = $plugin_options[$optname];
			} elseif ( get_option( $optname ) ) {
				$options[ $optname ] = get_option( $optname );
			}
		}

		if ( ! isset( $plugin_options['mailserver_protocol'] )
			&& in_array( $options['mailserver_port'], array( 110, 995 ) ) ) {
			$options['mailserver_protocol'] = 'POP3';
			$options['delete_messages'] = false;
		}

		if ( ! isset( $plugin_options['ssl'] )
			&& in_array( $options['mailserver_port'], array( 110, 143 ) ) ) {
			$options['ssl'] = false;
		}

		update_option( 'post_by_email_options', $options );

		// if log already exists, this will return false, and that is okay
		add_option( 'post_by_email_log', array(), '', 'no' );

		// schedule hourly mail checks with wp_cron
		if ( ! wp_next_scheduled( 'post-by-email-wp-mail.php' ) ) {
			wp_schedule_event( current_time( 'timestamp', true ), 'hourly', 'post-by-email-wp-mail.php' );
		}
	}

	/**
	* Fired when the plugin is deactivated.
	*
	* @since    0.9.8
	*/
	public static function deactivate() {
		wp_clear_scheduled_hook( 'post-by-email-wp-mail.php' );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.9.0
	 * @codeCoverageIgnore
	 */
	public function load_plugin_textdomain() {
		$domain = $this->plugin_slug;
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Run "check_email" when not called by wp_cron.
	 *
	 * @since    0.9.9
	 */
	public function manual_check_email() {
		// update scheduled check so next one is an hour from last manual check
		wp_clear_scheduled_hook( 'post-by-email-wp-mail.php' );
		wp_schedule_event( current_time( 'timestamp', true ) + HOUR_IN_SECONDS, 'hourly', 'post-by-email-wp-mail.php' );

		$this->check_email();

		$args = array( 'page' => 'post-by-email', 'tab' => 'log' );
		$this->redirect( $args );
	}

	/**
	 * Do a wp_safe_redirect.
	 *
	 * @since    1.1
	 * @codeCoverageIgnore
	 */
	protected function redirect( $args ) {
		wp_safe_redirect( add_query_arg( $args, admin_url( 'tools.php' ) ) );
	}

	/**
	 * Check for new messages and post them to the blog.
	 *
	 * @since    0.9.0
	 */
	public function check_email() {
		// Only check at this interval for new messages.
		if ( ! defined( 'WP_MAIL_INTERVAL' ) )
			define( 'WP_MAIL_INTERVAL', 5 * MINUTE_IN_SECONDS );

		$last_checked = get_transient( 'post_by_email_last_checked' );

		if ( $last_checked && ! WP_DEBUG ) {
			$time_diff = __( human_time_diff( time(), time() + WP_MAIL_INTERVAL ), 'post-by-email' );
			$log_message = sprintf( __( 'Please wait %s to check mail again!', 'post-by-email' ), $time_diff );
			$this->save_log_message( $log_message );
			return;
		}

		set_transient( 'post_by_email_last_checked', true, WP_MAIL_INTERVAL );

		$options = get_option( 'post_by_email_options' );

		// if options aren't set, there's nothing to do, move along
		if ( 'unconfigured' === $options['status'] ) {
			return;
		}

		$options['last_checked'] = current_time( 'timestamp', true );
		$options['status'] = '';
		update_option( 'post_by_email_options', $options );

		$connection_options = array(
			'protocol' => $options['mailserver_protocol'],
			'username' => $options['mailserver_login'],
			'password' => $options['mailserver_pass'],
			'hostspec' => $options['mailserver_url'],
			'port'     => $options['mailserver_port'],
			'secure'   => $options['ssl'] ? 'ssl' : false,
		);

		try {
			$this->open_mailbox( $connection_options );
			$uids = $this->get_messages();
		} catch( Exception $e ) {
			$this->save_error_message( __( 'An error occurred: ', 'post-by-email') . $e->getMessage() );
			return;
		}

		if ( 0 === sizeof( $uids ) ) {
			$this->save_log_message( __( 'There doesn&#8217;t seem to be any new mail.', 'post-by-email' ) );
			$this->close_mailbox();
			return;
		}

		$log_message = sprintf( _n( 'Found 1 new message.', 'Found %s new messages.', sizeof( $uids ), 'post-by-email' ), sizeof( $uids ) );

		$time_difference = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

		foreach ( $uids as $uid ) {
			$post_ID = $this->create_post( $uid, $time_difference, $log_message );

			// We couldn't post, for whatever reason. Better move forward to the next email.
			if ( empty( $post_ID ) )
				continue;

			/* attachments */
			$attachment_count = $this->save_attachments( $uid, $post_ID );

			$post_content = get_post_field( 'post_content', $post_ID );
			if ( $attachment_count > 0 && ! has_shortcode( $post_content, 'gallery' ) ) {

				// add a default gallery if there isn't one already
				$post_info = array(
					'ID' => $post_ID,
					'post_content' => $post_content . '[gallery]',
				);

				wp_update_post( $post_info );
			}

			do_action( 'publish_phone', $post_ID );

			$post_title = get_the_title( $post_ID ) ? get_the_title( $post_ID ) : __( '(no title)', 'post-by-email' );

			$pending = '';
			if ( 'pending' == get_post_status( $post_ID ) ) {
				$pending = __( ' (pending)', 'post-by-email' );
			}

			$post_log_message = __( 'Posted:', 'post-by-email') . ' <a href="' . get_permalink( $post_ID ) . '">' . esc_html( $post_title ) . '</a>' . $pending;
			$log_message .= "<br />" . $post_log_message;

			// send response email for success
			if ( $options['send_response'] ) {
				$this->send_response( TRUE, $subject, $post_log_message, $from_email );
			}

		} // end foreach

		$this->save_log_message( $log_message );

		// mark all processed emails as read
		$this->mark_as_read( $uids, $options['delete_messages'] );
		$this->close_mailbox();
	}

	/**
	 * Create a post from the email.
	 *
	 * @since    1.1
	 *
	 * @param    integer    $uid    The email UID
	 * @param    integer    $time_difference Blog time difference from UTC (seconds)
	 * @param    string     $log_message The log message (passed by reference)
	 *
	 * @return   integer    $id
	 */
	public function create_post( $uid, $time_difference, &$log_message = '' ) {
		$phone_delim = '::';
		$options = get_option( 'post_by_email_options' );

		// get headers
		$headers = $this->get_message_headers( $uid );

		/* Subject */
		// Captures any text in the subject before $phone_delim as the subject
		$subject = $headers['Subject'];
		$subject = explode( $phone_delim, $subject );
		$subject = $subject[0];
		$subject = trim( $subject );


		/* Author */
		$from_email = $this->get_message_author( $headers );

		$userdata = get_user_by( 'email', $from_email );
		if ( ! empty( $userdata ) ) {
			$post_author = $userdata->ID;

			// Save as a draft if requested
			if ( $options['registered_pending'] ) {
				$post_status =  'draft';
			} else {
				// Set $post_status based on author's publish_posts capability
				$user = new WP_User( $post_author );
				$post_status = ( $user->has_cap( 'publish_posts' ) ) ? 'publish' : 'pending';
			}
		} else {
			if ( $options['discard_pending'] ) {
				$post_log_message = sprintf( __( "No author match for %s (Subject: %s); skipping.", 'post-by-email' ),
														$from_email, $subject );
				$log_message .= '<br />' . $post_log_message;
				// send response email for failure
				if ( $options['send_response'] ) {
					$this->send_response( FALSE, $subject, $post_log_message, $from_email );
				}
				return false;
			}
			// use admin if no author found
			$post_author = $this->get_admin_id();
			$post_status = 'pending';
		}


		/* Date */
		$ddate_U = $this->get_message_date( $headers );
		$post_date = gmdate( 'Y-m-d H:i:s', $ddate_U + $time_difference );
		$post_date_gmt = gmdate( 'Y-m-d H:i:s', $ddate_U );


		/* Message body */
		$content = $this->get_message_body( $uid );

		//Give Post-By-Email extending plugins full access to the content
		//Either the raw content or the content of the last quoted-printable section
		$content = apply_filters( 'wp_mail_original_content', $content );

		// Captures any text in the body after $phone_delim as the body
		$content = explode( $phone_delim, $content );
		$content = empty( $content[1] ) ? $content[0] : $content[1];

		$content = trim( $content );

		// replace HTML-ized quotes with the real thing, so shortcode arguments work
		$content = str_replace( array( '&#39;', '&quot;' ), array( "'", '"' ), $content );

		$post_content = apply_filters( 'phone_content' , $content );

		/* post title */
		$post_title = xmlrpc_getposttitle( $content );

		if ( '' == $post_title )
			$post_title = $subject;

		/* validate PIN */
		if ( $options['pin_required'] ) {
			$pin = $this->find_shortcode( 'pin', $post_content );
			$pin = implode( $pin );

			if ( $pin != $options['pin'] ) {
				// security check failed - move on to the next message
				$post_log_message = '"' . $post_title . '" ' . __( 'failed PIN authentication; discarding.', 'post-by-email' );
				$log_message .= '<br />' . $post_log_message;
				// send response email for failure
				if ( $options['send_response'] ) {
					$this->send_response( FALSE, $subject, $post_log_message, $from_email );
				}
				return false;
			}
		}


		/* shortcode: categories. [category cat1 cat2...] */

		$shortcode_categories = $this->find_shortcode( 'category', $post_content );
		$post_category = array();
		if ( empty( $shortcode_categories ) ) {
			$post_category[] = $options['default_email_category'];
		}
		foreach ( $shortcode_categories as $cat ) {
			if ( is_numeric( $cat ) ) {
				$post_category[] = $cat;
			} elseif ( get_category_by_slug( $cat ) ) {
				$term = get_category_by_slug( $cat );
				$post_category[] = $term->term_id;
			} else {  // create new category
				$new_category = wp_insert_term( $cat, 'category' );
				if ( $new_category ) {
					$post_category[] = $new_category['term_id'];
				}
			}
		}

		/* shortcode: tags. [tag tag1 tag2...] */

		$tags_input = $this->find_shortcode( 'tag', $post_content );

		$original_post_content = $post_content;
		$post_content = $this->filter_valid_shortcodes( $post_content );


		/* create the post */
		$post_data = compact( 'post_content', 'post_title', 'post_date', 'post_date_gmt', 'post_author', 'post_category', 'post_status', 'tags_input' );
		$post_data = wp_slash( $post_data );

		$post_ID = wp_insert_post( $post_data );
		if ( is_wp_error( $post_ID ) ) {
			$log_message .= "\n" . $post_ID->get_error_message();
			$this->save_error_message( $log_message );
			// send response email for failure
			if ( $options['send_response'] ) {
				$this->send_response( FALSE, $subject, $post_ID->get_error_message(), $from_email );
			}
		}

		// We couldn't post, for whatever reason. Return and move on.
		if ( empty( $post_ID ) )
			return $post_ID;

		// save original message sender as post_meta, in case we want it later
		add_post_meta( $post_ID, 'original_author', $from_email );

		/* shortcode: post-format. [post-format format] */
		$post_format_input = $this->find_shortcode( 'post-format', $original_post_content );
		if ( ! empty( $post_format_input ) )
			set_post_format( $post_ID, $post_format_input[0] );

		/* shortcode: custom taxonomies.  [taxname term1 term2 ...] */
		$tax_input = array();

		// get all registered custom taxonomies
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$registered_taxonomies = get_taxonomies( $args, 'names', 'and' ); 

		if ( $registered_taxonomies ) {
			foreach ( $registered_taxonomies as $taxonomy_name ) {
				$tax_shortcodes = $this->find_shortcode( $taxonomy_name, $original_post_content );
				if ( count( $tax_shortcodes ) > 0 ) {
					// pending bug fix: http://core.trac.wordpress.org/ticket/19373
					//$tax_input[] = array( $taxonomy_name => $tax_shortcodes );
					wp_set_post_terms( $post_ID, $tax_shortcodes, $taxonomy_name );
				}
			}
		}

		return $post_ID;		
	}

	/**
	 * Returns the site administrator's ID (used to set the author of posts sent from unrecognized email addresses).
	 *
	 * @since    1.0.1
	 *
	 * @return   integer    $id
	 */
	public function get_admin_id() {
		global $wpdb;

		$id = $wpdb->get_var( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wp%_capabilities' AND meta_value LIKE '%administrator%' ORDER BY user_id LIMIT 1" );
		return $id;
	}

	/**
	 * Find the email address of the message sender from the headers.
	 *
	 * @since    1.0.0
	 *
	 * @param    object    $headers    Message headers
	 *
	 * @return   string|false
	 */
	public function get_message_author( $headers ) {
		// @todo it might make sense to use "Reply-To" if we don't have a match for "From"
		$from_email = $headers['From'];

		if ( preg_match( '|[a-z0-9_.-]+@[a-z0-9_.-]+(?!.*<)|i', $from_email, $matches ) )
			$from_email = $matches[0];
		else
			$from_email = trim( $from_email );

		$from_email = sanitize_email( $from_email );

		if ( is_email( $from_email ) ) {
			return $from_email;
		}

		return false;  // author not found
	}

	/**
	 * Establishes the connection to the mailserver.
	 *
	 * @since    1.0.0
	 *
	 * @param    array    $options    Options array
	 *
	 * @return   bool
	 *
	 * @codeCoverageIgnore
	 */
	protected function open_mailbox( $connection_options ) {
		return self::$mailserver->open_mailbox_connection( $connection_options );
	}

	/**
	* Closes the connection to the mailserver.
	*
	* @since    1.0.4
	* @codeCoverageIgnore
	*/
	protected function close_mailbox() {
		return self::$mailserver->close_connection();
	}

	/**
	 * Retrieve the list of new message IDs from the server.
	 *
	 * @since    1.0.0
	 *
	 * @return   array    Array of message UIDs
	 *
	 * @codeCoverageIgnore
	 */
	protected function get_messages() {
		return self::$mailserver->get_messages();
	}

	/**
	 * Retrieve message headers.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $uid    Message UID
	 *
	 * @return   object
	 *
	 * @codeCoverageIgnore
	 */
	protected function get_message_headers( $uid ) {
		return self::$mailserver->get_message_headers( $uid );
	}

	/**
	 * Get the content of a message from the mailserver.
	 *
	 * @since    1.0.0
	 *
	 * @param    int       Message UID
	 *
	 * @return   string    Message content
	 *
	 * @codeCoverageIgnore
	 */
	protected function get_message_body( $uid ) {
		return self::$mailserver->get_message_body( $uid );
	}

	/**
	 * Get the date of a message from its headers.
	 *
	 * @since    1.0.0
	 *
	 * @param    object    $headers    Message headers
	 *
	 * @return   string
	 */
	public function get_message_date( $headers ) {
		$date = $headers['Date'];
		if ( ! $date ) {
			return current_time( 'timestamp', true );
		}

		// http://cr.yp.to/immhf/date.html
		// ex: 'Fri, 27 Mar 2015 01:40:04 +0000'

		$date = trim( $date );
		if ( strpos( $date, ',' ) ) {
			// if the day of the week is provided, remove it (e.g. "Fri, 27 Mar...")
			$date = trim( substr( $date, strpos( $date, ',' ) + 1, strlen( $date ) ) );
		}

		$date_pieces = explode( ' ', $date );
		$time_pieces = explode( ':', $date_pieces[3] );

		$day = $date_pieces[0];
		$month = $date_pieces[1];
		$year = $date_pieces[2];

		$hours = $time_pieces[0];
		$minutes = $time_pieces[1];

		// seconds are optional
		$seconds = 0;
		if ( count( $time_pieces ) > 2 ) {
			$seconds = $time_pieces[2];
		}

		// timezone is optional
		$timezone_offset = 0;
		if( count( $date_pieces ) > 4 ) {
			// @todo I think this is flawed
			$timezone_offset = intval( $date_pieces[4] ) * 36;
		}

		// convert month to numeric
		$month_names = array( '', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
		$month = array_search( $month, $month_names );

		$gmt_timestamp = gmmktime( $hours, $minutes, $seconds, $month, $day, $year );
		$timestamp = $gmt_timestamp - $timezone_offset;

		return $timestamp;
	}

	/**
	 * Mark a list of messages read on the server.
	 *
	 * @since    1.0.0
	 *
	 * @param    array    $uids      UIDs of messages that have been processed
	 * @param    bool     $delete    Whether to delete read messages
	 */
	protected function mark_as_read( $uids, $whether_to_delete ) {
		try {
			self::$mailserver->mark_as_read( $uids, $whether_to_delete );
		} catch( Exception $e ) {
			$this->save_error_message( __( 'An error occurred: ', 'post-by-email') . $e->getMessage() );
			return false;
		}
		return true;
	}

	/**
	 * Get a message's attachments from the mail server.
	 *
	 * @since    1.0.5
	 *
	 * @param    int       Message UID
	 *
	 * @return   array     Message attachments
	 *
	 * @codeCoverageIgnore
	 */
	protected function get_attachments( $uid ) {
		return self::$mailserver->get_attachments( $uid );
	}

	/**
	 * Get a single attachment from the mail server.
	 *
	 * @since    1.0.5
	 *
	 * @param    ID        Message ID
	 * @param    mime_id   Attachment's MIME ID
	 *
	 * @return   string    Decoded attachment data
	 *
	 * @codeCoverageIgnore
	 */
	protected function get_single_attachment( $id, $mime_id ) {
		return self::$mailserver->get_single_attachment( $id, $mime_id );
	}

	/**
	 * Get a message's attachments from the mail server and associate them with the post.
	 *
	 * @since    1.0.3
	 *
	 * @param    int       Message UID
	 * @param    int       ID of the Post to attach to
	 *
	 * @return   int       Number of attachments saved
	 */
	public function save_attachments( $uid, $postID ) {
		$image_types = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif' );

		$attachments = $this->get_attachments( $uid );

		$attachment_count = 0;
		$post_thumbnail = false;

		foreach ( $attachments as $attachment ) {
			if ( 'attachment' === $attachment['disposition'] || ( 'inline' === $attachment['disposition'] && 'image' === $attachment['type'] ) ) {
				$mime_id = $attachment['mime_id'];
				$filename = sanitize_file_name( $attachment['name'] );
				$filetype = $attachment['mimetype'];

				$upload_dir = wp_upload_dir();
				$directory = $upload_dir['basedir'] . $upload_dir['subdir'];

				$filename = wp_unique_filename( $directory, $filename );

				$image_data_decoded = $this->get_single_attachment( $uid, $mime_id );

				$tmp_file      = tmpfile();
				$meta_data     = stream_get_meta_data( $tmp_file );
				$written_bytes = fwrite( $tmp_file, $image_data_decoded );
				fseek( $tmp_file, 0 );

				$file = array(
					'name'     => $filename,
					'tmp_name' => $meta_data['uri'],
					'type'     => $filetype,
					'size'     => $written_bytes,
				);

				$new_file = wp_handle_sideload( $file, array( 'test_form' => false ) );

				// Delete the temp file
				fclose( $tmp_file );

				if ( $new_file && ! is_wp_error( $new_file ) && ! isset( $new_file['error'] ) ) {
					$filename = basename( $new_file['file'] );

					// add attachment to the post
					$attachment_args = array(
						'post_title' => $filename,
						'post_content' => '',
						'post_status' => 'inherit',
						'post_mime_type' => $filetype,
					);

					$attachment_id = wp_insert_attachment( $attachment_args, $new_file['file'], $postID );
					$attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $new_file['file'] );
					wp_update_attachment_metadata( $attachment_id, $attachment_metadata );
					$attachment_count++;

					// make the first image attachment the featured image
					if ( false === $post_thumbnail && in_array( $filetype, $image_types ) ) {
						set_post_thumbnail( $postID, $attachment_id );
						$post_thumbnail = true;
					}
				}
			}
		}

		return $attachment_count;
	}

	/**
	 * Send a response to the originating email address.
	 *
	 * @since    1.0.5
	 *
	 * @param    bool      $success        Was the post added?
	 * @param    string    $subject        The message subject
	 * @param    string    $log_message    The message returned by the post.
	 * @param    string    $author_email   Email from which post originated.
	 */
	public function send_response( $success, $subject, $log_message, $author_email ) {
		// Set the header as HTML content type
		$headers[] = 'Content-type: text/html';

		// Set the message depending on success or failure
		$message_title = $success ? __( 'Success!', 'post-by-email' ) : __( 'Failed!', 'post-by-email' );

		$message = '<strong>' . $message_title . '</strong><br /><br />' . $log_message;

		// Send the message
		wp_mail( $author_email, 'Re: ' . $subject, $message, $headers );
	}

	/**
	 * Look for a shortcode and return its arguments.
	 *
	 * @since    1.0.2
	 *
	 * @param    string    $shortcode    Shortcode to look for
	 * @param    string    $text         Text to search within
	 *
	 * @return   array     $args         Shortcode arguments
	 */
	public function find_shortcode( $shortcode, $text ) {
		if ( preg_match( "/\[$shortcode\s(.*?)\]/i", $text, $matches ) ) {
			return explode( ' ', $matches[1] );
		}
		return array();
	}

	/**
	 * Filter shortcodes out of the message content.
	 *
	 * @since    1.0.2
	 *
	 * @param    string    $text         Text to search within
	 *
	 * @return   string    $text         Filtered text
	 */
	public function filter_valid_shortcodes( $text ) {
		$valid_shortcodes = array( 'tag', 'category', 'pin', 'post-format' );

		// get all registered custom taxonomies
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$registered_taxonomies = get_taxonomies( $args, 'names', 'and' );

		if ( $registered_taxonomies ) {
			foreach ( $registered_taxonomies as $taxonomy ) {
				$valid_shortcodes[] = $taxonomy;
			}
		}

		foreach ( $valid_shortcodes as $shortcode ) {
			$text = preg_replace( "/\[$shortcode\s(.*?)\]/i", '', $text );	
		}
		return $text;
	}

	/**
	 * Save an error message to the log file.
	 *
	 * @since    0.9.9
	 *
	 * @param    string    $message    Error to save to the log.
	 */
	public function save_error_message( $message ) {
		$this->save_log_message( $message, true );
	}

	/**
	 * Save a message to the log file.
	 *
	 * @since    0.9.9
	 *
	 * @param    string    $message    Message to save to the log.
	 */
	public function save_log_message( $message, $error=false ) {
		$log = get_option( 'post_by_email_log', array() );

		array_unshift( $log, array(
				'timestamp' => current_time( 'timestamp' ),
				'message' => $message,
			)
		);

		update_option( 'post_by_email_log', $log );

		if ( $error ) {
			$options = get_option( 'post_by_email_options' );
			$options['status'] = 'error';
			update_option( 'post_by_email_options', $options );

			// clear the transient so the user can trigger another check right away
			delete_transient( 'post_by_email_last_checked' );
		}
	}

	/**
	 * Set the plugin include path and register the autoloader.
	 *
	 * @since 0.9.7
	 * @codeCoverageIgnore
	 */
	public static function load() {
		self::$path = dirname( __FILE__ );
		spl_autoload_register( array( 'Post_By_Email', 'autoload' ) );
	}

	/**
	 * Autoload class libraries as needed.
	 *
	 * @since 0.9.7
	 *
	 * @param    string    $class    Class name of requested object.
	 *
	 * @codeCoverageIgnore
	 */
	public static function autoload( $class ) {
		// We're only interested in autoloading Horde includes.
		if ( 0 !== strpos( $class, 'Horde' ) ) {
			return;
		}

		// Replace all underscores in the class name with slashes.
		// ex: we expect the class Horde_Imap_Client to be defined in Horde/Imap/Client.php.
		$filename = str_replace( array( '_', '\\' ), '/', $class );
		$filename = self::$path . '/include/' . $filename . '.php';
		if ( file_exists( $filename ) ) {
			require_once( $filename );
		}
	}
}
