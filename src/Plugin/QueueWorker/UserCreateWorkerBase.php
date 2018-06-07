<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Database\Database;
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
    // The contact match may not be in the match table yet,
    // so test if the Drupal user exists first.
    if (!$this->userExists($this->getUsername($contact), $contact['email'])) {
      $config = \Drupal::configFactory()->get('civicrm_user.settings');
      $roles = [];
      // Flatten role array.
      if (!empty($config->get('role'))) {
        foreach ($config->get('role') as $role) {
          $roles[] = $role;
        }
      }
      $values = [
        'name' => $this->getUsername($contact),
        'pass' => '@todo',
        'mail' => $contact['email'],
        'status' => 1,
        'roles' => $roles,
      ];
      /** @var \Drupal\user\Entity\User $user */
      try {
        $user = \Drupal::entityTypeManager()->getStorage('user')->create($values);
      }
      catch (InvalidPluginDefinitionException $exception) {
        \Drupal::messenger()->addError($exception->getMessage());
      }
      try {
        $result = $user->save();
      }
      catch (EntityStorageException $exception) {
        \Drupal::messenger()->addError($exception->getMessage());
      }

      $this->setContactMatch($user, $contact);
    }
    else {
      // This may be a contact from CiviCRM that shares the same email address.
      // The update queue will take care of updating these ones.
      \Drupal::messenger()->addWarning(t('Tried to create an existing user: @username', [
        '@username' => $this->getUsername($contact),
      ]));
    }
    return $result;
  }

  /**
   * Checks if a Drupal user already exists.
   *
   * This comparison does not handle cases of email or username change.
   * In these situations, only the UF Match can provide the right comparison.
   *
   * @param string $name
   *   User name.
   * @param string $email
   *   User mail.
   *
   * @return bool
   *   A boolean indicating if the user exists.
   */
  private function userExists($name, $email) {
    $query = \Drupal::database()->select('users_field_data', 'ufd')
      ->fields('ufd', ['uid']);
    $query->condition('uid', '0', '<>');
    $group = $query->orConditionGroup()
      ->condition('name', $name)
      ->condition('mail', $email);
    $query->condition($group);
    $queryResult = $query->countQuery()->execute()->fetchField();
    return (int) $queryResult > 0;
  }

}
