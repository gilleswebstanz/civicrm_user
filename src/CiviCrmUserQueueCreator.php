<?php

namespace Drupal\civicrm_user;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;

/**
 * Class CiviCrmUserQueueCreator.
 *
 * Creates a queue of CiviCrmUserQueueItems to be processed.
 */
class CiviCrmUserQueueCreator implements CiviCrmUserQueueCreatorInterface {

  /**
   * Drupal\Core\Queue\QueueFactory definition.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Drupal\civicrm_user\CiviCrmUserMatcherInterface definition.
   *
   * @var \Drupal\civicrm_user\CiviCrmUserMatcherInterface
   */
  protected $matcher;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new CiviCrmUserQueue object.
   */
  public function __construct(QueueFactory $queue_factory, CiviCrmUserMatcherInterface $matcher, StateInterface $state) {
    $this->queueFactory = $queue_factory;
    $this->matcher = $matcher;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function addItems($queue_type) : int {
    $result = 0;
    // @todo verify if use state is needed to check if the manual or cron queues are running
    // Create a queue for every worker.
    $createQueue = $this->queueFactory->get(CiviCrmUserQueueItem::getQueuesByType($queue_type)[CiviCrmUserQueueItem::OPERATION_CREATE]);
    $blockQueue = $this->queueFactory->get(CiviCrmUserQueueItem::getQueuesByType($queue_type)[CiviCrmUserQueueItem::OPERATION_BLOCK]);
    $updateQueue = $this->queueFactory->get(CiviCrmUserQueueItem::getQueuesByType($queue_type)[CiviCrmUserQueueItem::OPERATION_UPDATE]);

    $existingMatches = $this->matcher->getExistingMatches();
    $candidateMatches = $this->matcher->getCandidateMatches();

    // Create users that are not in the existing matches.
    $usersToCreate = array_diff_key($candidateMatches, $existingMatches);
    foreach ($usersToCreate as $contact) {
      $item = new CiviCrmUserQueueItem(CiviCrmUserQueueItem::OPERATION_CREATE, $contact);
      $createQueue->createItem($item);
      $result++;
    }

    // Block existing matches that are not candidates
    // for a user account anymore.
    $usersToBlock = array_diff_key($existingMatches, $candidateMatches);
    foreach ($usersToBlock as $contactMatch) {
      $item = new CiviCrmUserQueueItem(CiviCrmUserQueueItem::OPERATION_BLOCK, $contactMatch);
      $blockQueue->createItem($item);
      $result++;
    }

    // Update and unblock all other existing matches.
    $usersToUpdate = array_diff_key($candidateMatches, $usersToBlock);
    foreach ($usersToUpdate as $contact) {
      $item = new CiviCrmUserQueueItem(CiviCrmUserQueueItem::OPERATION_UPDATE, $contact);
      $updateQueue->createItem($item);
      $result++;
    }

    return $result;
  }

}
