<?php
/**
 * Class to access Producers and Consumers
 *
 * Mimics some configuration setup found in the Symfony extension but leaves 
 * most data to li3's autoconfig.
 *
 * @see RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension
 */

namespace li3_amqp\net;

use lithium\core\Libraries;
use lithium\data\Connections;
use lithium\core\ClassNotFoundException;
use lithium\util\Inflector;
use lithium\analysis\Logger;
use PhpAmqpLib\connections\AbstractConnection;

class Amqp extends \lithium\core\StaticObject {

	/**
	 * A map of producer objects mapped to names.
	 *
	 * @var array
	 */
  private static $_producers = array();

  /**
   * A map of consumer objects mapped to names.
   *
   * @var array
   */
  private static $_consumers = array();

	/**
	 * Placeholder for class dependencies i.e. Producers and Consumers
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'producers' => 'Producer',
    'consumers' => 'Consumer'
	);

  /**
   * The request object for URL resolution
   *
   * @var object
   */
  protected static $_request = null;

  /**
   * Mode constants to define how publishing via model save behaves
   */
  const PUBLISH_MODE_BLOCK = 'block';
  const PUBLISH_MODE_CONTINUE = 'continue';
  const PUBLISH_MODE_FOLLOW = 'follow';

  const NON_PERSISTENT = 1;
  const PERSISTENT = 2;

  private static function _getConnection() {
    return Connections::get('li3_amqp');
  }

  /*
   * This alters default baseamqp options from:
   * passive: false -> true
   * declare: true -> false
   */
  private static function _getProducer($name) {
    $options = array(
      'exchangeOptions' => array(
        'name' => '',
        'type' => 'direct',
        'passive' => true,
        'declare' => false
      ),
      'queueOptions' => array(
        'name' => null
      )
    );
    return static::_get('producers', $name, $options);
  }

  private static function _getConsumer($name) {
    $options = array();
    return static::_get('consumers', $name, $options);
  }

  private static function _get($path, $name, $options = array()) {
    $config = Libraries::get('li3_amqp');
    $default = array(
      'exchangeOptions' => array(),
      'queueOptions' => array()
    );

    if ($object = isset($config[$path][$name]) ? $config[$path][$name] : false) {
      $object = $object + $default;
      $options = $options + $default;

      $object['class'] = empty($object['class']) ? static::$_classes[$path] : $object['class'];

      $classType = ucfirst(Inflector::singularize($path));

      $object['exchangeOptions'] = array_merge($options['exchangeOptions'], $object['exchangeOptions']);
      $object['queueOptions'] = array_merge($options['queueOptions'], $object['queueOptions']);
      
      if (isset($object['callback'])) {
        $callback = $object['callback'];
        try {
          $object['callback'] = array(Libraries::instance($path, $callback, array()), 'execute');
        } catch (ClassNotFoundException $e) {
          throw new ClassNotFoundException(sprintf("Amqp %s callback of class `%s` not found during Libraries::instance(): %s", $classType, $callback, $e->getMessage()), null, $e);
        }
      }

      if (empty($object['connection'])) {
        $object['connection'] = Connections::get($name);
      } else if (is_string($object['connection']) && $object['connection'] !== 'default') {
        $object['connection'] = Connections::get($object['connection']);
      }

      if (!$object['connection'] instanceof AbstractConnection) {
        $object['connection'] = static::_getConnection();
      }

			try {
        return Libraries::instance($path, $object['class'], $object);
			} catch (ClassNotFoundException $e) {
				throw new ClassNotFoundException(sprintf("Amqp %s of class `%s` not found during Libraries::instance(): %s", $classType, $object['class'], $e->getMessage()), null, $e);
			}
    }
    return null;
  }

  public static function producer($name = null) {
    $producers = static::$_producers;
    if (isset($name) && !isset($producers[$name])) {
      $producers[$name] = static::_getProducer($name);
      static::$_producers = $producers;
    }
    return $name !== null ? $producers[$name] : $producers;
  }

  public static function consumer($name = null) {
    $consumers = static::$_consumers;
    if (isset($name) && !isset($consumers[$name])) {
      $consumers[$name] = static::_getConsumer($name);
      static::$_consumers = $consumers;
    }
    return $name !== null ? $consumers[$name] : $consumers;
  }

  public static function request($request = null) {
    if (isset($request)) {
      static::$_request = $request;
    }
    return static::$_request;
  }

  public static function applyFilters($config) {
    foreach ($config['producers'] as $key => $params) {
      if (array_key_exists('class', $params)) {
        $producer = Libraries::locate('producers', $params['class']);
        if ($model = $producer::model()) {
          $model::applyFilter('save', function($self, $params, $chain) use ($key) {
            $options = $params['options'];
            if (array_key_exists('amqp', $options)) {
              $amqp = is_array($options['amqp']) ? $options['amqp'] : array();
              $amqp += array(
                'mode' => self::PUBLISH_MODE_CONTINUE
              );
              return static::_publish($key, $self, $params, $chain, $amqp);
            }
            return $chain->next($self, $params, $chain);
          });
        }
      }
    }
  }

  protected static function _publish($key, $self, $params, $chain, $options) {
    $producer = static::producer($key);
    if ($options['mode'] === self::PUBLISH_MODE_CONTINUE) {
      $producer->save($params['entity']);
      return $chain->next($self, $params, $chain);
    }
    if ($options['mode'] === self::PUBLISH_MODE_BLOCK) {
      $producer->save($params['entity']);
      return true;
    }
    if ($options['mode'] === self::PUBLISH_MODE_FOLLOW) {
      $return = $chain->next($self, $params, $chain);
      $producer->save($params['entity']);
      return $return;
    }
  }
}
