<?php

namespace Drupal\civicrm_user;

/**
 * Interface CiviCrmUserQueueInterface.
 */
interface CiviCrmUserProcessorInterface {

  const PAGE_ITEMS = 25;

  /**
   * Enqueues the contacts to be checked for a match.
   */
  public function prepareContactQueue();

  /**
   * Processes the queue per Contact item with the User matcher.
   */
  public function processContactQueue();

}
