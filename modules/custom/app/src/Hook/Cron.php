<?php

namespace Drupal\app\Hook;

/**
 * Hook Cron.
 */
class Cron {

  /**
   * Hook.
   */
  public static function hook() {
    \Drupal::service('app')->cron();
  }

}
