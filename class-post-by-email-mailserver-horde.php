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
 * Horde Mailserver class.
 *
 * @package PostByEmail
 * @author  Kat Hagan <kat@codebykat.com>
 */
class Post_By_Email_Mailserver_Horde extends Post_By_Email_Mailserver {

	/**
	 * Establishes the connection to the mailserver.
	 *
	 * @since    1.1
	 *
	 * @param    array    $options    Options array
	 *
	 * @return   object
	 */
	public function open_mailbox_connection( $connection_options ) {
		if ( 'POP3' === $connection_options['protocol'] ) {
			$this->protocol = 'POP3';
			$this->connection = new Horde_Imap_Client_Socket_Pop3( $connection_options );
		} else {  // IMAP
			$this->protocol = 'IMAP';
			$this->connection = new Horde_Imap_Client_Socket( $connection_options );
		}
		$this->connection->_setInit( 'authmethod', 'USER' );
		$this->connection->login();
		return true;
	}

	/**
	* Closes the connection to the mailserver.
	*
	* @since    1.1
	*/
	public function close_connection() {
		if ( ! $this->connection )
			return;

		$this->connection->shutdown();
	}

	/**
	 * Retrieve the list of new message IDs from the server.
	 *
	 * @since    1.1
	 *
	 * @return   array    Array of message UIDs
	 * @throws   Horde_Imap_Client_Exception
	 */
	public function get_messages() {
		if ( ! $this->connection )
			return;

		$search_query = new Horde_Imap_Client_Search_Query();

		// POP3 doesn't understand about read/unread messages
		if ( 'POP3' !== $this->protocol ) {
			$search_query->flag( Horde_Imap_Client::FLAG_SEEN, false );
		}

		$test = $this->connection->search( 'INBOX', $search_query );
		$uids = $test['match'];

		return $uids->ids;
	}

	/**
	 * Retrieve message headers.
	 *
	 * @since    1.1
	 *
	 * @param    int    $id    Message UID
	 *
	 * @return   object
	 */
	public function get_message_headers( $id ) {
		if ( ! $this->connection )
			return;

		if ( 'POP3' === $this->protocol ) {
			$uid = new Horde_Imap_Client_Ids_Pop3( $id );
		} else {
			$uid = new Horde_Imap_Client_Ids( $id );
		}

		$headerquery = new Horde_Imap_Client_Fetch_Query();
		$headerquery->headerText();
		$headerlist = $this->connection->fetch( 'INBOX', $headerquery, array(
				'ids' => $uid,
			)
		);

		$headers = $headerlist->first()->getHeaderText( 0, Horde_Imap_Client_Data_Fetch::HEADER_PARSE );
		return $headers->toArray();
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
	public function mark_as_read( $ids, $delete=false ) {
		if ( ! $this->connection )
			return;

		if ( 'POP3' === $this->protocol ) {
			$uids = new Horde_Imap_Client_Ids_Pop3( $ids );
		} else {
			$uids = new Horde_Imap_Client_Ids( $ids );
		}

		$flag = Horde_Imap_Client::FLAG_SEEN;
		if ( $delete || ( 'POP3' === $this->protocol ) )
			$flag = Horde_Imap_Client::FLAG_DELETED;

		$this->connection->store( 'INBOX', array(
				'add' => array( $flag ),
				'ids' => $uids,
			)
		);
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
	public function get_message_body( $id ) {
		if ( ! $this->connection ) {
			return;
		}

		if ( 'POP3' === $this->protocol ) {
			$uid = new Horde_Imap_Client_Ids_Pop3( $id );
		} else {
			$uid = new Horde_Imap_Client_Ids( $id );
		}

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->structure();

		$list = $this->connection->fetch( 'INBOX', $query, array(
				'ids' => $uid,
			)
		);

		$part = $list->first()->getStructure();
		$body_id = $part->findBody('html');
		if ( is_null( $body_id ) ) {
			$body_id = $part->findBody();
		}
		$body = $part->getPart( $body_id );

		$query2 = new Horde_Imap_Client_Fetch_Query();
		$query2->bodyPart( $body_id, array(
				'decode' => true,
				'peek' => true,
			)
		);

		$list2 = $this->connection->fetch( 'INBOX', $query2, array(
				'ids' => $uid,
			)
		);

		$message2 = $list2->first();
		$content = $message2->getBodyPart( $body_id, true );
		if ( ! $message2->getBodyPartDecode( $body_id ) ) {
			// Quick way to transfer decode contents
			$body->setContents( $content );
			$content = $body->getContents();
		}

		$content = strip_tags( $content, '<img><p><br><i><b><u><em><strong><strike><font><span><div><style><a>' );
		$content = trim( $content );

		// encode to UTF-8; this fixes up unicode characters like smart quotes, accents, etc.
		$charset = $body->getCharset();
		if ( 'iso-8859-1' === $charset ) {
			$content = utf8_encode( $content );
		} elseif ( function_exists( 'iconv' ) ) {
			$content = iconv( $charset, 'UTF-8', $content );
		}

		return $content;
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
	public function get_attachments( $id ) {
		if ( ! $this->connection )
			return;

		if ( 'POP3' === $this->protocol ) {
			$uid = new Horde_Imap_Client_Ids_Pop3( $id );
		} else {
			$uid = new Horde_Imap_Client_Ids( $id );
		}

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->structure();

		$list = $this->connection->fetch( 'INBOX', $query, array(
				'ids' => $uid,
			)
		);

		$part = $list->first()->getStructure();
		$map = $part->ContentTypeMap();

		$attachments = array();

		foreach ( $map as $key => $value ) {
			$p = $part->getPart( $key );

			$disposition = $p->getDisposition();
			if ( ! in_array( $disposition, array( 'attachment', 'inline' ) ) ) {
				continue;
			}

			$name = $p->getName();
			if ( ! $name ) {
				// sometimes (usually with inline images), we don't have a filename
				// this will call it something like inline-1.jpg
				$allowed_extensions = get_allowed_mime_types();
				$possible_extensions = array_search( $p->getType(), $allowed_extensions );
				$ext = array_shift( explode( '|', $possible_extensions ) );
				$name = $disposition . '-' . ( count( $attachments ) + 1 ) . '.' . $ext;
			}

			$new_attachment = array(
				'disposition' => $disposition,
				'type'        => $p->getPrimaryType(),
				'mimetype'    => $p->getType(),
				'mime_id'     => $key,
				'name'        => $name,
			);

			$attachments[] = $new_attachment;
		}

		return $attachments;
	}

	/**
	 * Get a single attachment from the mail server.
	 *
	 * @since    1.1
	 *
	 * @param    ID        Message ID
	 * @param    mime_id   Attachment's MIME ID
	 *
	 * @return   string    Decoded attachment data
	 */
	public function get_single_attachment( $id, $mime_id ) {
		if ( ! $this->connection )
			return;

		if ( 'POP3' === $this->protocol ) {
			$uid = new Horde_Imap_Client_Ids_Pop3( $id );
		} else {
			$uid = new Horde_Imap_Client_Ids( $id );
		}

		$query = new Horde_Imap_Client_Fetch_Query();
		$query->bodyPart( $mime_id, array(
				'decode' => true,
				'peek' => true,
			)
		);

		$list = $this->connection->fetch( 'INBOX', $query, array(
				'ids' => $uid,
			)
		);

		$message = $list->first();

		$image_data = $message->getBodyPart( $mime_id );
		$image_data_decoded = base64_decode( $image_data );
		return $image_data_decoded;
	}
}