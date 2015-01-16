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
use lithium\action\DispatchException;
use lithium\util\Inflector;
use PhpAmqpLib\connections\AbstractConnection;

class Amqp extends \lithium\core\StaticObject {

	/**
	 * A map of producer objects mapped to names.
	 *
	 * @var array
	 */
  private static $_producers = array();

	/**
	 * Placeholder for class dependencies i.e. Producers and Consumers
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'producers' => 'Producer'
	);

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

  private static function _get($path, $name, $options) {
    $config = Libraries::get('li3_amqp');

    if ($object = isset($config[$path][$name]) ? $config[$path][$name] : false) {
      $object['class'] = empty($object['class']) ? static::$_classes[$path] : $object['class'];

      if (!isset($object['exchangeOptions'])) {
        $object['exchangeOptions'] = array();
      }
      if (!isset($object['queueOptions'])) {
        $object['queueOptions'] = array();
      }

      $object['exchangeOptions'] = array_merge($options['exchangeOptions'], $object['exchangeOptions']);
      $object['queueOptions'] = array_merge($options['queueOptions'], $object['queueOptions']);

      if (empty($object['connection'])) {
        $object['connection'] = Connections::get($name);
      } else if (is_string($object['connection']) && $object['connection'] !== 'default') {
        $object['connection'] = Connections::get($object['connection']);
      }

      if (!$object['connection'] instanceof AbstractConnection) {
        $object['connection'] = static::_getConnection();
      }

      $classType = ucfirst(Inflector::singularize($path));

			try {
        return Libraries::instance($path, $object['class'], $object);
			} catch (ClassNotFoundException $e) {
				throw new DispatchException(sprintf("%s of class `%s` not found.", $classType, $object['class']), null, $e);
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

}
