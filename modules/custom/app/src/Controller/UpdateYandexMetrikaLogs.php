<?php

/**
 * @file
 * Скрипт обновления данных Яндекс.Метрики в BigQuery.
 */

 use \Dotenv\Dotenv;

namespace Drupal\app\Controller;

require_once "YandexMetrikaClass.php";
require_once "GoogleBigQueryClass.php";


const PATH_TO_YANDEX_METRIKA_VISITS_LOGS_TABLE_SCHEMA = 'yandex_metrika_visits_logs_schema.json';

$dotenv = new \Dotenv\Dotenv("/var/www/");
$dotenv->load();

$json = file_get_contents(PATH_TO_YANDEX_METRIKA_VISITS_LOGS_TABLE_SCHEMA, TRUE);
$schema = json_decode($json, TRUE);
echo 'Имя пользователя: ' . $_SERVER["USER"];
echo 'GetEnv:' . getenv('GOOGLE_APPLICATION_CREDENTIALS');
#$yaRobot = new YandexMetrikaClass();
#$gbqRobot = new GoogleBigQueryClass();
// Генерируем список запрашиваемых параметров визитов из схемы.
$fields = '';
foreach ($schema["fields"] as $item) {
  $fields = $fields . "ym:s:" . $item['name'] . ",";
}
$fields = substr($fields, 0, (strlen($fields) - 1));
// Отправляю запрос на логи визитов за вчерашние сутки.
$date1 = date("Y-m-d", strtotime("-2 day"));
$date2 = date("Y-m-d", strtotime("-1 day"));
#$requestId = $yaRobot->requestVisitsLogs($date1, $date2, $fields);
// Проверяю готовность отчёта на сервере Яндекса.
sleep(60);
#while ($yaRobot->getVisitsLogsRequestInfo($requestId)['status'] != 'processed') {
  echo 'Проверка статуса запроса №' . $requestId;
  sleep(60);
#}
echo 'Отчёт подготовлен!';
// Выясняем количество частей подготовленного отчёта.
#$parts = count($yaRobot->getVisitsLogsRequestInfo($requestId)['parts']);
#echo $parts;
