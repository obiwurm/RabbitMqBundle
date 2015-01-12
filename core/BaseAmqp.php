<?php

namespace li3_amqp\core;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;

abstract class BaseAmqp extends \lithium\core\Object {
  protected $_connection;
  protected $_channel;
  protected $_consumerTag;
  protected $_exchangeDeclared = false;
  protected $_queueDeclared = false;
  protected $_routingKey = '';
  protected $_autoSetupFabric = true;
  protected $_basicProperties = array('content_type' => 'text/plain', 'delivery_mode' => 2);

  protected $_exchangeOptions = array(
    'passive' => false,
    'durable' => true,
    'auto_delete' => false,
    'internal' => false,
    'nowait' => false,
    'arguments' => null,
    'ticket' => null,
    'declare' => true,
  );

  protected $_queueOptions = array(
    'name' => '',
    'passive' => false,
    'durable' => true,
    'exclusive' => false,
    'auto_delete' => false,
    'nowait' => false,
    'arguments' => null,
    'ticket' => null
  );

  protected $_autoConfig = array(
    'connection',
    'consumerTag',
    'exchangeOptions' => 'merge',
    'queueOptions' => 'merge'
  );

  public function _init() {
    parent::_init();
    if (!($this->_connection instanceof AMQPLazyConnection)) {
      $this->getChannel();
    }
    $this->_consumerTag = empty($this->_consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $this->_consumerTag;
  }

  public function __destruct() {
    if ($this->_channel) {
      $this->_channel->close();
    }

    if ($this->_connection && $this->_connection->isConnected()) {
      $this->_connection->close();
    }
  }

  public function reconnect() {
    if (!$this->_connection->isConnected()) {
      return;
    }

    $this->_connection->reconnect();
  }

  /**
   * @return AMQPChannel
   */
  public function getChannel() {
    if (empty($this->_channel)) {
      $this->_channel = $this->_connection->channel();
    }

    return $this->_channel;
  }

  /**
   * @param  AMQPChannel $channel
   * @return void
   */
  public function setChannel(AMQPChannel $channel) {
    $this->_channel = $channel;
  }

  /**
   * @throws \InvalidArgumentException
   * @param  array                     $options
   * @return void
   */
  public function setExchangeOptions(array $options = array()) {
    if (!isset($options['name'])) {
      throw new \InvalidArgumentException('You must provide an exchange name');
    }

    if (empty($options['type'])) {
      throw new \InvalidArgumentException('You must provide an exchange type');
    }

    $this->_exchangeOptions = array_merge($this->_exchangeOptions, $options);
  }

  /**
   * @param  array $options
   * @return void
   */
  public function setQueueOptions(array $options = array()) {
    $this->_queueOptions = array_merge($this->_queueOptions, $options);
  }

  /**
   * @param  string $routingKey
   * @return void
   */
  public function setRoutingKey($routingKey) {
    $this->_routingKey = $routingKey;
  }

  protected function _exchangeDeclare() {
    if ($this->_exchangeOptions['declare']) {
      $this->getChannel()->exchange_declare(
        $this->_exchangeOptions['name'],
        $this->_exchangeOptions['type'],
        $this->_exchangeOptions['passive'],
        $this->_exchangeOptions['durable'],
        $this->_exchangeOptions['auto_delete'],
        $this->_exchangeOptions['internal'],
        $this->_exchangeOptions['nowait'],
        $this->_exchangeOptions['arguments'],
        $this->_exchangeOptions['ticket']);

      $this->_exchangeDeclared = true;
    }
  }

  protected function _queueDeclare() {
    if (null !== $this->_queueOptions['name']) {
      list($queueName, ,) = $this->getChannel()->queue_declare($this->_queueOptions['name'], $this->_queueOptions['passive'],
        $this->_queueOptions['durable'], $this->_queueOptions['exclusive'],
        $this->_queueOptions['auto_delete'], $this->_queueOptions['nowait'],
        $this->_queueOptions['arguments'], $this->_queueOptions['ticket']);

      if (isset($this->_queueOptions['routing_keys']) && count($this->_queueOptions['routing_keys']) > 0) {
        foreach ($this->_queueOptions['routing_keys'] as $routingKey) {
          $this->getChannel()->queue_bind($queueName, $this->_exchangeOptions['name'], $routingKey);
        }
      } else {
        $this->getChannel()->queue_bind($queueName, $this->_exchangeOptions['name'], $this->_routingKey);
      }

      $this->_queueDeclared = true;
    }
  }

  public function setupFabric() {
    if (!$this->_exchangeDeclared) {
      $this->_exchangeDeclare();
    }

    if (!$this->_queueDeclared) {
      $this->_queueDeclare();
    }
  }

  /**
   * disables the automatic SetupFabric when using a consumer or producer
   */
  public function disableAutoSetupFabric() {
    $this->_autoSetupFabric = false;
  }
}
