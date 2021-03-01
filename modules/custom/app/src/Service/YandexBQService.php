<?php

namespace Drupal\app\Service;

/**
 * Сервис для работы передачи данных из Я.Метрики и Я.Директа в Google BigQuery.
 *
 * App Service \Drupal::service('app.analytics').
 */
class YandexBQService {


  private const PATH_TO_YANDEX_METRIKA_VISITS_LOGS_TABLE_SCHEMA = 'yandex_metrika_visits_logs_schema.json';

  /**
   * API-ключ для запросов в Яндекс.
   *
   * @var string
   */
  private $yandexToken;

  /**
   * схему таблицы логов визитов Яндекс.Метрики.
   *
   * @var array
   */
  private $yandexMetrikaVisitsLogsSchema;

  /**
   * Объект для работы с API Google BigQuery.
   *
   * @var Drupal\app\Service\GoogleBigQueryService
   */
  private $bqRobot;

  /**
   * Объект для работы с API Яндекс Метрика.
   *
   * @var Drupal\app\Service\YandexMetrikaService
   */
  private $yaMRobot;


  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // Получаем токен Яндекса.
    $config = \Drupal::config('app.settings');
    $this->yandexToken = $config->get('yandexAccessToken');
    // Загружаем схему таблицы логов визитов Яндекс.Метрики.
    $json = file_get_contents(self::PATH_TO_YANDEX_METRIKA_VISITS_LOGS_TABLE_SCHEMA, TRUE);
    $this->yandexMetrikaVisitsLogsSchema = json_decode($json, TRUE);
    // Устанавливаем переменную окружения Google Creds JSON-файлу.
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $config->get('googleApplicationCredentialsPath'));
    $this->yandexCounterId = '1367525';
    $this->yandexClientLogin = 'fdholdem';
    $this->yandexVisitsLogsBQDatasetName = 'ds_yandex_metrika_' . $this->yandexCounterId;
    $this->yandexVisitsLogsBQTableName = 'tb_ym_visits_' . $this->yandexCounterId;
    $this->googleCloudProjectId = 'synapse-analytics';
    $this->bigQueryTempDataset = 'ds_temp';
    // Создаём объекты для работы с API Яндекса и Гугла
    $this->yaMRobot = \Drupal::service('app.yandex_metrika')->init($this->yandexToken, $this->yandexClientLogin, $this->yandexCounterId);
    $this->bqRobot = \Drupal::service('app.google_bigquery')->init($this->googleCloudProjectId, $this->bigQueryTempDataset);
  }

  /**
   * Здесь мы просто задаем все возможные варианты сообщений.
   */
  private function setMessages() {
    $this->messages = [
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
      'Phasellus maximus tincidunt dolor et ultrices.',
      'Maecenas vitae nulla sed felis faucibus ultricies. Suspendisse potenti.',
      'In nec orci vitae neque rhoncus rhoncus eu vel erat.',
      'Donec suscipit consequat ex, at ultricies mi venenatis ut.',
      'Fusce nibh erat, aliquam non metus quis, mattis elementum nibh. Nullam volutpat ante non tortor laoreet blandit.',
      'Suspendisse et nunc id ligula interdum malesuada.',
    ];
  }

  /**
   * Метод, который возвращает случайное сообщение.
   */
  public function getRandomMessage() {
    return $this->yandexToken;
  }

  /**
   * Получаем список запросов на логи Яндекс.Метрики.
   *
   * @return array
   *   Возвращает список запросов с их статусами и параметрами.
   *   ['request_id'] - id запроса, по которому можно будет скачать логи
   *   ['counter_id'] - номер счётчика с которого взяты логи
   *   ['source'] - вид логов - visits или hits
   *   ['date1'] - дата начала логов
   *   ['date2'] - дата окончания логов
   *   ['fields'] - массив, список запрошенных полей в логах
   *   ['status'] - текущий статус запроса
   *   ['parts'] - массив частей запроса (большие логи бьются на части)
   *   ['size'] - размер запрашиваемых логов
   *   ['attribution'] - тип аттрибуции в логах
   */
  public function getYandexMetrikaLogsRequestsList() {
    return $this->yaMRobot->getLogsRequestsList();
  }

  /**
   * Запрос полных логов визитов в Яндекс Метрике за вчерашний день.
   *
   * @return int
   *   Возвращает идентификатор созданного запроса
   */
  public function requestYandexMetrikaYesterdayVisitsLogs() {
    $date1 = date("Y-m-d", strtotime("-2 day"));
    $date2 = date("Y-m-d", strtotime("-1 day"));
    // Генерируем список запрашиваемых параметров визитов из схемы.
    $fields = '';
    foreach ($this->yandexMetrikaVisitsLogsSchema["fields"] as $item) {
      $fields = $fields . "ym:s:" . $item['name'] . ",";
    }
    $fields = substr($fields, 0, (strlen($fields) - 1));
    $requestID = $this->yaMRobot->requestVisitsLogs($date1, $date2, $fields);
    return $requestID;
  }

  /**
   * Удаляет все отчёты с сервера Yandex API.
   */
  public function cleanYandexMetrikaLogsRequests() {
    $requestsList = $this->yaMRobot->getLogsRequestsList();
    foreach ($requestsList as $item) {
      $this->yaMRobot->cleanLogRequest($item['request_id']);
    }
  }

  public function putYandexMetrikaLogsToGoogleBigQuery($requestId) {
    // Выясняем количество частей подготовленного отчёта.
    $parts = $this->yaMRobot->getLogsRequestInfo($requestId)['parts'];
    // Настройки партицирования таблицы в BQ.
    $bqTimePartition = [
      'type' => "DAY",
      'field' => "date",
    ];
    // Отправляем отчёт в Google BigQuery.
    foreach ($parts as $part) {
      // Скачиваем часть отчёта во временную директорию.
      $logsFilename = $this->yaMRobot->downloadLogs($requestId, $part['part_number']);
      // Отправляем скаченный csv в Google BigQuery.
      $res = $this->bqRobot->mergeTableFromCSVFile(
        $this->yandexVisitsLogsBQDatasetName,
        $this->yandexVisitsLogsBQTableName,
        $logsFilename,
        $this->yandexMetrikaVisitsLogsSchema,
        'visitID',
        $bqTimePartition);
    }
    return $res;

  }

  public function updateYandexMetrikaLogs() {
    $fields = $this->yaMRobot->helloFromService();
    return "UPD: " . $fields;
  }

}
