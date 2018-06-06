<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

/**
 * A user creator that operates from a manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "civicrm_user_manual_create",
 *   title = @Translation("CiviCRM manual user creator")
 * )
 */
class ManualUserCreateWorker extends UserCreateWorkerBase {}
