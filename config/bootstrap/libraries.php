<?php

use lithium\core\Libraries;

/**
 * Add a new class path for amqp producers
 */
Libraries::paths(array(
  "producer" => array(
    '{:library}\net\amqp\{:name}'
  //  '{:library}\extensions\net\amqp\producer\{:name}'
  )
));

/**
 * Add a new class path for amqp consumer handlers
 */
Libraries::paths(array(
  "consumer" => array('{:library}\extensions\net\amqp\consumer\{:name}')
));
