<?php

namespace Drupal\app\Controller;

/**
 * @file
 * Contains \Drupal\app\Controller\AppPage.
 */

use Drupal\app\Utility\HelperTheme;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller AppPage.
 */
class AppPage extends ControllerBase {

  /**
   * Render Page.
   */
  public function page(Request $request) {
    $text = $this->t('App example text');
    return [
      'text' => ['#markup' => "<p>{$text}</p>"],
      'table' => HelperTheme::renderTable(),
    ];
  }

  /**
   * Title.
   */
  public function pageTitle() {
    return $this->t('App');
  }

}
