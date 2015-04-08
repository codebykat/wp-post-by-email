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
 * @group   PostByEmailCore
 * @coversDefaultClass Post_By_Email
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
		$options['status'] = '';
		update_option( 'post_by_email_options', $options );
	}

	/** HELPER FUNCTIONS **/

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


	/** TESTS **/

	/**
	* Test plugin activation.
	*
	* @since    0.9.7
	* @covers   ::activate
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
	* @covers   ::activate
	* @covers   ::deactivate
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
	* @covers   ::check_email
	*/
	public function test_return_if_options_not_set() {
		$this->set_option( 'mailserver_url', 'mail.example.com' );
		$this->set_option( 'status', 'unconfigured' );

		$stub = $this->getMock( 'Post_By_Email', array( 'open_mailbox', 'get_messages', 'close_connection' ), array(), '', false );

		$stub->check_email();

		// should immediately return without doing anything
		$stub->expects( $this->never() )
		     ->method( 'open_mailbox' );

		$stub->expects( $this->never() )
		     ->method( 'get_messages' );

		$this->assertEquals( 'Nothing logged.', $this->get_last_log_message() );
	}

	/**
	* Test checking mailbox with no new messages found.
	*
	* @since    1.0.4
	* @covers   ::check_email
	*/
	public function test_check_email_no_new_messages() {
		$stub = $this->getMock( 'Post_By_Email', array( 'open_mailbox', 'get_messages', 'close_mailbox' ), array(), '', false );

		$this->set_option( 'mailserver_url', 'mail.test.com' );
		$this->set_option( 'mailserver_port', '110' );
		$this->set_option( 'ssl', false );
		$this->set_option( 'mailserver_protocol', 'POP3' );
		$this->set_option( 'mailserver_login', 'test@test.com' );
		$this->set_option( 'mailserver_pass', 'password' );
		$this->set_option( 'status', '' );

		$connection_options = array(
			'protocol' => 'POP3',
			'username' => 'test@test.com',
			'password' => 'password',
			'hostspec' => 'mail.test.com',
			'port'     => 110,
			'secure'   => false,
		);

		$stub->expects( $this->once() )
		     ->method( 'open_mailbox' )
		     ->with( $this->equalTo( $connection_options ) )
		     ->will( $this->returnValue( true ) );

		$stub->expects( $this->once() )
		     ->method( 'get_messages' )
		     ->will( $this->returnValue( array() ) );

		$stub->expects( $this->once() )
		     ->method( 'close_mailbox' );

		$stub->check_email();

		$this->assertEquals( "There doesn&#8217;t seem to be any new mail.", $this->get_last_log_message() );
	}

	/**
	* Test checking mailbox and finding a new message.
	*
	* @since    1.0.4
	* @covers   ::check_email
	*/
	public function test_check_mail() {
		$methods_to_stub = array(
			'open_mailbox',
			'get_messages',
			'close_mailbox',
			'create_post',
			'save_attachments',
			'mark_as_read',
		);
		$stub = $this->getMock( 'Post_By_Email', $methods_to_stub, array(), '', false );

		$stub->expects( $this->once() )
		     ->method( 'open_mailbox' )
		     ->will( $this->returnValue( true ) );

		$stub->expects( $this->once() )
		     ->method( 'get_messages' )
		     ->will( $this->returnValue( array( 1 ) ) );

		$new_post_ID = wp_insert_post( array( 'post_title' => 'Test post', 'post_content' => 'This is a test' ) );

		$stub->expects( $this->once() )
		     ->method( 'create_post' )
		     ->will( $this->returnValue( $new_post_ID ) );

		$stub->expects( $this->once() )
		     ->method( 'save_attachments' );

		$stub->expects( $this->once() )
		     ->method( 'mark_as_read' );

		$stub->expects( $this->once() )
		     ->method( 'close_mailbox' );

		$stub->check_email();

		$this->stringContains( "Found 1 new message.", $this->get_last_log_message() );
		$this->assertRegExp( '/Posted(.*?)' . 'Test post' . '/', $this->get_last_log_message() );

		// make sure we set the last checked time
		$timestamp = current_time( 'timestamp', true );
		$last_checked = $this->get_option( 'last_checked' );
		$this->assertEquals( $timestamp, $last_checked );
	}

	/**
	* Helper function to set up the stub for create_post.
	*
	* @since    1.1
	*/
	protected function create_post_stub() {
		$methods_to_stub = array(
			'get_message_headers',
			'get_message_author',
			'send_response',
			'get_admin_id',
			'get_message_date',
			'get_message_body',
			'find_shortcode',
			'filter_valid_shortcodes',
			'save_error_message',
		);
		$stub = $this->getMock( 'Post_By_Email', $methods_to_stub, array(), '', false );

		$message_text = file_get_contents( 'messages/message_with_attachments', true );
		$headers = Horde_Mime_Headers::parseHeaders( $message_text );

		$headers_array = array(
			'Date'    => $headers->getValue( 'Date' ),
			'Subject' => $headers->getValue( 'Subject' ),
			'From'    => $headers->getValue( 'From' ),
		);

		$stub->expects( $this->once() )
		     ->method( 'get_message_headers' )
		     ->will( $this->returnValue( $headers_array ) );

		$message = Horde_Mime_Part::parseMessage( $message_text );
		$body = $message->getPart( '1.1' )->toString();

		$stub->method( 'get_message_body' )
		     ->will( $this->returnValue( $body ) );

		$stub->method( 'find_shortcode' )
		     ->will( $this->returnValue( array() ) );

		return $stub;
	}

	/**
	* Test creating a post from an email.
	*
	* @since    1.1
	* @covers   ::create_post
	*/
	public function test_create_post_from_email() {
		$stub = $this->create_post_stub();
		$post_ID = $stub->create_post( 1, 0 );
		$post = get_post( $post_ID );
		$this->assertInstanceOf( 'WP_Post', $post );
	}

	/**
	* Test that posts from unknown address are discarded if that option is set.
	*
	* @since    1.1
	* @covers   ::create_post
	*/
	public function test_post_from_unknown_user_should_be_discarded() {
		$this->set_option( 'discard_pending', true );
		$stub = $this->create_post_stub();

		$post_ID = $stub->create_post( 1, 0 );
		$this->assertFalse( $post_ID );
	}

	/**
	* Test that posts from unknown address are set to pending if that option is set.
	*
	* @since    1.1
	* @covers   ::create_post
	*/
	public function test_post_from_unknown_user_should_pending() {
		$this->set_option( 'discard_pending', false );
		$stub = $this->create_post_stub();
		$post_ID = $stub->create_post( 1, 0 );
		$post = get_post( $post_ID );
		$this->assertInstanceOf( 'WP_Post', $post );
		$this->assertEquals( 'pending', $post->post_status );
	}

	/**
	* Test that posts from registered addresses are set to draft if that option is set.
	*
	* @since    1.1
	* @covers   ::create_post
	*/
	public function test_post_from_registered_user_should_draft() {
		$this->set_option( 'registered_pending', true );
		$stub = $this->create_post_stub();

		$admin_email = get_option( 'admin_email' );
		$stub->expects( $this->once() )
		     ->method( 'get_message_author' )
		     ->will( $this->returnValue( $admin_email ) );

		$post_ID = $stub->create_post( 1, 0 );
		$post = get_post( $post_ID );
		$this->assertInstanceOf( 'WP_Post', $post );
		$this->assertEquals( 'draft', $post->post_status );
	}

	/**
	* Test that posts from registered addresses are published if that option is set.
	*
	* @since    1.1
	* @covers   ::create_post
	*/
	public function test_post_from_registered_user_should_publish() {
		$this->set_option( 'registered_pending', false );
		$stub = $this->create_post_stub();

		$admin_email = get_option( 'admin_email' );
		$stub->expects( $this->once() )
		     ->method( 'get_message_author' )
		     ->will( $this->returnValue( $admin_email ) );

		$post_ID = $stub->create_post( 1, 0 );
		$post = get_post( $post_ID );
		$this->assertInstanceOf( 'WP_Post', $post );
		$this->assertEquals( 'publish', $post->post_status );
	}

	/**
	* Test that checking mail properly reschedules the next check.
	*
	* @since    1.1
	* @covers   ::manual_check_email
	*/
	public function test_checking_mail_reschedules_the_next_automatic_check() {
		$stub = $this->getMock( 'Post_By_Email', array( 'check_email', 'redirect' ), array(), '', false );

		$stub->expects( $this->once() )
		     ->method( 'check_email' );

		$stub->expects( $this->once() )
		     ->method( 'redirect' );

		$timestamp = current_time( 'timestamp', true ) + HOUR_IN_SECONDS;
		$stub->manual_check_email();

		$next_scheduled = wp_next_scheduled( 'post-by-email-wp-mail.php' );
		$this->assertNotEquals( false, $next_scheduled );
		$this->assertEquals( $timestamp, $next_scheduled );
	}

	/**
	* Test getting the site's admin ID.
	*
	* @since    1.1
	* @covers   ::get_admin_id
	*/
	public function test_get_admin_id() {
		$admin_email = get_option( 'admin_email' );
		$admin = get_user_by( 'email', $admin_email );

		$id = $this->plugin->get_admin_id();
		$this->assertEquals( $admin->ID, $id );
	}

	/**
	* Test getting the message author from the message headers.
	*
	* @since    1.1
	* @covers   ::get_message_author
	*/
	public function test_get_message_author_returns_correct_author() {
		$admin_email = get_option( 'admin_email' );
		$headers = array( 'From' => $admin_email );
		$author = $this->plugin->get_message_author( $headers );
		$this->assertNotEquals( false, $author );
		$this->assertEquals( $admin_email, $author );
	}

	/**
	* Test getting the message author from the message headers with an invalid email.
	*
	* @since    1.1
	* @covers   ::get_message_author
	*/
	public function test_get_message_author_returns_false_if_invalid() {
		$headers = array( 'From' => 'not an email address' );
		$author = $this->plugin->get_message_author( $headers );
		$this->assertFalse( $author );
	}

	/**
	* Getting the message date from headers should return current time if invalid.
	*
	* @since    1.1
	* @covers   ::get_message_date
	*/
	public function test_get_message_date_returns_now_if_invalid() {
		$headers = array( 'Date' => '' );
		$timestamp = current_time( 'timestamp', true );
		$date = $this->plugin->get_message_date( $headers );
		$this->assertEquals( $timestamp, $date );
	}

	/**
	* Test getting the message date from the message headers.
	*
	* @since    1.1
	* @covers   ::get_message_date
	*/
	public function test_get_message_date_returns_correct_timestamp() {
		$headers = array( 'Date' => 'Fri, 27 Mar 2015 01:40:04 +0000' );
		$date = $this->plugin->get_message_date( $headers );
		$this->assertEquals( 1427420404, $date );
	}

	/**
	* Test getting the message date from the message headers, without the day name.
	*
	* @since    1.1
	* @covers   ::get_message_date
	*/
	public function test_get_message_date_returns_correct_timestamp_without_day_name() {
		$headers = array( 'Date' => '27 Mar 2015 01:40:04 +0000' );
		$date = $this->plugin->get_message_date( $headers );
		$this->assertEquals( 1427420404, $date );
	}

	/**
	* Test getting the message date from the message headers, without the timezone.
	*
	* @since    1.1
	* @covers   ::get_message_date
	*/
	public function test_get_message_date_returns_correct_timestamp_without_timezone() {
		$headers = array( 'Date' => 'Fri, 27 Mar 2015 01:40:04' );
		$date = $this->plugin->get_message_date( $headers );
		$this->assertEquals( 1427420404, $date );
	}

	/**
	* Test getting the message date from the message headers, without the seconds.
	*
	* @since    1.1
	* @covers   ::get_message_date
	*/
	public function test_get_message_date_returns_correct_timestamp_without_seconds() {
		$headers = array( 'Date' => 'Fri, 27 Mar 2015 01:40 +0000' );
		$date = $this->plugin->get_message_date( $headers );
		$this->assertEquals( 1427420400, $date );
	}

	/**
	* Test getting the message attachments and uploading them to a post.
	*
	* @since    1.1
	* @covers   ::save_attachments
	*/
	public function test_save_attachments() {
		// This will test $stub->save_attachments();
		$this->markTestIncomplete();
	}

	/**
	* Test sending an email response.
	*
	* @since    1.1
	* @covers   ::send_response
	*/
	public function test_send_response() {
		// This will test $stub->send_response();
		$this->markTestIncomplete();
	}

	/**
	* Test finding a shortcode in the message content and returning its arguments.
	*
	* @since    1.1
	* @covers   ::find_shortcode
	*/
	public function test_find_shortcode() {
		// This will test $stub->find_shortcode();
		$this->markTestIncomplete();
	}

	/**
	* Test filtering shortcodes out of the message content.
	*
	* @since    1.1
	* @covers   ::filter_valid_shortcodes
	*/
	public function test_filter_valid_shortcodes() {
		// This will test $stub->filter_valid_shortcodes();
		$this->markTestIncomplete();
	}

	/**
	* Test saving a log message.
	*
	* @since    1.1
	* @covers   ::save_log_message
	*/
	public function test_save_log_message() {
		$log_message = 'This is a test.';
		$timestamp = current_time( 'timestamp' );
		$this->plugin->save_log_message( $log_message );

		$log = get_option( 'post_by_email_log' );
		$this->assertNotEmpty( $log );

		$last_entry = array_shift( $log );
		$this->assertEquals( $timestamp, $last_entry['timestamp'] );
		$this->assertEquals( $log_message, $last_entry['message'] );

		$status = $this->get_option( 'status' );
		$this->assertNotEquals( 'error', $status );
	}

	/**
	* Test saving an error message.
	*
	* @since    1.1
	* @covers   ::save_log_message
	*/
	public function test_save_error_message() {
		$log_message = 'This is a test.';
		$timestamp = current_time( 'timestamp' );
		$this->plugin->save_error_message( $log_message );

		$log = get_option( 'post_by_email_log' );
		$this->assertNotEmpty( $log );

		$last_entry = array_shift( $log );
		$this->assertEquals( $timestamp, $last_entry['timestamp'] );
		$this->assertEquals( $log_message, $last_entry['message'] );

		$status = $this->get_option( 'status' );
		$this->assertEquals( 'error', $status );

		$transient = get_transient( 'post_by_email_last_checked' );
		$this->assertFalse( $transient );
	}
}