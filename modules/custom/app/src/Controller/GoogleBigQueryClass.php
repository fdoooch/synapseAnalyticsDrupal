<?php

namespace Drupal\app\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Core\ExponentialBackoff;
use Google\Cloud\Core\Exception\NotFoundException;

/**
 * Класс для работы с API Google BigQuery.
 */
class GoogleBigQueryClass {


  /**
   * Конфигурация объекта класса.
   *
   * @var config
   */
  private $config;

  /**
   * Клиент для работы с Google BigQuery.
   *
   * @var bqClient
   */
  private $bqClient;

  /**
   * Конструктор класса.
   */
  public function __construct() {
    $config = \Drupal::config('app.settings');
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $config->get('googleApplicationCredentialsPath'));
    $this->config = [
      'googleCloudProjectId' => 'synapse-analytics',
      'bigQueryTempDataset' => 'ds_temp',
      'bigQueryTableName' => 'tb_metrika_visits',
    ];
    $this->bqClient = new BigQueryClient([
      'projectId' => $this->config['googleCloudProjectId'],
    ]);

  }

  private function ifTableExists($datasetName, $tableName) {
    $dataset = $this->bqClient->dataset($datasetName);
    $table = $dataset->table($tableName);
    return $table->exists();
  }

  private function createTableFromCSVFile($datasetName, $tableName, $csvFileName, $schema) {
    $dataset = $this->bqClient->dataset($datasetName);
    $table = $dataset->table($tableName);
    $loadJobConfig = $table->load(
      fopen($csvFileName, "r")
    );
    $loadJobConfig->autodetect(TRUE); // без этого не распознаёт CSV-файл
    $loadJobConfig->schema($schema);
    $loadJobConfig->writeDisposition('WRITE_TRUNCATE');
    $loadJobConfig->timePartitioning([
      'type' => "DAY",
      'field' => "date",
    ]);
    $job = $table->runJob($loadJobConfig);
    dsm($job);
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
   *   Подстрока, которая будет дописываться в конец каждой генерируемой по шаблону строки,
   *   кроме последней - там разделитель будет удалён.
   * @param string $stringTemplate
   *   Шаблон строки, в которой места подстановки элемента массива обозначены __item__.
   *
   * @return string
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
    $mergeQuery = "MERGE into " . $targetDataset . "." . $targetTable . " AS T
    USING " . $sourceDataset . "." . $sourceTable . "AS S
    ON T." . $key . " = S." . $key .
    " WHEN MATCHED then UPDATE
    SET " . $this->generateStringFromArray($head, ', ', 'T.__item__=S.__item__') . "
    WHEN NOT MATCHED then
    INSERT (" . implode(', ', $head) . ") VALUES (" .
    $this->generateStringFromArray($head, ', ', 'S.__item__') . ");";

    return $mergeQuery;

  }

  /**
   * HelloW function.
   */
  public function helloW() {
    $dataset = 'ds_temp';
    $table = 'tb_metrika_visits';
    if ($this->ifTableExists($dataset, $table)) {
      $text = 'table exist!';
    }
    else {
      $text = 'not fnd!';
    }
    return $text . ' # ' . $this->config['googleCloudProjectId'] . '!!!';
  }

  public function helloW2($filename, $schema) {
    $datasetName = 'ds_yandex_metrika';
    $tableName = 'tb_metrika_visits';
    if ($this->ifTableExists($datasetName, $tableName)) {
      // Если таблица уже существует.
      // Создаём временную таблицу в BigQuery.
      $this->createTableFromCSVFile('ds_temp', $tableName, $filename, $schema);
      // Генерируем список полей таблицы из схемы
      $head = [];
      foreach ($schema['fields'] as $item) {
        $head[] = $item['name'];
      }
      // Мержим временную таблицу в основную.
      $txt = $this->mergeBQTables('ds_temp', 'new_table', 'ds_temp', 'tb_metrika_visits', $head, 'visitID');
    }
    else {
      // Если таблицы нет - создаём.
      $this->createTableFromCSVFile($datasetName, $tableName, $filename, $schema);
    }
  }

}
