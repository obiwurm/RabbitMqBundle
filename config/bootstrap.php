<?php
/**
 * Lithium Library to incorporate messaging via RabbitMQ using the php-amqplib library.
 */

use lithium\data\Model;

/**
 * Configure libraries and paths
 */

 require __DIR__ . '/bootstrap/libraries.php';

/**
 * Hook into model save filter to intercept and publish data
 */

 require __DIR__ . '/bootstrap/filter.php';

?>
