<?php

namespace Drupal\civicrm_user;

/**
 * Interface CiviCrmUserQueueCreatorInterface.
 */
interface CiviCrmUserQueueCreatorInterface {

  const PAGE_ITEMS = 25;

  /**
   * Enqueues the CiviCrmUserQueueItem instances to be processed.
   *
   * @param string $queue_type
   *   Manual or cron queue.
   *
   * @return int
   *   Amount of items that were added.
   */
  public function addItems($queue_type): int;

}
