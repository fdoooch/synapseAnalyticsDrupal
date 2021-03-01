<?php

namespace Drupal\app\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure App settings for this site.
 */
class AppSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'app_app_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['app.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['yandexAccessToken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('YandexAccessToken'),
      '#default_value' => $this->config('app.settings')->get('yandexAccessToken'),
    ];
    // Путь к ключу аунтификации в Google Cloud.
    $form['googleApplicationCredentialsPath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('googleApplicationCredentialsPath'),
      '#default_value' => $this->config('app.settings')->get('googleApplicationCredentialsPath'),
    ];
    $form['example'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example'),
      '#default_value' => $this->config('app.settings')->get('example'),
    ];
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => $this->config('app.settings')->get('key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('example') != 'example') {
      $form_state->setErrorByName('example', $this->t('The value is not correct.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('app.settings')
      ->set('example', $form_state->getValue('example'))
      ->set('key', $form_state->getValue('key'))
      ->set('yandexAccessToken', $form_state->getValue('yandexAccessToken'))
      ->set('googleApplicationCredentialsPath', $form_state->getValue('googleApplicationCredentialsPath'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
