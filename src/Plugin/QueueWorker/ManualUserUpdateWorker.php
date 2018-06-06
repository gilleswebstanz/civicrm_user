<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

/**
 * A user updater that operates from a manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "civicrm_user_manual_update",
 *   title = @Translation("CiviCRM manual user updater")
 * )
 */
class ManualUserUpdateWorker extends UserUpdateWorkerBase {}
