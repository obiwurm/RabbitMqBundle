<?php
/**
 * Class to access Producers and Consumers
 */

namespace li3_amqp\net;

use lithium\core\Libraries;
use lithium\data\Connections;
use lithium\core\ClassNotFoundException;
use lithium\action\DispatchException;

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
		'producer' => 'Producer'
	);

  const NON_PERSISTENT = 1;
  const PERSISTENT = 2;

  private static function _getConnection() {
    return Connections::get('li3_amqp');
  }

  private static function _getProducer($name) {
    $config = Libraries::get('li3_amqp');

    if ($producer = isset($config['producers'][$name]) ? $config['producers'][$name] : false) {
      $producer['class'] = empty($producer['class']) ? static::$_classes['producer'] : $producer['class'];

      if (empty($producer['connection']) || $producer['connection'] === 'default') {
        $producer['connection'] = static::_getConnection();
      } else if (is_string($producer['connection'])) {
        $producer['connection'] = Connections::get($producer['connection']);
      }
			try {
        return Libraries::instance('producer', $producer['class'], $producer);
			} catch (ClassNotFoundException $e) {
				throw new DispatchException("Producer of class `{$producer['class']}` not found.", null, $e);
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
