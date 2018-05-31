<?php

namespace Drupal\civicrm_user;

use Drupal\civicrm_tools\CiviCrmApiInterface;
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
  public function getMatches(): array {
    // TODO: Implement getMatches() method.
    // make use of match filter while selecting them.
    // transform uf_id to contact id as a facade.
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

  /**
   * {@inheritdoc}
   */
  public function matchUserState(array $contact) {
    // Start by checking if a UF Match exists.
    /** @var CiviCrmContactInterface $civiCrmToolsContact */
    $civiCrmToolsContact = \Drupal::service('civicrm_tools.contact');
    if ($user = $civiCrmToolsContact->getUserFromContactId($contact['contact_id'])) {
      // @todo implement
    }
  }

}
