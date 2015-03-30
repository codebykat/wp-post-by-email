<?php
/**
 * Post By Email Mailserver POP3 Unit Tests
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      https://github.com/codebykat/wp-post-by-email
 * @copyright 2013-2015 Kat Hagan / Automattic
 */

/**
 * Abstract Mailserver POP3 test class. Mailserver tests should inherit from this.
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 */
require_once( 'test-post-by-email-mailserver.php' );
abstract class Tests_Post_By_Email_Mailserver_POP3 extends Tests_Post_By_Email_Mailserver {

	// DovecotTesting config
	protected static $connection_options = array(
		'protocol' => 'POP3',
		'username' => 'testuser',
		'password' => 'applesauce',
		'hostspec' => '172.31.1.2',
		'port' => 110,
		'secure' => false,
	);

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
	* Test opening a mailbox connection with bad options.
	*
	* @since    1.1
	*/
	public function test_open_mailbox_connection_with_bad_options_should_throw_exception( $connection_options = null ) {
		parent::test_open_mailbox_connection_with_bad_options_should_throw_exception( self::$connection_options );
	}

	/**
	* Test opening a mailbox connection with default options.
	*
	* @since    1.1
	*/
	public function test_open_mailbox_connection( $connection_options = null ) {
		parent::test_open_mailbox_connection( self::$connection_options );
	}
}