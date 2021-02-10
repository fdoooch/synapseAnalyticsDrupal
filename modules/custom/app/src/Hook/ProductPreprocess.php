<?php

namespace Drupal\app\Hook;
/**
 * @file
 * Contains \Drupal\app\Hook\ProductPreprocess.
 */

/**
 * Controller ProductPreprocess.
 */
class ProductPreprocess {

  /**
   * Hook.
   */
  public static function hook(&$variables) {
    $types = [
      'product',
    ];
    if ($node = self::check($variables, $types)) {
      // DO something.
      \Drupal::messenger()->addWarning(__CLASS__ . "Preprocess");
      $variables['hello'] = 'world';
    }
  }
  
  /**
   * Check bundle.
   */
  public static function check($variables, $types) {
    $result = FALSE;
    $type = $variables['product_entity']->bundle();
    if (in_array($type, $types)) {
      $result = $type;
    }
    return $result;
  }

}
