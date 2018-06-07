<?php

namespace Drupal\civicrm_user;

use Drupal\civicrm_tools\CiviCrmApiInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;

/**
 * Class CiviCrmUserMatcher.
 *
 * Matching operations between a User and a Contact.
 * Each operation have an implicit CiviCrmMatchFilter that
 * is not exposed publicly. This should be hidden from the outside
 * because it solely depends on the system wide configuration.
 */
class CiviCrmUserMatcher implements CiviCrmUserMatcherInterface {

  /**
   * Drupal\civicrm_tools\CiviCrmApiInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmApiInterface
   */
  protected $civicrmToolsApi;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\civicrm_user\CiviCrmMatchFilter definition.
   *
   * @var \Drupal\civicrm_user\CiviCrmMatchFilter
   */
  private $matchFilter;

  /**
   * Constructs a new CiviCrmUserMatcher object.
   */
  public function __construct(CiviCrmApiInterface $civicrm_tools_api, EntityTypeManagerInterface $entity_type_manager) {
    $this->civicrmToolsApi = $civicrm_tools_api;
    $this->entityTypeManager = $entity_type_manager;
    $this->matchFilter = new CiviCrmMatchFilter();
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingMatches(): array {
    $result = [];
    // @todo throw exception if domain id is not set
    // @todo make use of match filter while selecting matches.
    // @todo it should make sense to always return a civicrm contact,
    // this will allow to simplifiy CiviCrmUserQueueItem constructor
    // and is way more predictable.
    // Get a connection to the CiviCRM database.
    Database::setActiveConnection('civicrm');
    $db = Database::getConnection();
    // @todo check possible security issue here
    $query = $db->query("SELECT id, uf_id, uf_name, contact_id FROM {civicrm_uf_match} WHERE domain_id = :domain_id", [
      ':domain_id' => $this->matchFilter->getDomainId(),
    ]);
    $queryResult = $query->fetchAll();
    // Switch back to the default database.
    Database::setActiveConnection();
    foreach ($queryResult as $row) {
      $result[$row->contact_id] = [
        'uid' => $row->uf_id,
        'name' => $row->uf_name,
        'contact_id' => $row->contact_id,
      ];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidateMatches(): array {
    $result = [];
    $civiCrmMatchFilter = new CiviCrmMatchFilter();
    $params = [];
    $groups = $civiCrmMatchFilter->getGroups();
    if (!empty($groups)) {
      $params['group'] = [
        'IN' => $groups,
      ];
    }
    $tags = $civiCrmMatchFilter->getTags();
    if (!empty($tags)) {
      $params['tag'] = [
        'IN' => $tags,
      ];
    }
    /** @var \Drupal\civicrm_tools\CiviCrmApiInterface $api */
    $api = \Drupal::service('civicrm_tools.api');
    // $candidateMatchesCount = $api->count('Contact', $params);.
    // @todo due to the potentially high amount of items, use a queue worker
    // and iterate on CiviCRM api pages based on $candidateMatchesCount.
    $result = $api->getAll('Contact', $params);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getContactMatch($user_id): array {
    // TODO: Implement getContactFromUser() method.
    // call getMatches to apply filter.
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMatch($contact_id): User {
    // TODO: Implement getUserFromContact() method.
    // call getMatches to apply filter.
  }

}
