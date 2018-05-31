<?php

namespace Drupal\civicrm_user;

use Drupal\user\Entity\User;

/**
 * Interface CiviCrmUserMatcherInterface.
 */
interface CiviCrmUserMatcherInterface {

  /**
   * Returns a list of matches containing the user id and contact id.
   *
   * @return array
   *   List of matches.
   */
  public function getMatches(): array;

  /**
   * Returns a CiviCRM contact from a user id.
   */
  public function getContactMatch($user_id): array;

  /**
   * Returns a CiviCRM user from a contact id.
   */
  public function getUserMatch($contact): User;

  /**
   * Matches the state of a User based on a CiviCRM Contact.
   *
   * When the User does not match the criterion defined by the filter,
   * its status in Drupal becomes inactive (blocked).
   *
   * @param array $contact
   *   The CiviCRM Contact data.
   */
  public function matchUserState(array $contact);

}
