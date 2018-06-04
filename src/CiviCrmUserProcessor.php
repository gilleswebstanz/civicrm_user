<?php

namespace Drupal\civicrm_user;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityStorageException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class CiviCrmUserProcessor.
 *
 * Processes User operations.
 */
class CiviCrmUserProcessor implements CiviCrmUserProcessorInterface {

  /**
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $queue;

  /**
   * Drupal\Core\CronInterface definition.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\civicrm_user\CiviCrmUserMatcherInterface definition.
   *
   * @var \Drupal\civicrm_user\CiviCrmUserMatcherInterface
   */
  protected $matcher;

  /**
   * Constructs a new CiviCrmUserQueue object.
   */
  public function __construct(ContainerAwareInterface $queue, CronInterface $cron, StateInterface $state, CiviCrmUserMatcherInterface $matcher) {
    $this->queue = $queue;
    $this->cron = $cron;
    $this->state = $state;
    $this->matcher = $matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareContactQueue() {
    $existingMatches = $this->matcher->getExistingMatches();
    $candidateMatches = $this->matcher->getCandidateMatches();

    // Create users that are not in the existing matches.
    $usersToCreate = array_diff_key($candidateMatches, $existingMatches);
    // @todo process in a queue
    foreach ($usersToCreate as $contact) {
      //$this->createUser($contact);
    }

    // Block existing matches that are not candidates
    // for a user account anymore.
    $usersToBlock = array_diff_key($existingMatches, $candidateMatches);
    foreach ($usersToBlock as $contactMatch) {
      //$this->blockUser($contactMatch['uid']);
    }

    // Update and unblock all other existing matches.
    $usersToUpdate = array_diff_key($candidateMatches, $usersToBlock);
    foreach ($usersToUpdate as $contact) {
      //$this->unblockUser($contact);
      //$this->updateUser($contact);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processContactQueue() {
    // TODO: Implement processContactQueue() method.
  }

  /**
   * Adds a contact item to the 'contact' queue.
   *
   * @param array $contact
   *   CiviCRM contact data.
   */
  private function addContactItem(array $contact) {
    // TODO: Implement addContactItem() method.
  }

  /**
   * Processes each contact with the user matcher.
   *
   * @param array $contact
   *   CiviCRM contact data.
   */
  private function processContactItem(array $contact) {
    // @todo execute in worker.
    // $this->civicrmUserMatcher->matchUserState($contact, $filter);
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
      if($user->isActive()) {
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

  /**
   * When the User matches again the criterion defined by the match filter.
   *
   * @param array $contact
   *   CiviCRM contact.
   */
  private function unblockUser(array $contact) {
    /** @var \Drupal\user\Entity\User $user */
    try {
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
    if(!$this->userExists($this->getUsername($contact), $contact['email'])) {
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
   */
  public function userExists($name, $email) {
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
    // @todo dependency injection
    $result = '';
    $config = \Drupal::configFactory()->get('civicrm_user.settings');
    switch ($config->get('username')) {
      case 'first_and_last_name':
        // @todo sanitize and check duplicates
        $result = $contact['first_name'] . ' ' . $contact['last_name'];
        break;

      case 'display_name':
        // @todo sanitize and check duplicates
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
