<?php

namespace li3_amqp\core;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;

abstract class BaseAmqp extends \lithium\core\Object {
  protected $connection;
  protected $channel;
  protected $consumerTag;
  protected $exchangeDeclared = false;
  protected $queueDeclared = false;
  protected $routingKey = '';
  protected $autoSetupFabric = true;
  protected $basicProperties = array('content_type' => 'text/plain', 'delivery_mode' => 2);

  protected $exchangeOptions = array(
    'passive' => false,
    'durable' => true,
    'auto_delete' => false,
    'internal' => false,
    'nowait' => false,
    'arguments' => null,
    'ticket' => null,
    'declare' => true,
  );

  protected $queueOptions = array(
    'name' => '',
    'passive' => false,
    'durable' => true,
    'exclusive' => false,
    'auto_delete' => false,
    'nowait' => false,
    'arguments' => null,
    'ticket' => null
  );

  /**
   * Refactor: Utilising Object Config
   *
   * @param AMQPConnection   $conn
   * @param AMQPChannel|null $ch
   * @param null             $consumerTag
   *
   public function __construct(AMQPConnection $conn, AMQPChannel $ch = null, $consumerTag = null) {
     $this->conn = $conn;
     $this->ch = $ch;

     if (!($conn instanceof AMQPLazyConnection)) {
       $this->getChannel();
}

$this->consumerTag = empty($consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $consumerTag;
}
   */

  public function _init() {
    parent::_init();
    if (!($this->connection instanceof AMQPLazyConnection)) {
      $this->getChannel();
    }
    $this->consumerTag = empty($this->consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $this->consumerTag;
  }

  public function __destruct() {
    if ($this->channel) {
      $this->channel->close();
    }

    if ($this->connection && $this->connection->isConnected()) {
      $this->connection->close();
    }
  }

  public function reconnect() {
    if (!$this->connection->isConnected()) {
      return;
    }

    $this->connection->reconnect();
  }

  /**
   * @return AMQPChannel
   */
  public function getChannel() {
    if (empty($this->channel)) {
      $this->channel = $this->connection->channel();
    }

    return $this->channel;
  }

  /**
   * @param  AMQPChannel $channel
   * @return void
   */
  public function setChannel(AMQPChannel $channel) {
    $this->channel = $channel;
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

    $this->exchangeOptions = array_merge($this->exchangeOptions, $options);
  }

  /**
   * @param  array $options
   * @return void
   */
  public function setQueueOptions(array $options = array()) {
    $this->queueOptions = array_merge($this->queueOptions, $options);
  }

  /**
   * @param  string $routingKey
   * @return void
   */
  public function setRoutingKey($routingKey) {
    $this->routingKey = $routingKey;
  }

  protected function exchangeDeclare() {
    if ($this->exchangeOptions['declare']) {
      $this->getChannel()->exchange_declare(
        $this->exchangeOptions['name'],
        $this->exchangeOptions['type'],
        $this->exchangeOptions['passive'],
        $this->exchangeOptions['durable'],
        $this->exchangeOptions['auto_delete'],
        $this->exchangeOptions['internal'],
        $this->exchangeOptions['nowait'],
        $this->exchangeOptions['arguments'],
        $this->exchangeOptions['ticket']);

      $this->exchangeDeclared = true;
    }
  }

  protected function queueDeclare() {
    if (null !== $this->queueOptions['name']) {
      list($queueName, ,) = $this->getChannel()->queue_declare($this->queueOptions['name'], $this->queueOptions['passive'],
        $this->queueOptions['durable'], $this->queueOptions['exclusive'],
        $this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
        $this->queueOptions['arguments'], $this->queueOptions['ticket']);

      if (isset($this->queueOptions['routing_keys']) && count($this->queueOptions['routing_keys']) > 0) {
        foreach ($this->queueOptions['routing_keys'] as $routingKey) {
          $this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $routingKey);
        }
      } else {
        $this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);
      }

      $this->queueDeclared = true;
    }
  }

  public function setupFabric() {
    if (!$this->exchangeDeclared) {
      $this->exchangeDeclare();
    }

    if (!$this->queueDeclared) {
      $this->queueDeclare();
    }
  }

  /**
   * disables the automatic SetupFabric when using a consumer or producer
   */
  public function disableAutoSetupFabric() {
    $this->autoSetupFabric = false;
  }
}
