<?php

namespace Drupal\civicrm_user;

/**
 * Interface CiviCrmUserMatcherInterface.
 */
interface CiviCrmUserMatcherInterface {

  /**
   * Matches the state of a User based on a CiviCRM Contact.
   *
   * @param array $contact
   *   The CiviCRM Contact data.
   */
  public function matchUserState(array $contact);

}
