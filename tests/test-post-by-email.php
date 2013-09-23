<?php
/**
 * Post By Email Unit Tests
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      http://codebykat.wordpress.com
 * @copyright 2013 Kat Hagan
 */

/**
 * Plugin test class.
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 */
class Tests_Post_By_Email_Plugin extends WP_UnitTestCase {

	/**
	 * Instantiation of the plugin.
	 *
	 * @since   1.0.4
	 *
	 * @var     object
	 */
	protected $plugin;

	/**
	* Set up the tests.
	*
	* @since    1.0.4
	*/
	public function setUp() {
		parent::setUp();
		$this->plugin = Post_By_Email::get_instance();
		$options = Post_By_Email::$default_options;
		$option['status'] = '';
		update_option( 'post_by_email_options', $options );
	}

	/**
	* Set an option in the plugin's options array.
	*
	* @since    1.0.4
	*
	* @var      $key    Option name
	* @var      $value  Option value
	*/
	protected function set_option( $key, $value ) {
		$options = get_option( 'post_by_email_options' );
		$options[ $key ] = $value;
		update_option( 'post_by_email_options', $options );
	}

    /**
    * Get an option from the plugin's options array.
    *
    * @since    1.0.4
    *
    * @var      string    Option name to retrieve
    *
    * @return   string    Option's value
    */
	protected function get_option( $key ) {
		$options = get_option( 'post_by_email_options' );
		return $options[ $key ];
	}

	/**
	* Get the last message logged by the plugin.
	*
	* @since    1.0.4
	*
	* @return   string    Last message logged
	*/
	protected function get_last_log_message() {
		$log = get_option( 'post_by_email_log' );
		if ( $log ) {
			$last_entry = array_shift( $log );
			return $last_entry['message'];
		}
		return 'Nothing logged.';
	}

	/**
	* Test plugin activation.
	*
	* @since    0.9.7
	*/
	public function test_plugin_activation() {
		// with no preexisting options and no global ones, use defaults
		delete_option( 'post_by_email_options' );
		delete_option( 'mailserver_url' );
		$this->plugin->activate( false );

		$this->assertEquals( 'mail.example.com', $this->get_option( 'mailserver_url' ) );

		// copy over the global options if they exist
		delete_option( 'post_by_email_options' );
		update_option( 'mailserver_url', 'testing.example.com' );
		$this->plugin->activate( false );

		$this->assertNotEquals( false, get_option ('post_by_email_options' ) );
		$this->assertEquals( 'testing.example.com', $this->get_option( 'mailserver_url' ) );

		// when we have preexisting options, those should take precedence
		update_option( 'mailserver_url', 'another.example.com' );
		$this->plugin->activate( false );
		$this->assertNotEquals( 'another.example.com', $this->get_option( 'mailserver_url' ) );
	}

	/**
	* Test setup of wp_cron on activate/deactivate.
	*
	* @since    0.9.8
	*/
	public function test_wp_cron_setup() {
		// plugin activation should schedule an event with wp_cron
		$this->plugin->activate( false );
		$this->assertNotEquals( false, wp_next_scheduled( 'post-by-email-wp-mail.php' ) );

		// plugin deactivation should remove wp_cron scheduled event
		$this->plugin->deactivate();
		$this->assertFalse( wp_next_scheduled( 'post-by-email-wp-mail.php' ) );
	}

	/**
	* Test that plugin does nothing if options haven't been set.
	*
	* @since    1.0.4
	*/
	public function test_return_if_options_not_set() {
		$this->set_option( 'mailserver_url', 'mail.example.com' );
		$this->set_option( 'status', 'unconfigured' );

		$stub = $this->getMock( 'Post_By_Email', array( 'open_mailbox_connection', 'get_messages', 'close_connection' ), array(), '', false );

		$stub->check_email();

		// should immediately return without doing anything
		$stub->expects( $this->never() )
			->method( 'open_mailbox_connection' );

		$stub->expects( $this->never() )
			->method( 'get_messages' );

		$this->assertEquals( 'Nothing logged.', $this->get_last_log_message() );
	}

	/**
	* Test opening a POP3 connection with the right options.
	*
	* @since    1.0.4
	*/
	public function test_open_POP3_connection() {
		$stub = $this->getMock( 'Post_By_Email', array( 'open_mailbox_connection', 'get_messages', 'close_connection' ), array(), '', false );

		$this->set_option( 'mailserver_url', 'mail.test.com' );
		$this->set_option( 'mailserver_port', '110' );
		$this->set_option( 'ssl', false );
		$this->set_option( 'mailserver_protocol', 'POP3' );
		$this->set_option( 'mailserver_login', 'test@test.com' );
		$this->set_option( 'mailserver_pass', 'password' );
		$this->set_option( 'status', '' );

		$connection_options = array(
			'username' => 'test@test.com',
			'password' => 'password',
			'hostspec' => 'mail.test.com',
			'port' => 110,
			'secure' => false,
		);

		$stub->expects( $this->once() )
			->method( 'open_mailbox_connection' )
			->with( $this->equalTo( $connection_options ) );

		$stub->check_email();

		$this->assertEquals( 'Nothing logged.', $this->get_last_log_message() );
	}

	/**
	* Test checking mailbox with no new messages found.
	*
	* @since    1.0.4
	*/
	public function test_check_email_no_new_messages() {
		$this->set_option( 'status', '' );

		$stub = $this->getMock( 'Post_By_Email', array( 'open_mailbox_connection', 'get_messages', 'close_connection' ), array(), '', false );

		$stub->expects( $this->once() )
			->method( 'open_mailbox_connection' )
			->will( $this->returnValue( true ) );

		$stub->expects( $this->once() )
			->method( 'get_messages' )
			->will( $this->returnValue( array() ) );

		$stub->expects( $this->once() )
			->method( 'close_connection' );

		$stub->check_email();

		$this->assertEquals( "There doesn&#8217;t seem to be any new mail.", $this->get_last_log_message() );
	}

	/**
	* Test checking mailbox and finding a new message.
	*
	* @since    1.0.4
	*/
	public function test_check_mail() {
		$this->set_option( 'status', '' );

		$methods_to_stub = array(
			'open_mailbox_connection',
			'get_messages',
			'close_connection',
			'get_message_headers',
			'get_message_body',
			'save_attachments',
			'mark_as_read',
		);
		$stub = $this->getMock( 'Post_By_Email', $methods_to_stub, array(), '', false );

		$stub->expects( $this->once() )
			->method( 'open_mailbox_connection' )
			->will( $this->returnValue( true ) );

		$message_text = file_get_contents( 'messages/message_with_attachments', true );
		$headers = Horde_Mime_Headers::parseHeaders( $message_text );

		$stub->expects( $this->once() )
			->method( 'get_messages' )
			->will( $this->returnValue( array( 1 ) ) );

		$stub->expects( $this->once() )
			->method( 'get_message_headers' )
			->will( $this->returnValue( $headers ) );

		$message = Horde_Mime_Part::parseMessage( $message_text );
		$body = $message->getPart('1.1')->toString();

		$stub->expects( $this->once() )
			->method( 'get_message_body' )
			->will( $this->returnValue( $body ) );

		$stub->expects( $this->once() )
			->method( 'save_attachments' );

		$stub->expects( $this->once() )
			->method( 'mark_as_read' );

		$stub->expects( $this->once() )
			->method( 'close_connection' );

		$stub->check_email();

		$this->stringContains( "Found 1 new message.", $this->get_last_log_message() );
		$this->assertRegExp( '/Posted(.*?)' . $headers->getValue('Subject') . '/', $this->get_last_log_message() );
	}

}