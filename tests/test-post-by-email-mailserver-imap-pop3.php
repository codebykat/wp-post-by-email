<?php
/**
 * Post By Email Mailserver Unit Tests - IMAP bindings - POP3
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      https://github.com/codebykat/wp-post-by-email
 * @copyright 2013-2015 Kat Hagan / Automattic
 */

/**
 * Mailserver test class - IMAP bindings - POP3
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 * @group PostByEmailMailserver
 * @group PostByEmailMailserverLibraryIMAP
 * @group PostByEmailMailserverProtocolPOP3
 * @group PostByEmailMailserverIMAP_POP3
 */

require_once( 'test-post-by-email-mailserver-pop3.php' );

class Tests_Post_By_Email_Mailserver_IMAP_POP3 extends Tests_Post_By_Email_Mailserver_POP3 {

	// protected static $first_message_id = 1;

	/**
	* Set up the tests.
	*
	* @since    1.1
	*/
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$mailserver = new Post_By_Email_Mailserver_IMAP();
	}
}