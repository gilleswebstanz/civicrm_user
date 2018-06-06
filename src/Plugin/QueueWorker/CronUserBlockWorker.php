<?php

namespace Drupal\civicrm_user\Plugin\QueueWorker;

/**
 * A user blocker that operates on cron run.
 *
 * @QueueWorker(
 *   id = "civicrm_user_cron_block",
 *   title = @Translation("CiviCRM cron user blocker"),
 *   cron = {"time" = 5}
 * )
 */
class CronUserBlockWorker extends UserBlockWorkerBase {}
