<?php

/**
 * @file classes/log/EmailLogDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailLogDAO
 * @ingroup log
 * @see EmailLogEntry, Log
 *
 * @brief Class for inserting/accessing email log entries.
 */


import ('lib.pkp.classes.log.EmailLogEntry');

class EmailLogDAO extends DAO {
	/**
	 * Constructor
	 */
	function EmailLogDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a log entry by ID.
	 * @param $logId int
	 * @param $assocType int optional
	 * @param $assocId int optional
	 * @return EmailLogEntry
	 */
	function &getById($logId, $assocType = null, $assocId = null) {
		$params = array((int) $logId);
		if (isset($assocType)) {
			$params[] = (int) $assocType;
			$params[] = (int) $assocId;
		}

		$result =& $this->retrieve(
			'SELECT * FROM email_log WHERE log_id = ?' .
			(isset($assocType)?' AND assoc_type = ? AND assoc_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->build($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all log entries for an object matching the specified association.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $assocType int
	 * @param $assocId int
	 * @return DAOResultFactory containing matching EventLogEntry ordered by sequence
	 */
	function &getByAssoc($assocType = null, $assocId = null, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT	*
			FROM	email_log
			WHERE	assoc_type = ?
				AND assoc_id = ?
			ORDER BY log_id DESC',
			array((int) $assocType, (int) $assocId),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, 'build');
		return $returner;
	}

	/**
	 * Internal function to return an EmailLogEntry object from a row.
	 * @param $row array
	 * @return EmailLogEntry
	 */
	function &build(&$row) {
		$entry = $this->newDataObject();
		$entry->setId($row['log_id']);
		$entry->setAssocType($row['assoc_type']);
		$entry->setAssocId($row['assoc_id']);
		$entry->setSenderId($row['sender_id']);
		$entry->setDateSent($this->datetimeFromDB($row['date_sent']));
		$entry->setIPAddress($row['ip_address']);
		$entry->setEventType($row['event_type']);
		$entry->setFrom($row['from_address']);
		$entry->setRecipients($row['recipients']);
		$entry->setCcs($row['cc_recipients']);
		$entry->setBccs($row['bcc_recipients']);
		$entry->setSubject($row['subject']);
		$entry->setBody($row['body']);

		HookRegistry::call('EmailLogDAO::build', array(&$entry, &$row));

		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry EmailLogEntry
	 */
	function insertObject(&$entry) {
		$this->update(
			sprintf('INSERT INTO email_log
				(sender_id, date_sent, ip_address, event_type, assoc_type, assoc_id, from_address, recipients, cc_recipients, bcc_recipients, subject, body)
				VALUES
				(?, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($entry->getDateSent())),
			array(
				$entry->getSenderId(),
				$entry->getIPAddress(),
				$entry->getEventType(),
				$entry->getAssocType(),
				$entry->getAssocId(),
				$entry->getFrom(),
				$entry->getRecipients(),
				$entry->getCcs(),
				$entry->getBccs(),
				$entry->getSubject(),
				$entry->getBody()
			)
		);

		$entry->setId($this->getInsertLogId());
		return $entry->getId();
	}

	/**
	 * Delete a single log entry for an object.
	 * @param $logId int
	 * @param $assocType int optional
	 * @param $assocId int optional
	 */
	function deleteObject($logId, $assocType = null, $assocId = null) {
		$params = array((int) $logId);
		if (isset($assocType)) {
			$params[] = (int) $assocType;
			$params[] = (int) $assocId;
		}
		return $this->update(
			'DELETE FROM email_log WHERE log_id = ?' .
			(isset($assocType)?' AND assoc_type = ? AND assoc_id = ?':''),
			$params
		);
	}

	/**
	 * Delete all log entries for an object.
	 * @param $assocType int
	 * @praam $assocId int
	 */
	function deleteByAssoc($assocType, $assocId) {
		return $this->update(
			'DELETE FROM email_log WHERE assoc_type = ? AND assoc_id = ?',
			array((int) $assocType, (int) $assocId)
		);
	}

	/**
	 * Transfer all log entries to another user.
	 * @param $oldUserId int
	 * @param $newUserId int
	 */
	function changeUser($oldUserId, $newUserId) {
		return $this->update(
			'UPDATE email_log SET sender_id = ? WHERE sender_id = ?',
			array((int) $newUserId, (int) $oldUserId)
		);
	}

	/**
	 * Get the ID of the last inserted log entry.
	 * @return int
	 */
	function getInsertLogId() {
		return $this->getInsertId('email_log', 'log_id');
	}
}

?>