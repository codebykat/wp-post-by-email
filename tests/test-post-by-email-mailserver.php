<?php
/**
 * Post By Email Mailserver Unit Tests
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      https://github.com/codebykat/wp-post-by-email
 * @copyright 2013-2015 Kat Hagan / Automattic
 */

/**
 * Abstract Mailserver test class. Mailserver tests should inherit from this.
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 */
abstract class Tests_Post_By_Email_Mailserver extends WP_UnitTestCase {

	/**
	 * Instantiation of the mailserver library.
	 *
	 * @since   1.1
	 *
	 * @var     object
	 */
	protected static $mailserver;

	// DovecotTesting config
	protected static $connection_options = array(
		'protocol' => 'IMAP',
		'username' => 'testuser',
		'password' => 'applesauce',
		'hostspec' => '172.31.1.2',
		'port' => 143,
		'secure' => false,
	);

	// Some message info to test
	protected static $first_message_id = 8;

	/**
	* Before all tests: set options and start up / reset the test mailserver.
	*
	* @since    1.1
	*/
	public static function setUpBeforeClass() {
		if ( getenv( 'TRAVIS') ) {
			self::$connection_options['hostspec'] = '127.0.0.1';
		} else {
			shell_exec( 'bash ' . plugin_dir_path( __FILE__ ) . '../vendor/tedivm/dovecottesting/SetupEnvironment.sh' );
		}

		// child classes MUST instantiate self::$mailserver in their setUpBeforeClass() functions.
	}

	/**
	* Before every test: connect to mailserver.
	*
	* @since    1.1
	*/
	public function setUp() {
		self::$mailserver->open_mailbox_connection( self::$connection_options );
	}

	/**
	* After every test: close mailbox connection.
	*
	* @since    1.1
	*/
	public function tearDown() {
		self::$mailserver->close_connection();
	}

	/**
	* Test opening a mailbox connection with bad options.
	*
	* @since    1.1
	*/
	public function test_open_mailbox_connection_with_bad_options_should_throw_exception() {
		self::$mailserver->close_connection();

		$connection_options = self::$connection_options;
		$connection_options['hostspec'] = 'mail.example.com';

		try {
			self::$mailserver->open_mailbox_connection( $connection_options );
		} catch ( Exception $e ) {
			$this->assertNotNull( $e->getMessage() );
			return;
		}
		$this->fail( 'Expected exception was not thrown.' );
	}

	/**
	* Test opening a mailbox connection (IMAP / no SSL).
	*
	* @since    1.1
	*/
	public function test_open_mailbox_connection_IMAP() {
		self::$mailserver->close_connection();

		$return = self::$mailserver->open_mailbox_connection( self::$connection_options );
		$this->assertTrue( $return );
	}

	/**
	* Test opening a mailbox connection (POP3 / no SSL).
	*
	* @since    1.1
	*/
	public function test_open_mailbox_connection_POP3() {
		self::$mailserver->close_connection();

		$connection_options = self::$connection_options;
		$connection_options['protocol'] = 'POP3';
		$connection_options['port'] = 110;
		$return = self::$mailserver->open_mailbox_connection( $connection_options );
		$this->assertTrue( $return );
	}

	/**
	* Test retrieving message IDs from the mailbox.
	*
	* @since    1.1
	*/
	public function test_get_messages() {
		$uids = self::$mailserver->get_messages();
		$this->assertNotEmpty( $uids );
		$this->assertCount( 3, $uids );
		$this->assertEquals( self::$first_message_id, $uids[0] );
	}

	/**
	* Test getting message headers.
	*
	* @since    1.1
	*/
	public function test_get_message_headers() {
		$headers = self::$mailserver->get_message_headers( self::$first_message_id );
		$this->assertInternalType( 'array', $headers );
		$this->assertNotEmpty( $headers );
		$this->assertArrayHasKey( 'Date', $headers );
		$this->assertArrayHasKey( 'Subject', $headers );
		$this->assertArrayHasKey( 'From', $headers );
		$this->assertInternalType( 'string', $headers['From'] );
	}

	/**
	* Test getting message body.
	*
	* @since    1.1
	*/
	public function test_get_message_body() {
		$body = self::$mailserver->get_message_body( self::$first_message_id );
		$this->assertNotEmpty( $body );
	}

	/**
	* Test getting message attachments.
	*
	* @since    1.1
	*/
	public function test_get_attachments() {
		$attachments = self::$mailserver->get_attachments( self::$first_message_id );
		$this->assertInternalType( 'array', $attachments );
		$this->markTestIncomplete( 'Pending message ID with attachments' );
	// 	$this->assertNotEmpty( $attachments );
	}

	/**
	* Test marking message IDs as read.
	*
	* @since    1.1
	*/
	public function test_mark_as_read() {
		$result = self::$mailserver->mark_as_read( array( self::$first_message_id ) );
		$this->assertTrue( $result );

		$uids = self::$mailserver->get_messages();
		$this->assertNotContains( 8, $uids );
	}
}