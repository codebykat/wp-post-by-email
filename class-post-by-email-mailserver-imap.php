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
 * Mailserver class that uses the built-in IMAP bindings.
 * http://php.net/manual/en/book.imap.php
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 */
class Post_By_Email_Mailserver_IMAP extends Post_By_Email_Mailserver {

	/**
	 * Establishes the connection to the mailserver.
	 *
	 * @since    1.1
	 *
	 * @param    array    $options    Options array
	 *
	 * @return   object
	 */
	public function open_mailbox_connection( $options ) {
		$ssl = $options['secure'] ? '/ssl' : '/notls';
		$this->protocol = $options['protocol'];
		$mailbox = '{' . $options['hostspec'] . ':' . $options['port']
		         . '/' . $options['protocol'] . $ssl . '}INBOX';
		$username = $options['username'];
		$password = $options['password'];
		$this->connection = imap_open( $mailbox, $username, $password );

		$errors = imap_errors();
		foreach ( $errors as $error ) {
			if ( 'SECURITY PROBLEM: insecure server advertised AUTH=PLAIN' === $error ) {
				continue;  // this happens with no SSL, it's fine
			}
			// implicit else: something actually went wrong
			// throw new Exception( 'Error connecting to mail server.' );
		}

		if ( ! $this->connection )
			return false;
		return true;
	}

	/**
	* Closes the connection to the mailserver.
	*
	* @since    1.1
	*/
	public function close_connection() {
		if ( ! $this->connection )
			return true;
		if ( ! imap_ping( $this->connection ) ) {
			return true;
		}
		$result = imap_close( $this->connection );
		$this->connection = null;
		return $result;
	}

	/**
	 * Retrieve the list of new message IDs from the server.
	 *
	 * @since    1.1
	 *
	 * @return   array    Array of message UIDs
	 */
	public function get_messages() {
		if ( ! $this->connection )
			return;

		$messages = imap_search( $this->connection, 'UNSEEN', SE_UID );
		if ( ! $messages ) {
			return array();
		}
		return $messages;
	}

	/**
	 * Retrieve message headers.
	 *
	 * @since    1.1
	 *
	 * @param    int    $uid    Message UID
	 *
	 * @return   object
	 */
	public function get_message_headers( $uid ) {
		if ( ! $this->connection ) {
			return;
		}

		$headers_raw = imap_fetchheader( $this->connection, $uid, FT_UID );
		$headers_parsed = imap_rfc822_parse_headers( $headers_raw );
		$headers = get_object_vars($headers_parsed);

		// standardize headers
		$headers['From'] = $headers['fromaddress'];
		$headers['To'] = $headers['toaddress'];
		$headers['Reply_To'] = $headers['reply_toaddress'];
		$headers['Sender'] = $headers['senderaddress'];

		// "from", "to", "reply_to", and "sender" are still objects
		foreach ( array( 'from', 'to', 'reply_to', 'sender' ) as $key ) {
			$first = $headers[ $key ][0];
			$headers[ $key ] = $first->mailbox . '@' . $first->host;
		}
		return $headers;
	}

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
	public function mark_as_read( $uids, $delete=false ) {
		if ( ! $this->connection ) {
			return;
		}

		$flag = '\Seen';
		if ( $delete || ( 'POP3' === $this->protocol ) ) {
			$flag = '\Deleted';
		}

		return imap_setflag_full( $this->connection, implode( ',', $uids ), '\Seen', ST_UID );
	}

	/**
	 * Get the content of a message from the mailserver.
	 *
	 * @since    1.1
	 *
	 * @param    int       Message UID
	 *
	 * @return   string    Message content
	 */
	public function get_message_body( $uid ) {
		if ( ! $this->connection ) {
			return;
		}

		// imap_body() will only return a verbatim copy of the message body.
		// To extract single parts of a multipart MIME-encoded message you have to use
		// imap_fetchstructure() to analyze its structure and imap_fetchbody() to extract
		// a copy of a single body component.
		$body = imap_body( $this->connection, $uid, FT_UID | FT_PEEK );
		// print $body;
		// die();
		return $body;
	}

	/**
	 * Get a message's attachments from the mail server.
	 *
	 * @since    1.1
	 *
	 * @param    int       Message UID
	 *
	 * @return   array     Attachments
	 */
	public function get_attachments( $uid ) {
		return array();
	}

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
	public function get_single_attachment( $uid, $mime_id ) {
		return '';
	}
}