<?php

use lithium\action\Dispatcher;
use li3_amqp\net\Amqp;

/*
 * Applying filters mid bootstrap seems to precede connections being setup so 
 * an exception 'No adapter set for configuration in class 
 * `lithium\data\Connections`.' is thrown. To circumvent, apply the filters 
 * after bootstrap during Dispatcher::run()
 */
Dispatcher::applyFilter('run', function($self, $params, $chain) {
  Amqp::applyFilters();
  return $chain->next($self, $params, $chain);
});
