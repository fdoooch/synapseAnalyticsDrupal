<?php

namespace Drupal\app\Hook;

/**
 * @file
 * Contains \Drupal\app\Hook\EntityPresave.
 */

/**
 * Controller NodePresave.
 */
class NodePresave {

  /**
   * Hook.
   */
  public static function hook($node) {
    // Set Node-title.
    $types = [
      'syspage',
    ];
    if ($type = self::check($node, $types)) {
      // DO something.
      \Drupal::messenger()->addWarning(__FILE__);
    }
  }

  /**
   * Check node type.
   */
  public static function check($node, $types) {
    $result = FALSE;
    if (method_exists($node, 'getType')) {
      $type = $node->getType();
      if (in_array($type, $types)) {
        $result = $type;
      }
    }
    return $type;
  }

}
