<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\Entity\User;

/**
 * Provides base functionality for the user update workers.
 */
abstract class UserUpdateWorkerBase extends UserWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    if ($item instanceof CiviCrmUserQueueItem) {
      $this->updateUser($item->getContact());
      $this->reportWork(get_class(), $item);
    }
  }

  /**
   * Updates the user name and email address from the latest contact data.
   *
   * @param array $contact
   *   CiviCRM contact.
   */
  private function updateUser(array $contact) {
    // @todo refactor with QueuePreviewController error management.
    /** @var \Drupal\civicrm_tools\CiviCrmContactInterface $civiCrmToolsContact */
    $civiCrmToolsContact = \Drupal::service('civicrm_tools.contact');
    if ($user = $civiCrmToolsContact->getUserFromContactId($contact['contact_id'])) {
      if ($user instanceof User) {
        try {
          $hasChanged = FALSE;
          // Should have already been done by CiviCRM during contact update.
          // but in some situations, a Drupal user could have edited his email.
          // @todo provide in configuration the user email reset as it will
          // block further access or send a mail to the user to let him known
          // about credentials changes.
          if ($user->getEmail() !== $contact['email']) {
            \Drupal::messenger()->addWarning(t('User *email* before: @previous_email, after: @current_email.', [
              '@previous_email' => $user->getEmail(),
              '@current_email' => $contact['email'],
            ]));
            $hasChanged = TRUE;
          }
          if ($user->getUsername() !== $this->getUsername($contact)) {
            \Drupal::messenger()->addWarning(t('User *name* before: @previous_name, after: @current_name.', [
              '@previous_name' => $user->getUsername(),
              '@current_name' => $this->getUsername($contact),
            ]));
            $hasChanged = TRUE;
          }

          if ($hasChanged) {
            // Log before applying the change.
            // @todo refactor with reportWork
            $this->logOperation($user, $contact, CiviCrmUserQueueItem::OPERATION_UPDATE);

            $user->setEmail($contact['email']);
            $user->setUsername($this->getUsername($contact));
            // Unblock user as it can have been blocked previously.
            $user->activate();
            $user->save();

            // Then update the contact match table and log operation.
            $this->setContactMatch($user, $contact);
          }
        }
        catch (EntityStorageException $exception) {
          \Drupal::messenger()->addError($exception->getMessage());
        }
      }
    }
  }

}
