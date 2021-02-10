<?php

namespace Drupal\app\Hook;

/**
 * @file
 * Contains \Drupal\app\Hook\NodeView.
 */

use Drupal\app\Utility\HelperTheme;

/**
 * Controller NodeView.
 */
class NodeView {

  /**
   * Hook.
   */
  public static function hook(&$build, $node, $view_mode) {
    $types = [
      'project',
    ];
    if ($type = self::check($node, $types)) {
      if ($view_mode == 'full') {
        $build['app'] = [
          'from' => \Drupal::formBuilder()->getForm('Drupal\app\Form\Set', $node),
          'products' => HelperTheme::renderTable($node),
        ];
      }
      if ($view_mode == 'teaser') {
        $build['app'] = [
          'products' => HelperTheme::renderTable($node, 'small'),
        ];
      }
    }
  }

  /**
   * Check node & type.
   */
  public static function check($node, $types) {
    $result = FALSE;
    if (method_exists($node, 'getType')) {
      $type = $node->getType();
      if (in_array($type, $types)) {
        $result = $type;
      }
    }
    return $result;
  }

}
