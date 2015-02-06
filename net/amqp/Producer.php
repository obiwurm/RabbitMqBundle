<?php

namespace li3_amqp\net\amqp;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends \li3_amqp\core\BaseAmqp {
  protected $_contentType = 'text/plain';
  protected $_deliveryMode = 2;

  /**
   * @todo remove and add to autoconfig?
   */
  public function setContentType($contentType) {
    $this->_contentType = $contentType;

    return $this;
  }

  /**
   * @todo remove and add to autoconfig?
   */
  public function setDeliveryMode($deliveryMode) {
    $this->_deliveryMode = $deliveryMode;

    return $this;
  }

  protected function _getBasicProperties() {
    return array('content_type' => $this->_contentType, 'delivery_mode' => $this->_deliveryMode);
  }

  /**
   * Publishes the message and merges additional properties with basic properties
   *
   * @param string $msgBody
   * @param string $routingKey
   * @param array $additionalProperties
   */
  public function publish($msgBody, $routingKey = '', $additionalProperties = array()) {
    $msg = new AMQPMessage((string) $msgBody, array_merge($this->_getBasicProperties(), $additionalProperties));
    $this->getChannel()->basic_publish($msg, $this->_exchangeOptions['name'], (string) $routingKey);
  }
}
