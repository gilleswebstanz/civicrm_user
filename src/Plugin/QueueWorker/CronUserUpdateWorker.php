<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

/**
 * A user updater that operates on cron run.
 *
 * @QueueWorker(
 *   id = "civicrm_user_cron_update",
 *   title = @Translation("CiviCRM cron user updater"),
 *   cron = {"time" = 5}
 * )
 */
class CronUserUpdateWorker extends UserUpdateWorkerBase {}
