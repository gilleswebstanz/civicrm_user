<?php

namespace Drupal\civicrm_user;

use Drupal\civicrm_tools\CiviCrmApiInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class CiviCrmUserMatcher.
 *
 * Makes the match between a User and a Contact.
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
   * Constructs a new CiviCrmUserMatcher object.
   */
  public function __construct(CiviCrmApiInterface $civicrm_tools_api, EntityTypeManagerInterface $entity_type_manager) {
    $this->civicrmToolsApi = $civicrm_tools_api;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function matchUserState(array $contact) {
    // @todo implement
  }

}
