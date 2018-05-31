<?php

namespace Drupal\civicrm_user;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class CiviCrmUserProcessor.
 *
 * Processes User operations.
 */
class CiviCrmUserProcessor implements CiviCrmUserProcessorInterface {

  /**
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $queue;

  /**
   * Drupal\Core\CronInterface definition.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\civicrm_user\CiviCrmUserMatcherInterface definition.
   *
   * @var \Drupal\civicrm_user\CiviCrmUserMatcherInterface
   */
  protected $civicrmUserMatcher;

  /**
   * Constructs a new CiviCrmUserQueue object.
   */
  public function __construct(ContainerAwareInterface $queue, CronInterface $cron, StateInterface $state, CiviCrmUserMatcherInterface $civicrm_user_matcher) {
    $this->queue = $queue;
    $this->cron = $cron;
    $this->state = $state;
    $this->civicrmUserMatcher = $civicrm_user_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareContactQueue() {
    // @todo pseudo code to implement
    // array contacts — get a list of all the contact id’s filtered
    //
    // array existingUserMatches — iterate through civicrm_uf_match
    // table and store the pair uid - cid
    //
    // array usersToCreate - make a diff between contacts
    // + existingUserMatches
    //
    // Then
    // - check the matchStatus for all contacts in the existingUserMatches
    // - create user accounts for usersToCreate.
    $civiCrmMatchFilter = new CiviCrmMatchFilter();
    $params = [];
    $groups = $civiCrmMatchFilter->getGroups();
    $params['group'] = [
      'IN' => $groups,
    ];
    $tags = $civiCrmMatchFilter->getTags();
    if (!empty($tags)) {
      $params['tag'] = [
        'IN' => $tags,
      ];
    }

    /** @var \Drupal\civicrm_tools\CiviCrmApiInterface $api */
    $api = \Drupal::service('civicrm_tools.api');
    $count = $api->count('Contact', $params);

    // If there is less than 1 page.
    // @todo case to remove, used for debug only.
    if ($count <= CiviCrmUserProcessorInterface::PAGE_ITEMS) {
      $contacts = $api->getAll('Contact', $params);
      foreach ($contacts as $contact) {
        $this->addContactItem($contact);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processContactQueue() {
    // TODO: Implement processContactQueue() method.
  }

  /**
   * Adds a contact page item to the 'contact page' queue.
   *
   * @param array $page
   *   Page of CiviCRM Contacts.
   */
  private function addContactPageItem(array $page) {
    // TODO: Implement addContactPageItem() method.
  }

  /**
   * Processes a contact page by adding the contact items.
   */
  private function processContactPageItem($contact_id) {
    // TODO: Implement processContactPageItem() method.
  }

  /**
   * Adds a contact item to the 'contact' queue.
   *
   * @param array $contact
   *   CiviCRM contact data.
   */
  private function addContactItem(array $contact) {
    // TODO: Implement addContactItem() method.
  }

  /**
   * Processes each contact with the user matcher.
   *
   * @param array $contact
   *   CiviCRM contact data.
   */
  private function processContactItem(array $contact) {
    // @todo execute in worker.
    // $this->civicrmUserMatcher->matchUserState($contact, $filter);
  }

}
