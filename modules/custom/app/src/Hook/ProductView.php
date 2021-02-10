<?php

namespace Drupal\app\Hook;

/**
 * @file
 * Contains \Drupal\app\Hook\ProductView.
 */

/**
 * Controller ProductView.
 */
class ProductView {

  /**
   * Hook.
   */
  public static function hook(&$build, $entity, $view_mode) {
    $types = [
      'product',
    ];
    if ($type = self::check($entity, $types)) {
      if ($view_mode == 'full') {
        \Drupal::messenger()->addWarning(__CLASS__ . "view-full");
      }
    }
  }

  /**
   * Check bundle.
   */
  public static function check($entity, $types) {
    $result = FALSE;
    $type = $entity->bundle();
    if (in_array($type, $types)) {
      $result = $type;
    }
    return $result;
  }

}