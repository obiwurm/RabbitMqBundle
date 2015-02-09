<?php

use lithium\action\Dispatcher;
use lithium\core\Libraries;
use li3_amqp\net\Amqp;

/*
 * Applying filters mid bootstrap seems to precede connections being setup so 
 * an exception 'No adapter set for configuration in class 
 * `lithium\data\Connections`.' is thrown. To circumvent, apply the filters 
 * after bootstrap during Dispatcher::run()
 */
$config = Libraries::get('li3_amqp');
Dispatcher::applyFilter('run', function($self, $params, $chain) use ($config) {
  Amqp::request($params['request']);
  if (isset($config['producers']) && is_array($config['producers'])) {
    Amqp::applyFilters($config);
  }
  return $chain->next($self, $params, $chain);
});
