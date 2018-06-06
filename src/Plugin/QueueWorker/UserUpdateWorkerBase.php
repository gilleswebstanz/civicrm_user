<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Provides base functionality for the user update workers.
 */
abstract class UserUpdateWorkerBase extends UserWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof CiviCrmUserQueueItem) {
      // @todo merge two operations as they both need the user to be saved.
      $this->unblockUser($data->getContact());
      $this->updateUser($data->getContact());
      $this->reportWork(get_class(), $data);
    }
  }

  /**
   * When a user exists and matches again the configured filters.
   *
   * @param array $contact
   *   CiviCRM contact.
   */
  private function unblockUser(array $contact) {
    /** @var \Drupal\user\Entity\User $user */
    try {
      /** @var \Drupal\civicrm_tools\CiviCrmContactInterface $civiCrmToolsContact */
      $civiCrmToolsContact = \Drupal::service('civicrm_tools.contact');
      if ($user = $civiCrmToolsContact->getUserFromContactId($contact['contact_id'])) {
        if ($user->isBlocked()) {
          $user->activate();
          $user->save();
        }
      }
    }
    catch (EntityStorageException $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }
  }

  /**
   * Updates the user name and email address from the latest contact data.
   *
   * @param array $contact
   *   CiviCRM contact.
   */
  private function updateUser(array $contact) {
    $civiCrmToolsContact = \Drupal::service('civicrm_tools.contact');
    if ($user = $civiCrmToolsContact->getUserFromContactId($contact['contact_id'])) {
      // @todo implement
    }
  }

}
