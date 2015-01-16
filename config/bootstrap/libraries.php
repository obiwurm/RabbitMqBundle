<?php

use lithium\core\Libraries;

Libraries::add('php-amqplib', array(
  "path" => LITHIUM_LIBRARY_PATH . "/li3_amqp/php-amqplib/PhpAmqpLib",
  "includePath" => LITHIUM_LIBRARY_PATH . "/li3_amqp/php-amqplib",
  "loader" => function($class) {
    if (strpos($class, 'PhpAmqpLib') !== false) {
      include str_replace("\\", "/", $class) . ".php";
    }
  }
));


/**
 * Add a new class path for amqp producers
 */
Libraries::paths(array(
  "producers" => array(
    '{:library}\extensions\net\amqp\producer\{:name}',
    '{:library}\net\amqp\producer\{:name}',
    '{:library}\net\amqp\{:name}'
  )
));

/**
 * Add a new class path for amqp consumer handlers
 */
Libraries::paths(array(
  "consumers" => array('{:library}\extensions\net\amqp\consumer\{:name}')
));
