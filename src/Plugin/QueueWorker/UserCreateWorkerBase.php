<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Provides base functionality for the user create workers.
 */
abstract class UserCreateWorkerBase extends UserWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    if ($item instanceof CiviCrmUserQueueItem) {
      $this->createUser($item->getContact());
      $this->reportWork(get_class(), $item);
    }
  }

  /**
   * Creates a Drupal user based on a contact information and configuration.
   *
   * @param array $contact
   *   CiviCRM contact.
   *
   * @return int
   *   Result of the save operation.
   */
  private function createUser(array $contact) {
    $result = 0;
    // @todo refactor with QueuePreviewController error management.
    // The contact must have an email address.
    if (empty($contact['email'])) {
      \Drupal::messenger()->addWarning(t('No email address for contact id @contact_id.', [
        '@contact_id' => $contact['contact_id'],
      ]));
      return $result;
    }

    // If the contact name is not set, default to the email address.
    $userName = $this->getUsername($contact);
    if (empty($userName)) {
      \Drupal::messenger()->addWarning(t('No username for the contact id @contact_id, default to email address.', [
        '@contact_id' => $contact['contact_id'],
      ]));
      $userName = $contact['email'];
    }

    // The contact match may not be in the match table yet,
    // or the match table could be corrupted,
    // so test if the Drupal user exists by comparing the username and email.
    /** @var \Drupal\civicrm_user\CiviCrmUserMatcherInterface $matcher */
    $matcher = \Drupal::service('civicrm_user.matcher');

    if (!$matcher->userExists($userName, $contact['email'])) {
      $config = \Drupal::configFactory()->get('civicrm_user.settings');
      $roles = [];
      // @todo use user->addRole
      // Flatten role array.
      if (!empty($config->get('role'))) {
        foreach ($config->get('role') as $role) {
          $roles[] = $role;
        }
      }
      // Set default password.
      // Password taken from the config is for testing purpose only.
      $password = NULL;
      if (!empty($config->get('passwd'))) {
        $password = $config->get('passwd');
      }
      else {
        $password = \Drupal::service('password_generator')->generate();
      }

      /** @var \Drupal\user\Entity\User $user */
      try {
        $values = [
          'roles' => $roles,
        ];
        $user = \Drupal::entityTypeManager()->getStorage('user')->create($values);
        // @todo the language should be passed by CiviCRM via the contact
        $languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $user->setUsername($userName);
        $user->setEmail($contact['email']);
        $user->setPassword($password);
        $user->enforceIsNew();
        $user->set('init', 'email');
        $user->set('langcode', $languageId);
        $user->set('preferred_langcode', $languageId);
        $user->set('preferred_admin_langcode', $languageId);
        $user->activate();
      }
      catch (InvalidPluginDefinitionException $exception) {
        \Drupal::messenger()->addError($exception->getMessage());
      }
      try {
        $result = $user->save();
        $this->setContactMatch($user, $contact);
        // @todo refactor with reportWork
        $this->logOperation($user, $contact, CiviCrmUserQueueItem::OPERATION_CREATE);
      }
      catch (EntityStorageException $exception) {
        \Drupal::messenger()->addError($exception->getMessage());
      }
    }
    else {
      // This may be a contact from CiviCRM that shares the same email address.
      // The update queue will take care of updating these ones.
      \Drupal::messenger()->addWarning(t('The Drupal user account exists for username <em>@username</em> and email <em>@email</em>.', [
        '@username' => $userName,
        '@email' => $contact['email'],
      ]));
    }
    return $result;
  }

}
