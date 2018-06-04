<?php

namespace Drupal\civicrm_user;

use Drupal\user\Entity\User;

/**
 * Interface CiviCrmUserMatcherInterface.
 */
interface CiviCrmUserMatcherInterface {

  /**
   * Returns a list of existing contact - user matches.
   *
   * @return array
   *   List of matches.
   */
  public function getExistingMatches(): array;

  /**
   * Returns a list of contacts filtered by the match conditions.
   *
   * Get contacts filtered by groups, tags, domain_id that are the
   * candidates for a user match.
   *
   * @return array
   *   List of matches.
   */
  public function getCandidateMatches(): array;

  /**
   * Returns a CiviCRM contact from a user id.
   */
  public function getContactMatch($user_id): array;

  /**
   * Returns a CiviCRM user from a contact id.
   */
  public function getUserMatch($contact): User;

}
