<?php

namespace Drupal\app\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\synhelper\Controller\AjaxResult;

/**
 * Class EntityButton.
 */
class FormExample extends FormBase {
  private $wrapper = 'app-form-wrap';
  private $requestId = 0;

  /**
   * AJAX ajaxPrev.
   */
  public function ajaxSubmit(array &$form, $form_state) {
    $otvet = "ajaxSubmit:\n";
    $otvet .= "Hello from " . __CLASS__ . "\n";
    $otvet .= \Drupal::service('app.analytics')->getRandomMessage();
    \Drupal::service('app.yandex_bq')->requestYandexMetrikaYesterdayVisitsLogs();
    $extra = $form_state->extra;
    return AjaxResult::ajax($this->wrapper, $otvet);
  }

  public function cleanSubmit(array &$form, $form_state) {
    \Drupal::service('app.yandex_bq')->cleanYandexMetrikaLogsRequests();
  }

  public function downloadSubmit(array &$form, $form_state) {
    $otvet = 'downloaded!';
    $requestId = $form_state->extra[$form_state->getValue("request_id_select")];
    $otvet .= $requestId;
    $otvet .= \Drupal::service('app.yandex_bq')->putYandexMetrikaLogsToGoogleBigQuery($requestId);
    return AjaxResult::ajax($this->wrapper, $otvet);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {
    $form_state->extra = $extra;
    $form_state->setCached(FALSE);
    $form["id"] = [
      '#type' => 'hidden',
    ];
    $form["submit"] = [
      '#type' => 'button',
      '#value' => $this->t('Submit'),
    ];
    $form["mysubmit"] = [
      '#type' => 'button',
      '#value' => $this->t('Заказать отчёт'),
      '#attributes' => ['class' => ['inline']],
      '#ajax'   => [
        'callback' => '::ajaxSubmit',
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
      '#suffix' => '<div id="' . $this->wrapper . '"></div>',
    ];
    $form["request_id_select"] = [
      '#type' => 'select',
      '#title' => t('Список отчётов для скачивания'),
      '#options' => $extra,
    ];
    $form["clean_submit"] = [
      '#type' => 'button',
      '#value' => $this->t('Очистить запросы'),
      '#attributes' => ['class' => ['inline']],
      '#ajax'   => [
        'callback' => '::cleanSubmit',
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
    ];
    $form["download_submit"] = [
      '#type' => 'button',
      '#value' => $this->t('Скачать отчёт'),
      '#attributes' => ['class' => ['inline']],
      '#ajax'   => [
        'callback' => '::downloadSubmit',
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
      '#suffix' => '<div id="' . $this->wrapper . '"></div>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'app-form';
  }

}
