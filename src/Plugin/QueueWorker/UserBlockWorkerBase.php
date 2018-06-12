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
      $this->blockUser($item);
      $this->reportWork(get_class(), $item);
    }
  }

  /**
   * When the User does not match the criterion defined by the match filter.
   *
   * @param int $item
   *   Process item.
   */
  private function blockUser($item) {
    /** @var \Drupal\user\Entity\User $user */
    try {
      if($item instanceof CiviCrmUserQueueItem) {
        $uid = $item->getContact()['uid'];
        $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
        if ($user->isActive()) {
          $user->block();
          $user->save();
          // @todo refactor with reportWork
          $this->logOperation($user, $item->getContact(), CiviCrmUserQueueItem::OPERATION_BLOCK);
        }
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
