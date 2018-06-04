<?php

namespace Drupal\civicrm_user;

/**
 * Class CiviCrmMatchFilter.
 *
 * Sets typing for the configuration.
 */
class CiviCrmMatchFilter {

  private $domainId;

  private $groups;

  private $tags;

  /**
   * Constructs a new CiviCrmMatchFilter object.
   */
  public function __construct() {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config */
    $config = \Drupal::configFactory()->get('civicrm_user.settings');
    // The domain id is the only mandatory filter.
    $this->domainId = $config->get('domain_id');
    $this->groups = $config->get('group');
    $this->tags = $config->get('tag');
  }

  /**
   * Casts the CiviCRM domain id as an integer.
   *
   * @return int
   *   CiviCRM domain id.
   */
  public function getDomainId() {
    return (int) $this->domainId;
  }

  /**
   * Casts the CiviCRM groups as an array.
   *
   * @return array
   *   CiviCRM groups.
   */
  public function getGroups(): array {
    return (array) $this->groups;
  }

  /**
   * Casts the CiviCRM tags as an array.
   *
   * @return array
   *   CiviCRM tags.
   */
  public function getTags(): array {
    return (array) $this->tags;
  }

}
