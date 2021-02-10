<?php

namespace Drupal\app\Hook;

/**
 * @file
 * Contains \Drupal\app\Hook\NodePreprocess.
 */

/**
 * Controller NodePreprocess.
 */
class NodePreprocess {

  /**
   * Hook.
   */
  public static function hook(&$variables) {
    $types = [
      'syspage',
    ];
    if ($node = self::check($variables, $types)) {
      // DO something.
      // \Drupal::messenger()->addWarning("NodePreprocess");
    }
  }

  /**
   * Check node & type.
   */
  public static function check($variables, $types) {
    $result = FALSE;
    $node = $variables['node'];
    if (is_object($node) && method_exists($node, 'getType')) {
      $type = $node->getType();
      if (in_array($type, $types)) {
        $result = $node;
      }
    }
    return $result;
  }

}
