<?php

namespace Drupal\app\Controller;

use Drupal\Component\Serialization\Json;

/**
 * Класс для передачи данных из Yandex.Metrika в Google BigQuery.
 */
class YandexMetrikaToGoogleBigQueryClass {

  const PATH_TO_YANDEX_METRIKA_VISITS_LOGS_TABLE_SCHEMA = 'yandex_metrika_visits_logs_schema.json';

  /**
   * Основная функция.
   */
  public static function updateYandexMetrikaLogs() {
    $json = file_get_contents(self::PATH_TO_YANDEX_METRIKA_VISITS_LOGS_TABLE_SCHEMA, TRUE);
    $schema = json_decode($json, TRUE);
    $yaRobot = new YandexMetrikaClass();
    $gbqRobot = new GoogleBigQueryClass();
    // Генерируем список запрашиваемых параметров визитов из схемы.
    $fields = '';
    foreach ($schema["fields"] as $item) {
      $fields = $fields . "ym:s:" . $item['name'] . ",";
    }
    $fields = substr($fields, 0, (strlen($fields) - 1));
    // Отправляю запрос на логи визитов за вчерашние сутки
    $date1 = date("Y-m-d", strtotime("-2 day"));
    $date2 = date("Y-m-d", strtotime("-1 day"));
    $requestId = $yaRobot->requestVisitsLogs($date1, $date2, $fields);
    // Проверяю готовность отчёта на сервере Яндекса
    sleep(60);
    while ($yaRobot->getVisitsLogsRequestInfo($requestId)['status'] != 'processed') {
      sleep(60);
    }
    // Выясняем количество частей подготовленного отчёта.
    $parts = count($yaRobot->getVisitsLogsRequestInfo($requestId)['parts']);
    dsm($parts);


    $text = $gbqRobot->helloW();
    $countersList = $yaRobot->getCountersList();
    foreach ($countersList as $counter) {
      $text = $text . '<br/>' . $counter['id'] . ': ' . $counter['site'];
    }
    #$yaRobot->requestVisitsLogs('2021-02-19', '2021-02-20');
    $logsCSV = $yaRobot->helloW();
    $text = $gbqRobot->helloW2($logsCSV, $schema);
    dsm($schema);
    return $text;
  }

}
