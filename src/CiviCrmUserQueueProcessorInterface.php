<?php

namespace Drupal\civicrm_user;

/**
 * Interface CiviCrmUserQueueInterface.
 */
interface CiviCrmUserQueueProcessorInterface {

  /**
   * Processes the queue of CiviCrmUserQueueItem instances.
   *
   * @param int $queue_type
   *   Manual or cron queue.
   */
  public function processItems($queue_type) : void;

  /**
   * Returns the number of items for each queue of a queue type.
   *
   * @param string $queue_type
   *   Manual or cron queue type.
   *
   * @return array
   *   Number of items indexed by operation.
   */
  public function getNumberOfItemsPerQueue($queue_type): array;

  /**
   * Returns the total number of items for each queue of a queue type.
   *
   * @param string $queue_type
   *   Manual or cron queue type.
   *
   * @return int
   *   Number of items.
   */
  public function getNumberOfItems($queue_type): int;

}
