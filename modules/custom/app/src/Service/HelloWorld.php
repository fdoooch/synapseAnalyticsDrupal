<?php

namespace Drupal\app\Service;

/**
 * Class HelloWorld - проба работы с сервисами в Drupal.
 *
 * @package namespace Drupal\app\Controller;
 */
class HelloWorld {


  /**
   * Массив с сообщениями.
   *
   * @var [type]
   */
  private $messages;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // Записываем сообщения в свойство.
    $this->setMessages();
    $config = \Drupal::config('app.settings');
    $this->token = $config->get('yandexAccessToken');
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
    $random = rand(0, count($this->messages) - 1);
    dsm($this->token);
    return $this->messages[$random];
  }

}
