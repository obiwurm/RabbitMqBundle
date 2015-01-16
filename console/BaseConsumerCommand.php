<?php

namespace li3_amqp\console;

use lithium\core\Libraries;
use lithium\core\ClassNotFoundException;
use UnexpectedValueException;
use li3_amqp\core\BaseConsumer as Consumer;
use li3_amqp\net\Amqp;

abstract class BaseConsumerCommand extends \li3_amqp\console\BaseRabbitMqCommand {

  protected $_consumer;

  protected $_amount;

  public function stopConsumer() {
    if ($this->_consumer instanceof Consumer) {
      $this->_consumer->forceStopConsumer();
    } else {
      exit();
    }
  }

  public function restartConsumer() {
    // TODO: Implement restarting of consumer
  }

  /**
   * Executes the current command.
   *
   * @return integer 0 if everything went fine, or an error code
   *
   * @throws \InvalidArgumentException When the number of messages to consume is less than 0
   * @throws \BadFunctionCallException When the pcntl is not installed and option -s is true
   */
  protected function _execute() {
    if (defined('AMQP_WITHOUT_SIGNALS') === false) {
      define('AMQP_WITHOUT_SIGNALS', $this->_getOption('without-signals'));
    }

    if (!AMQP_WITHOUT_SIGNALS && extension_loaded('pcntl')) {
      if (!function_exists('pcntl_signal')) {
        throw new \BadFunctionCallException("Function 'pcntl_signal' is referenced in the php.ini 'disable_functions' and can't be called.");
      }

      pcntl_signal(SIGTERM, array(&$this, 'stopConsumer'));
      pcntl_signal(SIGINT, array(&$this, 'stopConsumer'));
      pcntl_signal(SIGHUP, array(&$this, 'restartConsumer'));
    }

    if (defined('AMQP_DEBUG') === false) {
      define('AMQP_DEBUG', (bool) $this->_getOption('debug'));
    }

    $this->amount = $this->_getOption('messages');

    if (0 > $this->amount) {
      throw new \InvalidArgumentException("The `messages` option should be null or greater than 0");
    }

    //$this->consumer = $this->getContainer()
    //  ->get(sprintf($this->getConsumerService(), $input->getArgument('name')));

    $type = $this->_getOption('type');
    $this->consumer = Amqp::$type($this->_getOption('name'));

    if (!is_null($this->_getOption('memory-limit')) && ctype_digit((string)$this->_getOption('memory-limit')) && $this->_getOption('memory-limit') > 0) {
      $this->consumer->setMemoryLimit($this->_getOption('memory-limit'));
    }
    $this->consumer->setRoutingKey($this->_getOption('route'));
    $this->consumer->consume($this->amount);
  }

  protected function _getOption($option) {
    return isset($this->{$option}) ? $this->{$option} : null;
  }
}
