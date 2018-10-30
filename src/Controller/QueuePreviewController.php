<?php

namespace Drupal\civicrm_user\Controller;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\civicrm_user\CiviCrmUserMatcherInterface;

/**
 * Class QueuePreviewController.
 */
class QueuePreviewController extends ControllerBase {

  /**
   * Drupal\civicrm_user\CiviCrmUserMatcherInterface definition.
   *
   * @var \Drupal\civicrm_user\CiviCrmUserMatcherInterface
   */
  protected $matcher;

  /**
   * Array of errors by operation.
   *
   * @var array
   */
  private $errors;

  /**
   * Array of changes by operation.
   *
   * @var array
   */
  private $changes;

  /**
   * Constructs a new QueuePreviewController object.
   */
  public function __construct(CiviCrmUserMatcherInterface $matcher) {
    $this->matcher = $matcher;
    $this->errors = [
      CiviCrmUserQueueItem::OPERATION_CREATE => 0,
      CiviCrmUserQueueItem::OPERATION_UPDATE => 0,
      CiviCrmUserQueueItem::OPERATION_BLOCK => 0,
    ];
    $this->changes = [
      CiviCrmUserQueueItem::OPERATION_CREATE => 0,
      CiviCrmUserQueueItem::OPERATION_UPDATE => 0,
      CiviCrmUserQueueItem::OPERATION_BLOCK => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm_user.matcher')
    );
  }

  /**
   * Builds a table header.
   *
   * @return array
   *   Header.
   */
  private function buildHeader($operation) {
    $header = [
      'contact_id' => [
        'data' => $this->t('Contact id'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'contact_name' => [
        'data' => $this->t('Contact name'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'contact_email' => [
        'data' => $this->t('Contact email'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];
    // Match data are not available on create.
    if ($operation === CiviCrmUserQueueItem::OPERATION_UPDATE
      || $operation === CiviCrmUserQueueItem::OPERATION_UPDATE) {
      $header['drupal_id'] = [
        'data' => $this->t('Drupal id'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
      $header['drupal_name'] = [
        'data' => $this->t('Drupal name'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
      $header['drupal_email'] = [
        'data' => $this->t('Drupal email'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    $header['drupal_status'] = [
      'data' => $this->t('Drupal status'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    return $header;
  }

  /**
   * Builds a table row.
   *
   * @param array $contact
   *   CiviCRM contact details.
   *
   * @return array
   *   Array mapped to header.
   */
  private function buildRow(array $contact, $operation) {
    $config = \Drupal::configFactory()->get('civicrm_user.settings');
    $domainId = $config->get('domain_id');
    $hasUpdate = TRUE;
    // @fixme block operation returns a contact match
    $result = [
      'contact_id' => $this->getContactLink($contact['id']),
      'contact_name' => $contact['sort_name'],
      'contact_email' => $contact['email'],
    ];
    // @todo errors and changes can be simplified.
    // Check if user exists with mail and/or name.
    if ($operation === CiviCrmUserQueueItem::OPERATION_CREATE) {
      if ($this->matcher->userExists($this->getUsername($contact), $contact['email'])) {
        $result['drupal_status'] = $this->t('** Exists **');
        $this->errors[$operation]++;
      }
      elseif (empty($contact['email'])) {
        $result['drupal_status'] = $this->t('** No email **');
        $this->errors[$operation]++;
      }
      else {
        $result['drupal_status'] = $this->t('Ok');
        $this->changes[$operation]++;
      }
    }

    // Match data are not available on create.
    if ($operation === CiviCrmUserQueueItem::OPERATION_UPDATE
      || $operation === CiviCrmUserQueueItem::OPERATION_BLOCK) {
      /** @var \Drupal\civicrm_tools\CiviCrmContactInterface $civiCrmToolsContact */
      $civiCrmToolsContact = \Drupal::service('civicrm_tools.contact');
      if ($user = $civiCrmToolsContact->getUserFromContactId($contact['contact_id'], $domainId)) {
        $result['drupal_id'] = $this->getUserLink($user->id());
        $result['drupal_name'] = $user->getUsername();
        $result['drupal_email'] = $user->getEmail();
        if ($user->getEmail() !== $contact['email']
          || $user->getUsername() !== $this->getUsername($contact)) {
          // @todo review for block
          $result['drupal_status'] = $this->t('Email or username changes.');
          $this->changes[$operation]++;
        }
        else {
          $hasUpdate = FALSE;
          $result['drupal_status'] = $this->t('No update needed.');
        }
      }
    }

    // Filter unchanged updates.
    // @todo find a more elegant way to filter rows that does not need an update.
    if (!$hasUpdate) {
      return NULL;
    }
    return $result;
  }

  /**
   * Builds the preview for a queue operation for table.html.twig.
   *
   * @param array $contacts
   *   List of CiviCRM contact.
   *
   * @return array
   *   Table render array.
   */
  private function buildTable(array $contacts, $operation) {
    $build = [];
    $config = \Drupal::configFactory()->get('civicrm_user.settings');
    $configuredOperations = $config->get('operation');
    if (isset($configuredOperations[$operation]) &&
      $configuredOperations[$operation] === $operation) {
      $build['table'] = [
        '#type' => 'table',
        '#header' => $this->buildHeader($operation),
        // @todo map operation machine name to a translatable string.
        '#caption' => ucfirst($operation),
        '#rows' => [],
        '#empty' => $this->t('No contacts to process.'),
      ];
      foreach ($contacts as $contact) {
        if ($row = $this->buildRow($contact, $operation)) {
          $build['table']['#rows'][] = $row;
        }
      }
    }
    // @todo pagination
    return $build;
  }

  /**
   * Returns a list of items grouped by operations.
   *
   * @return array
   *   Array of tables containing items.
   */
  private function getTableItems() {
    $result = [];
    $existingMatches = $this->matcher->getExistingMatches();
    $candidateMatches = $this->matcher->getCandidateMatches();

    // Create users that are not in the existing matches.
    $usersToCreate = array_diff_key($candidateMatches, $existingMatches);
    $result[] = $this->buildTable($usersToCreate, CiviCrmUserQueueItem::OPERATION_CREATE);

    // Block existing matches that are not candidates
    // for a user account anymore.
    $usersToBlock = array_diff_key($existingMatches, $candidateMatches);
    $result[] = $this->buildTable($usersToBlock, CiviCrmUserQueueItem::OPERATION_BLOCK);

    // Update and unblock all other existing matches.
    $usersToUpdate = array_diff_key($candidateMatches, $usersToBlock);
    $result[] = $this->buildTable($usersToUpdate, CiviCrmUserQueueItem::OPERATION_UPDATE);
    return $result;
  }

  /**
   * Get the username format that is set in the configuration.
   *
   * @todo refactor with UserWorkerBase
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
   * Returns a link to a user profile.
   *
   * @param int $user_id
   *   Drupal user id.
   *
   * @return string
   *   Rendered link.
   */
  private function getUserLink($user_id) {
    $url = Url::fromUserInput('/user/' . $user_id);
    $link = Link::fromTextAndUrl($user_id, $url);
    $link = $link->toRenderable();
    return \Drupal::service('renderer')->renderRoot($link);
  }

  /**
   * Returns a link to a CiviCRM contact profile.
   *
   * @param int $contact_id
   *   CiviCRM contact id.
   *
   * @return string
   *   Rendered link.
   */
  private function getContactLink($contact_id) {
    $url = Url::fromUserInput('/civicrm/contact/view?reset=1&cid=' . $contact_id);
    $link = Link::fromTextAndUrl($contact_id, $url);
    $link = $link->toRenderable();
    return \Drupal::service('renderer')->renderRoot($link);
  }

  /**
   * Preview.
   *
   * @return array
   *   Return render array.
   */
  public function preview() {
    $output = '';
    foreach ($this->getTableItems() as $items) {
      $output .= \Drupal::service('renderer')->renderRoot($items);
    }
    foreach ($this->errors as $operation => $numberErrors) {
      if ($numberErrors > 0) {
        // @todo format plural
        $this->messenger()->addError(t('@number_errors errors detected for @operation operation.',
          [
            '@number_errors' => $numberErrors,
            '@operation' => ucfirst($operation),
          ]
        ));
      }
    }
    foreach ($this->changes as $operation => $numberChanges) {
      if ($numberChanges > 0) {
        // @todo format plural
        $this->messenger()->addWarning(t('@number_changes changes for @operation operation.',
          [
            '@number_changes' => $numberChanges,
            '@operation' => ucfirst($operation),
          ]
        ));
      }
    }
    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

}
