<?php

namespace li3_amqp\net\amqp;

use li3_amqp\net\amqp\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends \li3_amqp\core\BaseConsumer {
  /**
   * @var int $memoryLimit
   */
  protected $_memoryLimit = null;

  /**
   * Set the memory limit
   *
   * @param int $memoryLimit
   */
  public function setMemoryLimit($memoryLimit)
  {
    $this->memoryLimit = $memoryLimit;
  }

  /**
   * Get the memory limit
   *
   * @return int
   */
  public function getMemoryLimit() {
    return $this->_memoryLimit;
  }

  /**
   * Consume the message
   *
   * @param int $msgAmount
   */
  public function consume($msgAmount) {
    $this->_target = $msgAmount;

    $this->_setupConsumer();

    while (count($this->getChannel()->callbacks)) {
      $this->_maybeStopConsumer();
      $this->getChannel()->wait(null, false, $this->getIdleTimeout());
    }
  }

  /**
   * Purge the queue
   */
  public function purge() {
    $this->getChannel()->queue_purge($this->_queueOptions['name'], true);
  }

  public function processMessage(AMQPMessage $msg) {
    $processFlag = call_user_func($this->_callback, $msg);

    $this->_handleProcessMessage($msg, $processFlag);
  }

  protected function _handleProcessMessage(AMQPMessage $msg, $processFlag) {
    if ($processFlag === ConsumerInterface::MSG_REJECT_REQUEUE || false === $processFlag) {
      // Reject and requeue message to RabbitMQ
      $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
    } else if ($processFlag === ConsumerInterface::MSG_SINGLE_NACK_REQUEUE) {
      // NACK and requeue message to RabbitMQ
      $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
    } else if ($processFlag === ConsumerInterface::MSG_REJECT) {
      // Reject and drop
      $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
    } else {
      // Remove message from queue only if callback return not false
      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    $this->_consumed++;
    $this->_maybeStopConsumer();

    if (!is_null($this->getMemoryLimit()) && $this->_isRamAlmostOverloaded()) {
      $this->stopConsuming();
    }
  }

  /**
   * Checks if memory in use is greater or equal than memory allowed for this process
   *
   * @return boolean
   */
  protected function _isRamAlmostOverloaded() {
    if (memory_get_usage(true) >= ($this->getMemoryLimit() * 1024 * 1024)) {
      return true;
    } else {
      return false;
    }
  }
}
