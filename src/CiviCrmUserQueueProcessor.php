<?php

namespace Drupal\civicrm_user;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\State\StateInterface;

/**
 * Class CiviCrmUserQueueProcessor.
 *
 * Processes CiviCrmUserQueueItems instances.
 */
class CiviCrmUserQueueProcessor implements CiviCrmUserQueueProcessorInterface {

  /**
   * Drupal\Core\Queue\QueueFactory definition.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Drupal\Core\Queue\QueueWorkerManagerInterface definition.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new CiviCrmUserQueue object.
   */
  public function __construct(QueueFactory $queue_factory, QueueWorkerManagerInterface $queue_manager, StateInterface $state) {
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function processItems($queue_type) : void {
    $queues = CiviCrmUserQueueItem::getQueuesByType($queue_type);
    foreach ($queues as $queueId) {
      $queue = $this->queueFactory->get($queueId);
      try {
        /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
        // The queue id is shared with the worker id.
        $queue_worker = $this->queueManager->createInstance($queueId);
        while ($item = $queue->claimItem()) {
          try {
            $queue_worker->processItem($item->data);
            $queue->deleteItem($item);
          }
          catch (SuspendQueueException $exception) {
            $queue->releaseItem($item);
            break;
          }
          catch (\Exception $exception) {
            watchdog_exception('civicrm_user', $exception);
          }
        }
      }
      catch (PluginException $exception) {
        watchdog_exception('civicrm_user', $exception);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfItemsPerQueue($queue_type): array {
    $result = [];
    $queues = CiviCrmUserQueueItem::getQueuesByType($queue_type);
    foreach ($queues as $operation => $queue) {
      $queue = $this->queueFactory->get($queue);
      $result[$operation] = $queue->numberOfItems();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfItems($queue_type): int {
    $result = 0;
    $numberOfItemsPerQueue = $this->getNumberOfItemsPerQueue($queue_type);
    foreach ($numberOfItemsPerQueue as $queueNumberOfItems) {
      $result += $queueNumberOfItems;
    }
    return $result;
  }

}
