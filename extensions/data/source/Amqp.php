<?php
/**
 * This is a Lithium source adaptor representing an AMQP connection. It is 
 * essentially a wrapper for the Php AMQP Library Connection class
 */

namespace li3_amqp\extensions\data\source;

use lithium\analysis\Logger;
use PhpAmqpLib\Exception\AMQPRuntimeException;

class Amqp extends \PhpAmqpLib\Connection\AMQPConnection {

  private $_config = array();

  private $_status = 0;

  /**
   * @param array $config The configuration options for setting up an AMQPSConnection
   * @see PhpAmqpLib/Connection/AMQPStreamConnection
   */
  public function __construct(array $config = array()) {
    $defaults = array(
      'host' => 'localhost',
      'port' => 5672,
      'login' => 'guest',
      'password' => 'guest',
      'vhost' => "/",
      'insist' => false,
      'login_method' => "AMQPLAIN",
      'login_response' => null,
      'locale' => "en_US",
      'connection_timeout' => 3,
      'read_write_timeout' => 3,
      'context' => null,
      'keepalive' => false,
      'heartbeat' => 0
    );
    $config = $config + $defaults;
    $this->_config = $config;

    // extract the explicit AMQPSConnection args
    extract($config);

    // Lithium's Connection supplies an empty login and password by default.
    // Match it up to PhpAmqpLib's guest credentials
    $user = empty($login) ? 'guest' : $login;
    $password = empty($password) ? 'guest' : $password;

    try {
      parent::__construct(
        $host,
        $port,
        $user,
        $password,
        $vhost,
        $insist,
        $login_method,
        $login_response,
        $locale,
        $connection_timeout,
        $read_write_timeout,
        $context,
        $keepalive,
        $heartbeat
      );
      $this->status = 1;
    } catch (AMQPRuntimeException $e) {
      Logger::error(sprintf("AmqpConnection: %s", $e->getMessage()));
      $this->status = 0;
    }
  }

  public function connected() {
    return $this->status;
  }
}
