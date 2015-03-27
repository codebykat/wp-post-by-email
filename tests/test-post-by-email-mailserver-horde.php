<?php
/**
 * Post By Email Mailserver Unit Tests - Horde library
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      https://github.com/codebykat/wp-post-by-email
 * @copyright 2013-2015 Kat Hagan / Automattic
 */

/**
 * Mailserver test class - Horde library.
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 * @group PostByEmailMailserverHorde
 */

require_once( 'test-post-by-email-mailserver.php' );

class Tests_Post_By_Email_Mailserver_Horde extends Tests_Post_By_Email_Mailserver {
	/**
	* Set up the tests.
	*
	* @since    1.1
	*/
	public function setUp() {
		parent::setUp();
		$this->mailserver = new Post_By_Email_Mailserver_Horde();
	}
}