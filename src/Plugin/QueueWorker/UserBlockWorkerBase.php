<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Provides base functionality for the user block workers.
 */
abstract class UserBlockWorkerBase extends UserWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    if ($item instanceof CiviCrmUserQueueItem) {
      $this->blockUser($item->getContact()['uid']);
      $this->reportWork(get_class(), $item);
    }
  }

  /**
   * When the User does not match the criterion defined by the match filter.
   *
   * @param int $uid
   *   User id.
   */
  private function blockUser($uid) {
    /** @var \Drupal\user\Entity\User $user */
    try {
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
      if ($user->isActive()) {
        $user->block();
        $user->save();
      }
    }
    catch (InvalidPluginDefinitionException $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }
    catch (EntityStorageException $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }
  }

}
