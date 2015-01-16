<?php

namespace li3_amqp\core;

use lithium\util\Inflector;
use lithium\core\ClassNotFoundException;

abstract class BaseConsumer extends \li3_amqp\core\BaseAmqp {

  protected $_target;

  protected $_consumed = 0;

  protected $_callback = array();

  protected $_forceStop = false;

  protected $_idleTimeout = 0;

  protected $_qosOptions = array();

  /**
   * Moving setCallback from OldSoundRabbitMqExtension loader to here
   */
  public function _init() {
    $this->_autoConfig += array(
      'callback' => 'merge',
      'qosOptions' => 'merge'
    );

    parent::_init();
  }

  public function start($msgAmount = 0) {
    $this->_target = $msgAmount;

    $this->_setupConsumer();

    while (count($this->getChannel()->callbacks)) {
      $this->getChannel()->wait();
    }
  }

  public function stopConsuming() {
    $this->getChannel()->basic_cancel($this->getConsumerTag());
  }

  protected function _setupConsumer() {
    if ($this->_autoSetupFabric) {
      $this->setupFabric();
    }

    if (!empty($this->_qosOptions)) {
      extract($this->_qosOptions);
      $this->setQosOptions($prefetchSize, $prefetchCount, $global);
    }

    $this->getChannel()->basic_consume($this->_queueOptions['name'], $this->getConsumerTag(), false, false, false, false, array($this, 'processMessage'));
  }

  protected function _maybeStopConsumer() {
    if (extension_loaded('pcntl') && (defined('AMQP_WITHOUT_SIGNALS') ? !AMQP_WITHOUT_SIGNALS : true)) {
      if (!function_exists('pcntl_signal_dispatch')) {
        throw new \BadFunctionCallException("Function 'pcntl_signal_dispatch' is referenced in the php.ini 'disable_functions' and can't be called.");
      }

      pcntl_signal_dispatch();
    }

    if ($this->_forceStop || ($this->_consumed == $this->_target && $this->_target > 0)) {
      $this->stopConsuming();
    } else {
      return;
    }
  }

  /**
   * Sets the qos settings for the current channel
   * Consider that prefetchSize and global do not work with rabbitMQ version <= 8.0
   *
   * @param int  $prefetchSize
   * @param int  $prefetchCount
   * @param bool $global
   */
  public function setQosOptions($prefetchSize = 0, $prefetchCount = 0, $global = false)
  {
    $this->getChannel()->basic_qos($prefetchSize, $prefetchCount, $global);
  }

  public function setConsumerTag($tag) {
    $this->_consumerTag = $tag;
  }

  public function getConsumerTag() {
    return $this->_consumerTag;
  }

  public function forceStopConsumer() {
    $this->_forceStop = true;
  }

  public function getIdleTimeout() {
    return $this->_idleTimeout;
  }
}
