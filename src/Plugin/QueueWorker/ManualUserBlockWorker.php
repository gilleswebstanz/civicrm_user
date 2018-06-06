<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

/**
 * A user blocker that operates from a manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "civicrm_user_manual_block",
 *   title = @Translation("CiviCRM manual user blocker")
 * )
 */
class ManualUserBlockWorker extends UserBlockWorkerBase {}
