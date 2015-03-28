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

	/**
	* Set up the tests.
	*
	* @since    1.1
	*/
	public static function setUpBeforeClass() {
		if ( getenv( 'TRAVIS') ) {
			self::$connection_options['hostspec'] = '127.0.0.1';
		} else {
			// spin up email server
			shell_exec( 'bash ' . plugin_dir_path( __FILE__ ) . '../vendor/tedivm/dovecottesting/SetupEnvironment.sh' );
		}

		// child classes MUST instantiate self::$mailserver in their setUpBeforeClass() functions.
	}

	/**
	* Close mailbox connection.
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
		$return = self::$mailserver->open_mailbox_connection( self::$connection_options );
		$this->assertTrue( $return );
	}

	/**
	* Test opening a mailbox connection (POP3 / no SSL).
	*
	* @since    1.1
	*/
	public function test_open_mailbox_connection_POP3() {
		$connection_options = self::$connection_options;
		$connection_options['protocol'] = 'POP3';
		$connection_options['port'] = 110;
		$return = self::$mailserver->open_mailbox_connection( $connection_options );
		$this->assertTrue( $return );
	}

	/**
	* Test checking mailbox and finding new messages.
	*
	* @since    1.1
	*/
	public function test_get_messages() {
		self::$mailserver->open_mailbox_connection( self::$connection_options );
		$uids = self::$mailserver->get_messages();
		$this->assertNotEmpty( $uids );
		$this->assertCount( 3, $uids );
		$this->assertEquals( 8, $uids[0] );
	}

}