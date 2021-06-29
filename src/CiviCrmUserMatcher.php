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

  const CONTACT_FIELDS = ['id', 'contact_id', 'contact_is_deleted'];

  /**
   * Drupal\civicrm_tools\CiviCrmApiInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmApiInterface
   */
  protected $civicrmToolsApiV3;

  /**
   * Drupal\civicrm_tools\CiviCrmApiInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmApiInterface
   */
  protected $civicrmToolsApiV4;

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
   *
   * @param \Drupal\civicrm_tools\CiviCrmApiInterface $civicrm_tools_api_v3
   * @param \Drupal\civicrm_tools\CiviCrmApiInterface $civicrm_tools_api_v4
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(CiviCrmApiInterface $civicrm_tools_api_v3, CiviCrmApiInterface $civicrm_tools_api_v4, EntityTypeManagerInterface $entity_type_manager) {
    $this->civicrmToolsApiV3 = $civicrm_tools_api_v3;
    $this->civicrmToolsApiV4 = $civicrm_tools_api_v4;
    $this->entityTypeManager = $entity_type_manager;
    $this->matchFilter = new CiviCrmMatchFilter();
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingMatches(): array {
    /** @var \Civi\Api4\Generic\Result $result */
    // We use apiv4 here because otherwise with v3 we can't
    // 1 : join from contact table to ufmatch table
    // 2 : Get the contact's mail when joining from ufmatch table
    $result = $this->civicrmToolsApiV4->getDAO('Contact')::get()
      ->setJoin([
        ['UFMatch AS uf_match', TRUE, NULL, ['uf_match.domain_id', '=', $this->matchFilter->getDomainId()]],
      ])
      ->setCheckPermissions(false)
      ->setSelect(CiviCrmUserMatcher::CONTACT_FIELDS)
      ->execute();

    $result->indexBy('id');
    return $result->getArrayCopy();
  }


  /**
   * {@inheritdoc}
   */
  public function oldgetExistingMatches(): array {
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
    $params = [];
    $ufmatch = $this->matchFilter->getDomainId();
    if (!empty($ufmatch)) {
      $params[] = ['UFMatch AS uf_match', TRUE, NULL, ['uf_match.domain_id', '=', $ufmatch]];
    }
    $groups = $this->matchFilter->getGroups();
    if (!empty($groups)) {
      $params[] = ['Group AS group', TRUE, NULL, ['group.id', 'IN', $groups]];
    }
    $tags = $this->matchFilter->getTags();
    if (!empty($tags)) {
      $params[] = ['EntityTag AS entity_tag', TRUE, NULL, ['entity_tag.tag_id', 'IN', $tags]];
    }
    // @Todo : We can't use the apiv4 here yet because joining on entity_tag table is not yet supported
    // @todo due to the potentially high amount of items, use a queue worker
    // and iterate on CiviCRM api pages based on $candidateMatchesCount.
    $result = $this->civicrmToolsApiV4->getDAO('Contact')::get()
      ->setJoin(
        $params
      )
      ->setCheckPermissions(false)
      ->setSelect(CiviCrmUserMatcher::CONTACT_FIELDS)
      ->execute();
    $result->indexBy('id');
    return $result->getArrayCopy();
  }

  /**
   * {@inheritdoc}
   */
  public function oldgetCandidateMatches(): array {
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
