<?php

namespace Drupal\civicrm_user\Controller;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Core\Controller\ControllerBase;
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
   * Constructs a new QueuePreviewController object.
   */
  public function __construct(CiviCrmUserMatcherInterface $matcher) {
    $this->matcher = $matcher;
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
  private function buildHeader() {
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
  private function buildRow(array $contact) {
    return [
      'contact_id' => $contact['id'],
      // @todo get name format from configuration
      'contact_name' => $contact['sort_name'],
      'contact_email' => $contact['email'],
    ];
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
        '#header' => $this->buildHeader(),
        // @todo map operation machine name to a translatable string.
        '#caption' => ucfirst($operation),
        '#rows' => [],
        '#empty' => $this->t('No contacts to process.'),
      ];
      foreach ($contacts as $contact) {
        if ($row = $this->buildRow($contact)) {
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
    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

}
