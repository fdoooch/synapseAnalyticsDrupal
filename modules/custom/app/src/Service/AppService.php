<?php

namespace Drupal\app\Service;

/**
 * App Service \Drupal::service('app').
 */
class AppService {

  /**
   * HelloW function.
   */
  public function helloFromService() {
    return 'Hello World!';
  }

  /**
   * Runs from HookCron see Drupal\app\Hook\Cron.
   */
  public function cron() {
    $message = "Hello world";
    \Drupal::messenger()->addWarning($message);
    \Drupal::logger(__CLASS__)->notice($message);
    return 'Hello World!';
  }

}
