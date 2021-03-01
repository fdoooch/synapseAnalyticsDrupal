<?php

namespace Drupal\app\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;

/**
 * Класс для работы с API Яндекс.Метрика.
 */
class YandexMetrikaClass {


  /**
   * Конфигурация объекта класса.
   *
   * @var config
   */
  private static $config;

  /**
   * Токен доступа к API Yandex.
   *
   * @var token
   */
  private $token;

  /**
   * Конструктор класса.
   */
  public function __construct() {
    $config = \Drupal::config('app.settings');
    $this->tempDir = "/tmp";
    $this->token = $config->get('yandexAccessToken');
    $this->urlManagementAPI = 'https://api-metrika.yandex.net/management/v1/';
    $this->urlStatAPI = 'https://api-metrika.yandex.net/stat/v1/';
    $this->clientLogin = 'fdholdem';
    $this->counterId = '1367525';

  }

  /**
   * HelloW function.
   *
   * Для пробы разной фигни. Должна возвращать STRING.
   */
  public function helloW() {
    $text = $this->downloadLogs(15133060, 0);
#        $csvArray = str_getcsv($body, $separator = '\t', $escape = '\\', $enclosure = '\\');
#        return $csvArray[1];
    return $text;
  }

  /**
   * Получить список доступных счётчиков Яндекс.Метрики.
   */
  public function getCountersList() {
    $methodAPI = 'counters';
    $client = new Client(['base_uri' => $this->urlManagementAPI]);
    // Установка HTTP-заголовков запроса.
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Client-Login' => $this->clientLogin,
    ];
    try {
      $response = $client->request('GET', $methodAPI, ['headers' => $headers]);
      $body = $response->getBody()->getContents();
      if (in_array(substr($body, 0, 1), ['{', '['])) {
        $body = Json::decode($body, TRUE)['counters'];
      }
      if ($response->getStatusCode() == 200) {
        return $body;
      }
      return [
        'code' => $response->getStatusCode(),
        'header' => $response->getHeaders(),
        'body' => $body,
      ];
    }
    catch (RequestException $e) {
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

  /**
   * пробная функция - возвращает сводный отчёт по источникам трафика.
   *
   * @return array
   */
  public function getSourcesSummary() {
    $methodAPI = 'data';
    $client = new Client(['base_uri' => $this->urlStatAPI]);
    // Установка HTTP-заголовков запроса.
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Client-Login' => $this->clientLogin,
    ];
    $query = [
      'Ids' => $this->counterId,
      'preset' => 'sources_summary',
    ];
    try {
      $response = $client->request('GET', $methodAPI, [
        'headers' => $headers,
        'query' => $query,
        ]);
      $body = $response->getBody()->getContents();
      if (in_array(substr($body, 0, 1), ['{', '['])) {
        $body = Json::decode($body, TRUE);
      }
      if ($response->getStatusCode() == 200) {
        return $body;
      }
      return [
        'code' => $response->getStatusCode(),
        'header' => $response->getHeaders(),
        'body' => $body,
      ];
    }
    catch (RequestException $e) {
      \Drupal::messenger()->addError($e->getMessage());
    }

  }

  public function getVisitsLogsRequestInfo($requestId) {
    $methodAPI = 'counter/' . $this->counterId . '/logrequest/' . $requestId;
    $client = new Client(['base_uri' => $this->urlManagementAPI]);
    // Установка HTTP-заголовков запроса.
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Client-Login' => $this->clientLogin,
    ];
    try {
      $response = $client->request('GET', $methodAPI, [
        'headers' => $headers,
        ]);
      $body = $response->getBody()->getContents();
      if (in_array(substr($body, 0, 1), ['{', '['])) {
        $body = Json::decode($body, TRUE);
      }
      if ($response->getStatusCode() == 200) {
        return $body['log_request'];
      }
      return [
        'code' => $response->getStatusCode(),
        'header' => $response->getHeaders(),
        'body' => $body,
      ];
    }
    catch (RequestException $e) {
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

  /**
   * Скачивание части подготовленных Яндексом логов.
   *
   * @param int $requestId
   *   Идентификатор запроса на сервере Яндекса.
   * @param int $partNumber
   *   Номер запрашиваемой для скачивания части запроса
   *   (большие запросы Яндекс бьёт на части).
   *
   * @return string
   *   Возвращает путь к сохранённому файлу CSV
   */
  private function downloadLogs($requestId, $partNumber) {
    $filename = $this->tempDir . '/ym_logs_' . $requestId . '_' . $partNumber . '.csv';
    $methodAPI = 'counter/' . $this->counterId . '/logrequest/' . $requestId . '/part/' . $partNumber . '/download';
    $client = new Client(['base_uri' => $this->urlManagementAPI]);
    // Установка HTTP-заголовков запроса.
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Client-Login' => $this->clientLogin,
    ];
    try {
      $response = $client->request('GET', $methodAPI, [
        'headers' => $headers,
      ]);
      $body = $response->getBody()->getContents();
      if ($response->getStatusCode() == 200) {
        $body = preg_replace('/ym:s:/', '', $body);
        $handle = fopen($filename, "w+");
        fwrite($handle, $body);
        fclose($handle);
        return $filename;
      }
      return [
        'code' => $response->getStatusCode(),
        'header' => $response->getHeaders(),
        'body' => $body,
      ];
    }
    catch (RequestException $e) {
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

  public function getVisitsLogsFile($requestId) {
    $methodAPI = 'counter/' . $this->counterId . '/logrequest/' . $requestId . '/part/0/download';
    $client = new Client(['base_uri' => $this->urlManagementAPI]);
    // Установка HTTP-заголовков запроса.
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Client-Login' => $this->clientLogin,
    ];
    try {
      $response = $client->request('GET', $methodAPI, [
        'headers' => $headers,
        ]);
      $body = $response->getBody()->getContents();
      if ($response->getStatusCode() == 200) {
        $handle = fopen("tmp\\resource.csv", "w+");
        fwrite($handle, $body);

        fclose($handle);
        $csvArray = str_getcsv($body, $separator = '\t', $escape = '\\', $enclosure = '\\');
        return $csvArray[1];
      }
      return [
        'code' => $response->getStatusCode(),
        'header' => $response->getHeaders(),
        'body' => $body,
      ];
    }

    catch (RequestException $e) {
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

  public function requestVisitsLogs($date1, $date2, $fields) {
    $methodAPI = 'counter/' . $this->counterId . '/logrequests';
    $client = new Client(['base_uri' => $this->urlManagementAPI]);
    // Установка HTTP-заголовков запроса.
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Client-Login' => $this->clientLogin,
    ];
    $query = [
      'date1' => $date1,
      'date2' => $date2,
      'source' => 'visits',
      'fields' => $fields,
    ];
    try {
      $response = $client->request('POST', $methodAPI, [
        'headers' => $headers,
        'query' => $query,
        ]);
      $body = $response->getBody()->getContents();
      if (in_array(substr($body, 0, 1), ['{', '['])) {
        $body = Json::decode($body, TRUE);
      }
      if ($response->getStatusCode() == 200) {
        return $body['log_request']['request_id'];
      }
      return [
        'code' => $response->getStatusCode(),
        'header' => $response->getHeaders(),
        'body' => $body,
      ];
    }
    catch (RequestException $e) {
      \Drupal::messenger()->addError($e->getMessage());
    }

  }

}
