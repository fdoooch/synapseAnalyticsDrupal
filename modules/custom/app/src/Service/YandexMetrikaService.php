<?php

namespace Drupal\app\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;

/**
 * App Service \Drupal::service('app.yandex_metrika').
 *
 * Сервис для работы с API Yandex.Metrika.
 * для работы использует токен, получая его
 * из app.settings('yandexAccessToken)
 */
class YandexMetrikaService {

  /**
   * API-ключ для запросов в Яндекс.
   *
   * @var string
   */
  private $token;

  /**
   * Логин пользователя, от имени которого осуществляются запросы.
   *
   * @var string
   */
  private $clientLogin;

  /**
   * Номер счётчика Яндекс.Метрики из которого получаем данные.
   *
   * @var string
   */
  private $counterId;

  /**
   * Конструктор класса.
   */
  public function __construct() {
    $this->APIurl = [
      'management' => 'https://api-metrika.yandex.net/management/v1/',
      'stat' => 'https://api-metrika.yandex.net/stat/v1/',
    ];
    $this->tempDir = "/tmp";
  }

  /**
   * Получаем основные параметры запросов к API.
   *
   * @param string $yandexAPIToken
   *   Токен доступа к Yandex API.
   * @param string $clientLogin
   *   Логин пользователя, от имени которого обращаемся к API.
   * @param string $counterId
   *   ID счётчика метрики, к которому будем делать запросы.
   *
   * @return YandexMetrikaService
   *   Объект класса
   */
  public function init($yandexAPIToken, $clientLogin, $counterId) {
    $this->token = $yandexAPIToken;
    $this->clientLogin = $clientLogin;
    $this->counterId = $counterId;
    return $this;
  }

  /**
   * HelloW function.
   */
  public function helloFromService() {
    return 'Hello World!' . $this->token;
  }

  /**
   * Получить список запросов логов.
   *
   * @return array
   *   Возвращает список запросов на логи с сервера Yandex
   */
  public function getLogsRequestsList() {
    $methodAPI = 'counter/' . $this->counterId . '/logrequests';
    $client = $client = new Client(['base_uri' => $this->APIurl['management']]);
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
        return $body['requests'];
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
    $client = new Client(['base_uri' => $this->APIurl['management']]);
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

  public function getLogsRequestInfo($requestId) {
    $methodAPI = 'counter/' . $this->counterId . '/logrequest/' . $requestId;
    $client = new Client(['base_uri' => $this->APIurl['management']]);
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

  public function cleanLogRequest($requestId) {
    $methodAPI = 'counter/' . $this->counterId . '/logrequest/' . $requestId . '/clean';
    $client = new Client(['base_uri' => $this->APIurl['management']]);
    // Установка HTTP-заголовков запроса.
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Client-Login' => $this->clientLogin,
    ];
    try {
      $response = $client->request('POST', $methodAPI, [
        'headers' => $headers,
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
  public function downloadLogs($requestId, $partNumber) {
    $filename = $this->tempDir . '/ym_logs_' . $requestId . '_' . $partNumber . '.csv';
    $methodAPI = 'counter/' . $this->counterId . '/logrequest/' . $requestId . '/part/' . $partNumber . '/download';
    $client = new Client(['base_uri' => $this->APIurl['management']]);
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
        $body = preg_replace('/ym:s:from/', 'ym_from', $body); // 'from' - неудачное название для поля в BigQuery, поэтому меняем его на 'ym_from'.
        $body = preg_replace('/ym:s:/', '', $body);
        $handle = fopen($filename, "w+");
        $fwrite = fwrite($handle, $body);
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

}
