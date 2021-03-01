<?php

namespace Drupal\app\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Google\Cloud\BigQuery\BigQueryClient;

/**
 * App Service \Drupal::service('app.google_bigquery').
 *
 * Сервис для работы с API Google BigQuery.
 * для работы необходима переменная окружения
 * GOOGLE_APPLICATION_CREDENTIALS, хранящая путь к
 * JSON-ключу авторизации в API.
 * https://cloud.google.com/docs/authentication/getting-started.
 */
class GoogleBigQueryService {

  /**
   * Клиент для работы с Google BigQuery.
   *
   * @var bqClient
   */
  private $bqClient;

  /**
   * Идентификатор проекта в Google Cloud.
   *
   * @var googleCloudProjectId
   */
  private $googleCloudProjectId;

  /**
   * Датасет в  Google BigQuery для создания временных служебных таблиц.
   *
   * Предполагается, что в этом датасете не хранятся долговременные данные.
   *
   * @var bigQueryTempDataset
   */
  private $bigQueryTempDataset;

  /**
   * Конструктор класса.
   */
  public function __construct() {
  }

  public function init($googleCloudProjectId, $bigQueryTempDataset) {
    $this->googleCloudProjectId = $googleCloudProjectId;
    $this->bigQueryTempDataset = $bigQueryTempDataset;
    $this->bqClient = new BigQueryClient([
      'projectId' => $googleCloudProjectId,
    ]);
    return $this;
  }

  /**
   * Генератор строки из массива по шаблону.
   *
   * Шаблон - строка, в которой места для подстановки элемента массива
   * обозначены комбинацией '__item__'.
   *
   * @param array $array
   *   Массив элементов, которые будут подставляться в строку вместо __item__.
   * @param string $separator
   *   Подстрока, которая будет дописываться в конец каждой
   *   генерируемой по шаблону строки,
   *   кроме последней - там разделитель будет удалён.
   * @param string $stringTemplate
   *   Шаблон строки, в которой места подстановки элемента массива
   *   обозначены __item__.
   *
   * @return string
   *   возвращает строку, которая состоит из
   *   копий $stringTemplate в которых все вхождения __item__
   *   заменены на элемент массива $array.
   *   Строки разделены подстрокой $separator
   */
  private function generateStringFromArray($array, $separator, $stringTemplate) {
    $result = '';
    $string = explode('__item__', $stringTemplate);
    foreach ($array as $item) {
      for ($j = 0; $j < (count($string) - 1); $j++) {
        $result = $result . $string[$j] . $item;
      }
      $result = $result . $separator;
    }
    $result = substr($result, 0, (strlen($result) - strlen($separator)));
    return $result;
  }

  private function mergeBQTables($targetDataset, $targetTable, $sourceDataset, $sourceTable, $head, $key) {
    // Конструируем запрос в BiqQuery.
    $mergeQuery = "MERGE into " . $targetDataset . "." . $targetTable . " AS T
    USING " . $sourceDataset . "." . $sourceTable . " AS S
    ON T." . $key . " = S." . $key .
    " WHEN MATCHED then UPDATE
    SET " . $this->generateStringFromArray($head, ', ', 'T.__item__=S.__item__') . "
    WHEN NOT MATCHED then
    INSERT (" . implode(', ', $head) . ") VALUES (" .
    $this->generateStringFromArray($head, ', ', 'S.__item__') . ");";
    // Отправляем запрос в BigQuery.
    dsm($mergeQuery);
    $queryJobConfig = $this->bqClient->query($mergeQuery);
    $queryResults = $this->bqClient->runQuery($queryJobConfig);
    dsm($queryResults);
    return $queryResults;

  }

  private function createTableFromCSVFile($datasetName, $tableName, $csvFilename, $schema, $timePartitioning) {
    // Проверяем существование датасета. Если нет - создаём.
    if (!$this->bqClient->dataset($datasetName)->exists()) {
      dsm("создаю датасет $datasetName");
      $this->bqClient->createDataset($datasetName);
    }
    $dataset = $this->bqClient->dataset($datasetName);
    $table = $dataset->table($tableName);
    dsm($csvFilename);
    dsm("создаю таблицу $tableName");
    $handle = fopen($csvFilename, "r");
    $txt = fgets($handle);
    fclose($handle);
    dsm($txt);
    $loadJobConfig = $table->load(
      fopen($csvFilename, "r")
    );
    $loadJobConfig->autodetect(TRUE); // без этого не распознаёт CSV-файл.
    $loadJobConfig->schema($schema);
    $loadJobConfig->writeDisposition('WRITE_TRUNCATE');
    $loadJobConfig->timePartitioning($timePartitioning);
    $job = $table->runJob($loadJobConfig);
    #return $job->state();
  }

  public function mergeTableFromCSVFile($datasetName, $tableName, $csvFilename, $schema, $key, $timePartitioning) {
    // Если таблица уже существует в BigQuery.
    if ($this->bqClient->dataset($datasetName)->table($tableName)->exists()) {
      // Создаём временную таблицу в BigQuery.
      $this->createTableFromCSVFile($this->bigQueryTempDataset, $tableName, $csvFilename, $schema, $timePartitioning);
      dsm("doing temp table: $this->bigQueryTempDataset, $csvFilename");
      // Генерируем список полей таблицы из схемы.
      $head = [];
      foreach ($schema['fields'] as $item) {
        $head[] = $item['name'];
      }
      // Мерджим созданную временную таблицу в основную.
      $res = $this->mergeBQTables($datasetName, $tableName, $this->bigQueryTempDataset, $tableName, $head, $key);
    }
    else {
      // Если таблицы нет - создаём.
      dsm("создаём таблицу $datasetName.$tableName");
      $res = $this->createTableFromCSVFile($datasetName, $tableName, $csvFilename, $schema, $timePartitioning);
    }
    return $res;
  }

}
