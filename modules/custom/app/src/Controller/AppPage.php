<?php

namespace Drupal\app\Controller;

/**
 * @file
 * Contains \Drupal\app\Controller\AppPage.
 */

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\app\Service;
use Dotenv\Dotenv;

/**
 * Controller AppPage.
 */
class AppPage extends ControllerBase {

  /**
   * Render Page.
   */
  public function page(Request $request) {
    // Указываем авторизационные данные.
    $clientId = '9876543210fedcbaabcdef0123456789';
    $token = '01234567-89ab-cdef-fedc-ba9876543210';

    // Создаем экземпляр клиента с базовыми методами.
    // $baseClient = new BaseClient($clientId, $token);.

    $data = \Drupal::service('app.yandex_bq')->getYandexMetrikaLogsRequestsList();
    $processedRequestsList = [];
    $text = '<h2>Список отчётов:</h2>';
    foreach ($data as $item) {
      $text .= 'Запрос ' .
      $item['request_id'] . ': ' . $item['status'];
      if (isset($item['parts'])) {
        $text .= ', частей: ' . count($item['parts']) . '<br />';
      }
      else {
        $text .= '<br />';
      }
      if ($item['status'] == 'processed') {
        $processedRequestsList[] = $item['request_id'];
      }
    }
    dsm($this);
    dsm([
      'hello' => 'world',
      'hello-helo' => 'world',
    ]);
    return [
      'text' => ['#markup' => "текст <br />$text"],
      'form' => \Drupal::formBuilder()->getForm('Drupal\app\Form\FormExample', $processedRequestsList),
      // 'table' => HelperTheme::renderTable(),
    ];
  }

  /**
   * Title.
   */
  public function pageTitle() {
    return $this->t('App');
  }

}
