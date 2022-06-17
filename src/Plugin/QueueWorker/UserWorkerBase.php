<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

use Drupal\civicrm_user\CiviCrmMatchFilter;
use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Core\Database\Database;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the UserWorkers.
 */
abstract class UserWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * UserWorkerBase constructor.
   *
   * @param array $configuration
   *   The configuration of the instance.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service the instance should use.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service the instance should use.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $form = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('logger.factory')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Simple reporter log and display information about the queue.
   *
   * @param string $worker
   *   Worker operation.
   * @param object $item
   *   The $item which was stored in the cron queue.
   */
  protected function reportWork($worker, $item) {
    // @todo set status message state
    if ($item instanceof CiviCrmUserQueueItem) {
      if ($this->state->get('civicrm_user_show_status_message')) {
        $this->messenger()->addMessage(
          $this->t('Queue @worker worker processed item for contact id @id.', [
            '@worker' => $worker,
            '@id' => $item->getContactId(),
          ])
        );
      }
      $this->logger->get('civicrm_user')->info('Queue @worker worker processed item contact id @id.', [
        '@worker' => $worker,
        '@id' => $item->getContactId(),
      ]);
    }
    else {
      // @todo be more verbose.
      $this->logger->get('civicrm_user')->error('Worker @worker cannot process item.', [
        '@worker' => $worker,
      ]);
    }
  }

  /**
   * Get the username format that is set in the configuration.
   *
   * @param array $contact
   *   CiviCRM contact.
   *
   * @return string
   *   The formatted username.
   */
  protected function getUsername(array $contact) {
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
   * Creates or updates the contact match.
   *
   * CiviCRM is not always finding the right match
   * and creates a new contact in that case.
   *
   * @param \Drupal\user\Entity\User $user
   *   Drupal user entity.
   * @param array $contact
   *   CiviCRM contact entity.
   */
  protected function setContactMatch(User $user, array $contact) {
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

    // If so, update it if necessary (uf_id or uf_name has changed).
    if ($queryResult) {
      // @todo use uf_name as well.
      if ($queryResult != $user->id()) {
        \Drupal::messenger()->addWarning(t('*Update* contact match. Contact id @contact_id / user id @user_id.', [
          '@contact_id' => $contact['contact_id'],
          '@user_id' => $queryResult,
        ]));
        $db->update($matchTable)
          ->fields([
            'uf_id' => $user->id(),
            'uf_name' => $user->getDisplayName(),
          ])
          ->condition('domain_id', $domainId)
          ->condition('contact_id', $contact['contact_id'])
          ->execute();
      }
      // Otherwise insert it.
    }
    else {
      \Drupal::messenger()->addWarning(t('*Create* contact match. Contact id @contact_id / user id @user_id.', [
        '@contact_id' => $contact['contact_id'],
        '@user_id' => $user->id(),
      ]));
      try {
        $db->insert($matchTable)
          ->fields(
            [
              'domain_id' => $domainId,
              'uf_id' => $user->id(),
              'uf_name' => $user->getDisplayName(),
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

  /**
   * Logs the operation on the user entity.
   *
   * @param \Drupal\user\Entity\User $user
   *   Drupal user.
   *
   * @param array $contact
   *   CiviCRM contact or contact match.
   *
   * @param string $operation
   *   Operation on the user.
   */
  protected function logOperation(User $user, array $contact, $operation) {
    $fields = [
      'uid' => (int) $user->id(),
      'contact_id' => (int) $contact['contact_id'],
      'user_name' => (string) $user->getDisplayName(),
      'user_mail' => (string) $user->getEmail(),
      'operation' => (string) $operation,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    try {
      $insert = \Drupal::database()->insert('civicrm_user_log');
      $insert->fields($fields);
      $insert->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('civicrm_user')->error($e->getMessage());
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

}
