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
   * Add items for a queue type for each worker (operation).
   *
   * @param string $queue_type
   *   Manual or cron queue.
   * @param array $contacts
   *   List of CiviCRM contacts.
   * @param string $operation
   *   Operation to apply on Drupal users.
   *
   * @return int
   *   Number of items that were added to the queue.
   */
  private function addItemsForOperation($queue_type, array $contacts, $operation): int {
    $result = 0;
    $config = \Drupal::configFactory()->get('civicrm_user.settings');
    $configuredOperations = $config->get('operation');
    if (isset($configuredOperations[$operation]) &&
      $configuredOperations[$operation] === $operation) {
      // @todo verify if use state is needed to check if the manual or cron queues are running
      // Create a queue for every worker.
      $queue = $this->queueFactory->get(CiviCrmUserQueueItem::getQueuesByType($queue_type)[$operation]);
      foreach ($contacts as $contact) {
        // @todo contact is a contactMatch for the 'block' operation, which is ambiguous
        $item = new CiviCrmUserQueueItem($operation, $contact);
        $queue->createItem($item);
        $result++;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function addItems($queue_type) : int {
    $result = 0;

    $existingMatches = $this->matcher->getExistingMatches();
    $candidateMatches = $this->matcher->getCandidateMatches();

    // Create users that are not in the existing matches.
    $usersToCreate = array_diff_key($candidateMatches, $existingMatches);
    $result += $this->addItemsForOperation($queue_type, $usersToCreate, CiviCrmUserQueueItem::OPERATION_CREATE);

    // Block existing matches that are not candidates
    // for a user account anymore.
    $usersToBlock = array_diff_key($existingMatches, $candidateMatches);
    $result += $this->addItemsForOperation($queue_type, $usersToBlock, CiviCrmUserQueueItem::OPERATION_BLOCK);

    // Update and unblock all other existing matches.
    $usersToUpdate = array_diff_key($candidateMatches, $usersToBlock);
    $result += $this->addItemsForOperation($queue_type, $usersToUpdate, CiviCrmUserQueueItem::OPERATION_UPDATE);

    return $result;
  }

}
