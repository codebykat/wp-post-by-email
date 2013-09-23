<?php
/**
 * Plugin admin class.
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 */
class Post_By_Email_Admin {
	protected static $instance = null;

	/**
	 * Instance of this class.
	 *
	 * @since    0.9.6
	 *
	 * @var      object
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	* Help tabs for the settings page.
	*
	* @since    1.0.4
	*/
	public static $help_tabs = array(
		'post-by-email' => array(
			'title'   => 'Post By Email',
			'content' => 'Post By Email allows you to send your WordPress site an email with the content of your post. You must set up a special-purpose e-mail account with IMAP or POP3 access to use this, and any mail received at this address will be posted, so it&#8217;s a good idea to keep this address very secret.</p><p>For detailed installation and configuration instructions, see the links to the right.',
		),
		'mailserver' => array(
				'title'   => 'Mailbox Settings',
				'content' => 'If you&#8217;re not sure what to put here and the defaults don&#8217;t work, ask your email provider for the correct settings.  Here are links to the settings for some common email providers:<li><a href="https://support.google.com/mail/troubleshooter/1668960?hl=en">Gmail</a></li><li><a href="http://help.yahoo.com/kb/index?page=content&id=SLN4075">Yahoo</a></li><li><a href="http://windows.microsoft.com/en-ca/windows/outlook/send-receive-from-app">Outlook.com</a></li>Note that you might also have to enable POP/IMAP access via your email provider.',
		),
		'security' => array(
				'title'  => 'Security',
				'content' => 'If you&#8217ve set a PIN for authentication, you will need to specify it somewhere in your email message using the following shortcode:<p><kbd>[pin abc123]</kbd><p>(Replace abc123 with the PIN you chose.)</p><p>Mail that doe not contain this PIN will be discarded!',
		),
		'shortcodes' => array(
				'title'    => 'Shortcodes',
				'content'  => 'You can specify categories, tags and custom taxonomy terms in your email by including shortcodes.  If no categories or tags are specified, the post will be created in the default category.</p><p>You can also include a gallery shortcode to specify gallery options for any attachments.</p><p>Shortcode examples can be found on the <a href="http://wordpress.org/plugins/post-by-email/">plugin page</a>.',
		),
	);

	/**
	 * Hook up our functions to the admin menus.
	 *
	 * @since     0.9.6
	 */
	private function __construct() {
		// Add the options page and menu item.
		add_action( 'admin_init', array( $this, 'add_plugin_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// disable post by email settings on Settings->Writing page
		add_filter( 'enable_post_by_email_configuration', '__return_false' );

		// AJAX hook to clear the log
		add_action( 'wp_ajax_post_by_email_clear_log', array( $this, 'clear_log') );

		add_action( 'wp_ajax_post_by_email_generate_pin', array( $this, 'generate_pin') );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// allow for manual trigger of the wp-mail.php action hook
		add_action( 'admin_init', array( $this, 'maybe_check_mail' ) );
	}

	/**
	 * Register the settings.
	 *
	 * @since    0.9.0
	 */
	public function add_plugin_settings() {
		register_setting( 'post_by_email_options', 'post_by_email_options', array( $this, 'post_by_email_validate' ) );
	}

	/**
	* Initiate manual check for new mail.
	*
	* @since    1.0.3
	*/
	public function maybe_check_mail() {
		if ( isset( $_GET['check_mail'] ) ) {
			do_action( 'wp-mail.php' );
		}
	}

	/**
	 * Validate saved options.
	 *
	 * @since    0.9.5
	 *
	 * @param   array    $input    Form fields submitted from the settings page.
	 */
	public function post_by_email_validate( $input ) {
		// load all the options so we don't wipe out pre-existing stuff
		$options = get_option( 'post_by_email_options' );

		$default_options = Post_By_Email::$default_options;

		/* no validation here, just sanitation */
		$options['mailserver_url'] = wp_kses_data( strip_tags( $input['mailserver_url'] ) );
		$options['mailserver_login'] = wp_kses_data( strip_tags( $input['mailserver_login'] ) );
		$options['mailserver_pass'] = wp_kses_data( strip_tags( $input['mailserver_pass'] ) );

		$mailserver_protocol = trim( $input['mailserver_protocol'] );
		if ( in_array( $mailserver_protocol, array( 'POP3', 'IMAP' ) ) ) {
			$options['mailserver_protocol'] = $mailserver_protocol;
		} else {
			$error_message .= __( "Could not save protocol: must be POP3 or IMAP.", 'post-by-email' );
			add_settings_error( 'post_by_email_options',
				'post_by_email_options[mailserver_protocol]',
				$error_message
			);
		}
 
		// port must be numeric and 16 digits max
		$mailserver_port = trim( $input['mailserver_port'] );
		if ( preg_match('/^[1-9][0-9]{0,15}$/', $mailserver_port ) ) {
			$options['mailserver_port'] = absint( $mailserver_port );
		} else {
			$error_message = __( "Could not save port number: invalid number.", 'post-by-email' );
			add_settings_error( 'post_by_email_options',
				'post_by_email_options[mailserver_port]',
				$error_message
			);
		}

		// default email category must be the ID of a real category
		$default_email_category = $input['default_email_category'];
		if ( get_category( $default_email_category ) ) {
			$options['default_email_category'] = $default_email_category;
		} else {
			$error_message = __( 'Could not save default category: category not found.', 'post-by-email' );
			add_settings_error( 'post_by_email_options',
				'post_by_email_options[default_email_category]',
				$error_message
			);
		}

		$options['ssl'] = isset( $input['ssl'] ) && '' != $input['ssl'];
		$options['delete_messages'] = isset( $input['delete_messages'] ) && '' != $input['delete_messages'];

		$options['pin_required'] = isset( $input['pin_required'] ) && '' != $input['pin_required'];
		$options['pin'] = trim( $input['pin'] );

		if ( $options['pin_required'] && '' == $options['pin'] ) {
			$error_message = __( 'Please enter a security PIN to enable PIN authentication.', 'post-by-email' );
			add_settings_error( 'post_by_email_options',
				'post_by_email_options[mailserver_pin]',
				$error_message
			);
			$options['pin_required'] = false;
		}

		if ( strpos( $options['pin'], ']' ) ) {
			$error_message = __( 'Error: PIN cannot contain shortcode delimiters.', 'post-by-email' );
			add_settings_error( 'post_by_email_options',
				'post_by_email_options[mailserver_pin]',
				$error_message
			);
			$options['pin'] = '';
			$options['pin_required'] = false;
		}

		if ( isset( $input['discard_pending'] ) ) {
			$options['discard_pending'] = ( 'discard' == $input['discard_pending'] );
		}

		// this is ridiculous
		if ( isset ( $input['status'] ) && in_array( $input['status'], array( 'unconfigured', 'error', '') ) ) {
			// maintain saved state
			$options['status'] = $input['status'];
		}
		elseif ( ( $options['mailserver_url'] == $default_options['mailserver_url'] )
			|| ( '' == $options['mailserver_url'] )
			|| ( $options['mailserver_login'] == $default_options['mailserver_login'] )
			|| ( '' == $options['mailserver_login'] )
			|| ( '' == $options['mailserver_pass'] )
			|| ( '' == $options['mailserver_port'] )
			|| ( '' == $options['mailserver_protocol' ] )
			) {
			// detect if settings are blank or defaults
			$options['status'] = 'unconfigured';
		}
		else {
			// clear the transient and any error conditions if we have good options now
			delete_transient( 'mailserver_last_checked' );
			$options['status'] = '';
		}

		// make sure all default options are set
		foreach ( $default_options as $key => $value ) {
			if ( ! isset( $options[ $key ] ) ) {
				$options[ $key ] = $value;
			}
		}

		return $options;
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.9.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_management_page(
			__( 'Post By Email', 'post-by-email' ),
			__( 'Post By Email', 'post-by-email' ),
			'read',
			'post-by-email',
			array( $this, 'display_plugin_admin_page' )
		);
		$screen = WP_Screen::get( $this->plugin_screen_hook_suffix);
		foreach ( self::$help_tabs as $id => $data ) {
			$screen->add_help_tab( array(
					'id'       => $id,
					'title'    => __( $data['title'], 'post-by-email' ),
					'content'  => '',
					'callback' => array( $this, 'show_help_tabs' )
				)
			);
		}
		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'post-by-email' ) . '</strong></p>' .
			'<p><a href="http://wordpress.org/plugins/post-by-email/installation" target="_blank">' . __( 'Installation', 'post-by-email' ) . '</a></p>' .
			'<p><a href="http://wordpress.org/plugins/post-by-email/" target="_blank">' . __( 'Usage', 'post-by-email' ) . '</a></p>' .
			'<p><a href="http://wordpress.org/support/plugin/post-by-email" target="_blank">' . __( 'Support Forums', 'post-by-email' ) . '</a></p>'
		);
	}

	/**
	* Prints out the content for the contextual help tabs.
	*
	* @since    1.0.4
	*/
	public function show_help_tabs( $screen, $tab ) {
		printf(
				'<p>%s</p>',
				__( self::$help_tabs[ $tab['id'] ]['content'], 'post-by-email' )
			);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.9.0
	 */
	public function display_plugin_admin_page() {
		include_once( plugin_dir_path( __FILE__ ) . 'views/admin.php' );
	}

	/**
	* Load up Javascript for the admin page.
	*
	* @since    1.0.1
	*
	* @param    string    $hook    Name of the current admin page.
	*/
	public function enqueue_scripts( $hook ) {
		if ( $hook != $this->plugin_screen_hook_suffix )
			return;

		wp_enqueue_script( 'post-by-email-admin-js', plugins_url( 'js/admin.js', __FILE__ ), 'jquery', '', true );

		// add nonces and JS messages
		$settings_message = "Your settings have not yet been saved.\nChecking mail now will discard your changes.\nAre you sure you want to do this?";

		$vars = array(
			'logNonce' => wp_create_nonce( 'post-by-email-clear-log' ),
			'pinNonce' => wp_create_nonce( 'post-by-email-generate-pin' ),
			'settingsMessage' => __( $settings_message, 'post-by-email' ),
		);
		wp_localize_script( 'post-by-email-admin-js', 'PostByEmailVars', $vars );
	}

	/**
	* Display any errors or notices as an admin banner.
	*
	* @since    1.0.1
	*/
	public function admin_notices() {
		$options = get_option( 'post_by_email_options' );
		$settings_url = add_query_arg( 'page', 'post-by-email', admin_url( 'tools.php' ) );
		if ( ! $options || ! isset( $options['status'] ) || 'unconfigured' == $options['status'] ) {
			echo "<div class='error'><p>";
			_e( "Notice: Post By Email is currently disabled.  To post to your blog via email, please <a href='$settings_url'>configure your settings now</a>.", 'post-by-email' );
			echo "</p></div>";
		} elseif ( 'error' == $options['status'] ) {
			echo "<div class='error'><p>";
			_e( "Post By Email encountered an error.  <a href='$settings_url&tab=log'>View the log</a> for details.", 'post-by-email' );
			echo "</p></div>";
		}

		settings_errors( 'post_by_email_options' );
	}

	/**
	 * Clear the log file.
	 *
	 * @since    0.9.9
	*/
	public function clear_log() {
		check_ajax_referer( 'post-by-email-clear-log', 'security' );
		if ( current_user_can( 'manage_options' ) ) {
			update_option( 'post_by_email_log', array() );
		}

		die();
	}

	/**
	 * Generate a good PIN.
	 *
	 * @since    1.0.2
	*/
	public function generate_pin() {
		check_ajax_referer( 'post-by-email-generate-pin', 'security' );
		if ( current_user_can( 'manage_options' ) ) {
			echo wp_generate_password( 8, true, false );
		}

		die();
	}

}