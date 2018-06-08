<?php

namespace Drupal\civicrm_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $config = \Drupal::configFactory()->get('civicrm_user.settings');
    // @todo make use of read only flag
    $isReadOnly = $config->get('user_readonly');

    // This could be set from the configuration
    // (Account settings > Registration > Administrators only)
    // but we want to make sure that user registration is disabled.
    // Deny access to '/user/register'.
    if ($route = $collection->get('user.register')) {
      $route->setRequirement('_access', 'FALSE');
    }

    // Disallow user create from the backend.
    if ($route = $collection->get('user.admin_create')) {
      // @todo add message about the readonly configuration.
      $route->setRequirement('_access', 'FALSE');
    }
    // Disallow user edit.
    if ($route = $collection->get('entity.user.edit_form')) {
      // @todo add message about the readonly configuration.
      $route->setRequirement('_access', 'FALSE');
    }
    // Disallow user delete.
    // @todo this will not prevent batch deletion.
    // if ($route = $collection->get('entity.user.cancel_form')) {.
    // @todo add message about the readonly configuration.
    // $route->setRequirement('_access', 'FALSE');
    // }.
  }

}
