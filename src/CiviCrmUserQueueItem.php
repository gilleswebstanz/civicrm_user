<?php

namespace Drupal\civicrm_user;

/**
 * Class CiviCrmUserQueueItem.
 *
 * Value object to represent an item of the queue.
 *
 * @package Drupal\civicrm_user
 */
class CiviCrmUserQueueItem {

  const QUEUE_TYPE_MANUAL = 'manual';
  const QUEUE_TYPE_CRON = 'cron';

  const OPERATION_CREATE = 'create';
  const OPERATION_BLOCK = 'block';
  const OPERATION_UPDATE = 'update';

  /**
   * CiviCRM contact.
   *
   * @var array
   */
  private $contact;

  /**
   * CiviCrmUserQueueItem constructor.
   *
   * @param string $operation
   *   The operation to run on the queue item.
   * @param array $contact
   *   The CiviCRM contact that is the subject of the operation.
   */
  public function __construct($operation, array $contact) {
    $this->operation = $operation;
    // @todo validate the contact properties based on the operation
    // because contact can be a full contact or a contact match
    $this->contact = $contact;
  }

  /**
   * Returns a list of possible queues for a queue type.
   *
   * @param string $queue_type
   *   Manual or cron queue type.
   *
   * @return array
   *   List of possible queues for a queue type.
   */
  public static function getQueuesByType($queue_type): array {
    return [
      CiviCrmUserQueueItem::OPERATION_CREATE => 'civicrm_user_' . $queue_type . '_' . CiviCrmUserQueueItem::OPERATION_CREATE,
      CiviCrmUserQueueItem::OPERATION_BLOCK => 'civicrm_user_' . $queue_type . '_' . CiviCrmUserQueueItem::OPERATION_BLOCK,
      CiviCrmUserQueueItem::OPERATION_UPDATE => 'civicrm_user_' . $queue_type . '_' . CiviCrmUserQueueItem::OPERATION_UPDATE,
    ];
  }

  /**
   * Returns the operation to run.
   *
   * @return int
   *   Operation.
   */
  public function getOperation(): int {
    return $this->operation;
  }

  /**
   * Returns the contact that is the subject of the operation.
   *
   * @return array
   *   CiviCRM contact.
   */
  public function getContact(): array {
    return $this->contact;
  }

  /**
   * Returns a common identifier for the contact or contact match.
   *
   * @return int
   *   Identifier of the contact.
   */
  public function getContactId(): string {
    return (int) $this->contact['contact_id'];
  }

}
