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
    $config = \Drupal::configFactory()->get('civicrm_user.settings');
    $domainId = $config->get('domain_id');
    /** @var \Drupal\civicrm_tools\CiviCrmContactInterface $civiCrmToolsContact */
    $civiCrmToolsContact = \Drupal::service('civicrm_tools.contact');
    if ($user = $civiCrmToolsContact->getUserFromContactId($contact['contact_id'], $domainId)) {
      if ($user instanceof User) {
        try {
          $hasChanged = FALSE;
          // Should have already been done by CiviCRM during contact update.
          // but a Drupal user could have edited his email.
          // @todo provide in configuration the user email reset as it will
          // block further access or send a mail to the user to let him known
          // about credentials changes.
          if ($user->getEmail() !== $contact['email']) {
            \Drupal::messenger()->addWarning(t('User @user_id / contact @contact_id *email* before: @previous_email, after: @current_email.', [
              '@user_id' => $user->id(),
              '@contact_id' => $contact['contact_id'],
              '@previous_email' => $user->getEmail(),
              '@current_email' => $contact['email'],
            ]));
            $hasChanged = TRUE;
          }
          if ($user->getDisplayName() !== $this->getUsername($contact)) {
            \Drupal::messenger()->addWarning(t('User @user_id / contact @contact_id *name* before: @previous_name, after: @current_name.', [
              '@user_id' => $user->id(),
              '@contact_id' => $contact['contact_id'],
              '@previous_name' => $user->getDisplayName(),
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
            // @todo check here from the configuration if the user has been activated
            // if an email needs to be sent (and it should, apart from manual notification).
            $user->activate();
            if ($user->save()) {
              // Then update the contact match table and log operation.
              $this->setContactMatch($user, $contact);
            }
            else {
              \Drupal::messenger()->addError(t('User already exists. Contact id: @contact_id. User id: @user_id. *name* before: @previous_name, after: @current_name. *email* before: @previous_email, after: @current_email.', [
                '@contact_id' => $contact['contact_id'],
                '@user_id' => $user->id(),
                '@previous_name' => $user->getDisplayName(),
                '@current_name' => $this->getUsername($contact),
                '@previous_email' => $user->getEmail(),
                '@current_email' => $contact['email'],
              ]));
            }
          }
        }
        catch (EntityStorageException $exception) {
          \Drupal::messenger()->addError($exception->getMessage());
        }
      }
    }
    else {
      \Drupal::messenger()->addError(t('User match not found for contact id @contact_id', [
        '@contact_id' => $contact['contact_id'],
      ]));
    }
  }

}
