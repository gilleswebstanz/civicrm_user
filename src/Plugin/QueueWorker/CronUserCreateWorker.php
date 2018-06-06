<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

/**
 * A user creator that operates on cron run.
 *
 * @QueueWorker(
 *   id = "civicrm_user_cron_create",
 *   title = @Translation("CiviCRM cron user creator"),
 *   cron = {"time" = 5}
 * )
 */
class CronUserCreateWorker extends UserCreateWorkerBase {}
