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
   * Checks if a Drupal user already exists.
   *
   * This comparison does not handle cases of email or username change.
   * In these situations, only the UF Match can provide the right comparison.
   *
   * @param string $name
   *   User name.
   * @param string $email
   *   User mail.
   *
   * @return bool
   *   A boolean indicating if the user exists.
   */
  public function userExists($name, $email);

  /**
   * Returns a CiviCRM contact from a user id.
   *
   * @return array
   *   The CiviCRM contact that matches the Drupal user.
   */
  public function getContactMatch($user_id): array;

  /**
   * Returns a Drupal user from a contact id.
   *
   * @return \Drupal\user\Entity\User
   *   The Drupal user that matches the CiviCRM contact.
   */
  public function getUserMatch($contact): User;

}
