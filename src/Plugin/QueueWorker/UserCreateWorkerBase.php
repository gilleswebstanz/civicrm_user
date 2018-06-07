<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

use Drupal\civicrm_user\CiviCrmMatchFilter;
use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\Entity\User;

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
        'pass' => 'todo',
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

  /**
   * Get the username format set in the configuration.
   *
   * @param array $contact
   *   CiviCRM contact.
   *
   * @return string
   *   The formatted username.
   */
  private function getUsername(array $contact) {
    $result = '';
    $config = \Drupal::configFactory()->get('civicrm_user.settings');
    switch ($config->get('username')) {
      case 'first_and_last_name':
        // @todo sanitize
        $result = $contact['first_name'] . ' ' . $contact['last_name'];
        break;

      case 'display_name':
        // @todo sanitize
        $result = $contact['display_name'];
        break;

      case 'email':
      default:
        $result = $contact['email'];
        break;
    }
    return $result;
  }

  /**
   * Create or update the contact match.
   *
   * CiviCRM is not always finding the right match
   * and creates a new contact in that case.
   *
   * @param \Drupal\user\Entity\User $user
   *   Drupal user entity.
   * @param array $contact
   *   CiviCRM contact entity.
   */
  private function setContactMatch(User $user, array $contact) {
    // Get the domain id.
    $matchFilter = new CiviCrmMatchFilter();
    $domainId = $matchFilter->getDomainId();

    $matchTable = 'civicrm_uf_match';

    // Get a connection to the CiviCRM database.
    Database::setActiveConnection('civicrm');
    $db = Database::getConnection();

    // Check first if the contact match exists.
    // $selectQuery = $db->select($matchTable, 'match')
    // ->fields('match', ['uf_id']);
    // $selectQuery
    // ->condition('domain_id', $domainId)
    // ->condition('contact_id', $contact['contact_id']);
    // $queryResult = $selectQuery->countQuery()->execute()->fetchField();
    // @todo check possible security issue here
    $query = $db->query("SELECT uf_id FROM {$matchTable} WHERE domain_id = :domain_id AND contact_id = :contact_id", [
      ':domain_id' => $matchFilter->getDomainId(),
      ':contact_id' => $contact['contact_id'],
    ]);
    $queryResult = $query->fetchField();

    \Drupal::messenger()->addWarning('Contact match found for contact id ' . $contact['contact_id'] . '? User id ' . $queryResult);

    // If so, update it.
    if ($queryResult) {
      \Drupal::messenger()->addWarning('Updating');
      $db->update($matchTable)
        ->fields([
          'uf_id' => $user->id(),
          'uf_name' => $user->getUsername(),
        ])
        ->condition('domain_id', $domainId)
        ->condition('contact_id', $contact['contact_id'])
        ->execute();
      // Otherwise insert it.
    }
    else {
      \Drupal::messenger()->addWarning('Creating');
      try {
        $db->insert($matchTable)
          ->fields(
            [
              'domain_id' => $domainId,
              'uf_id' => $user->id(),
              'uf_name' => $user->getUsername(),
              'contact_id' => $contact['contact_id'],
            ]
          )
          ->execute();
      }
      catch (\Exception $exception) {
        // @todo logger
        \Drupal::messenger()->addError($exception->getMessage());
      }
    }
    // Switch back to the default database.
    Database::setActiveConnection();
  }

}
