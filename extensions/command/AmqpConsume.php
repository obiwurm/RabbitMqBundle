<?php

namespace li3_amqp\extensions\command;

/**
 * Consume messages from registered AMQP queues with a registered consumer.
 * Use `li3 amqp-consume --name=<consumer>`
 *
 */
class AmqpConsume extends \li3_amqp\console\BaseConsumerCommand {

  /**
   * Consumer Name. Required
   *
   * @var string
   */
  public $name = null;

  /**
   * Messages to consume. Optional
   *
   * @var int
   */
  public $messages = null;

  /**
   * Consumer type.
   * Default 'consumer'
   * Options [consumer, anon, multi]
   *
   * @var string
   */
  public $type = 'consumer';

  /**
   * Routing Key. Optional
   *
   * @var string
   */
  public $route = '';

  /**
   * Allowed memory for this process. Optional
   *
   * @var int
   */
  public $memoryLimit = null;

  /**
   * Enable Debugging. Optional
   *
   * @var bool
   */
  public $d = false;

  /**
   * Disable catching of system signals. Optional
   *
   * @var bool
   */
  public $w = false;

  /*
   * Consume messages from queues
   */
  public function run() {
    if (!isset($this->name)) {
      return $this->_help();
    }
    $this->memoryLimit = isset($this->{'memory-limit'}) ? $this->{'memory-limit'} : null;
    $this->debug = $this->d;
    $this->withoutSignals = $this->w;

    $this->_execute();
  }
}

