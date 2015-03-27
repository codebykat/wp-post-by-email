<?php
/**
 * Post By Email
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      https://github.com/codebykat/wp-post-by-email/
 * @copyright 2013-2015 Kat Hagan / Automattic
 */

/**
 * Abstract Mailserver class.
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 */
abstract class Post_By_Email_Mailserver {

	/**
	* Active connection.
	*
	* @since    1.1
	*
	* @var      object
	*/
	protected $connection;

	/**
	* Connection protocol (POP3 or IMAP).
	*
	* @since    1.1
	*
	* @var      string
	*/
	protected $protocol;

	/**
	 * Establishes the connection to the mailserver.
	 *
	 * @since    1.1
	 *
	 * @param    array    $options    Options array
	 *
	 * @return   object
	 */
	abstract public function open_mailbox_connection( $connection_options );

	/**
	* Closes the connection to the mailserver.
	*
	* @since    1.1
	*/
	abstract public function close_connection();

	/**
	 * Retrieve the list of new message IDs from the server.
	 *
	 * @since    1.1
	 *
	 * @return   array    Array of message UIDs
	 * @throws   Horde_Imap_Client_Exception
	 */
	abstract public function get_messages();

	/**
	 * Retrieve message headers.
	 *
	 * @since    1.1
	 *
	 * @param    int    $uid    Message UID
	 *
	 * @return   object
	 */
	abstract public function get_message_headers( $id );

	/**
	 * Mark a list of messages read on the server.
	 *
	 * @since    1.1
	 *
	 * @param    array    $uids      UIDs of messages to mark read
	 * @param    bool     $delete    Whether to delete read messages
	 *
	 * @throws   Horde_Imap_Client_Exception
	 */
	abstract public function mark_as_read( $uids, $delete=false );

	/**
	 * Get the content of a message from the mailserver.
	 *
	 * @since    1.1
	 *
	 * @param    int       Message UID
	 *
	 * @return   string    Message content
	 */
	abstract public function get_message_body( $uid );

	/**
	 * Get a message's attachments from the mail server.
	 *
	 * @since    1.1
	 *
	 * @param    int       Message UID
	 *
	 * @return   array     Attachments
	 */
	abstract public function get_attachments( $uid );

	/**
	 * Get a single attachment from the mail server.
	 *
	 * @since    1.1
	 *
	 * @param    int       Message UID
	 * @param    int       Attachment's MIME ID
	 *
	 * @return   string    Decoded attachment data
	 */
	abstract public function get_single_attachment( $uid, $mime_id );
}