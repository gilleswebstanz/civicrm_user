<?php

namespace Drupal\civicrm_user;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class CiviCrmUserQueue.
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
    // Defaults to an hourly interval.
    // Cron has to be running at least hourly for this to work.
    // @todo configure interval in settings form
    $interval = 3600;
    // We usually don't want to act every time cron runs (which could be every
    // minute) so keep a time for the next run in the site state.
    $next_execution = $this->state->get('civicrm_user_cron.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : 0;
    if ((int) $_SERVER['REQUEST_TIME'] >= $next_execution) {
      // @todo select and add contact item
      \Drupal::logger('civicrm_user')->notice(t('CiviCRM user - prepare contact queue.'));
      $this->state->set('civicrm_user_cron.next_execution', $_SERVER['REQUEST_TIME'] + $interval);
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
    // TODO: Implement processContactItem() method.
  }

}
