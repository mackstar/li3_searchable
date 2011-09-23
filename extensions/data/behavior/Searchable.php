<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_searchable\extensions\data\behavior;

/**
 * The `Translateable` class handles all translating MongoDB based content, the data is placed
 * into a language namespace for that record. This also needs to deal with validation to make sure
 * the model acts as expected in all scenarios.
 */
class Searchable extends \lithium\core\StaticObject {

	/**
	 * An array of configurations indexed by model class name, for each model to which this class
	 * is bound.
	 *
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'entity' => 'lithium\data\entity\Document'
	);

	/**
	 * A binding method to grab the class in question, with which you can alter the configuration
	 * and apply filters to
	 *
	 * @param string $class The current model class
	 * @param array $config Configuration options for the behavior
	 * @return
	 */
	public static function bind($class, array $config = array()) {

		$default = array('method' => 'or');
		$default += $config;
		static::$_configurations = $default;
		static::_save($class);


		$method = static::$_configurations['method'];

		$finder = function($self, $params, $chain) use ($class, $method) {
			$search = explode(' ', strtolower($params['options']['q']));

			if (count($search) > 1) {
				$params['options']['conditions'] = array(
					'$' . $method => array_map(function($term){
						return array('_keywords' => array('like' => '/' . $term . '/'));
					}, $search));
			}
			else {
				$params['options']['conditions'] = array(
					'_keywords' => array('like' => '/' . $search[0] . '/')
				);
			}
			return $chain->next($self, $params, $chain);
		};


		$class::finder('search', $finder);

		return static::$_configurations[$class] = $config;
	}

	/**
	 * A protected function to apply our filter to the classes save method.
	 * we add a _keywords array.
	 *
	 * @param string $class The model class to which the _save filter is applied
	 * @return mixed Upon success the current document will be returned. On fail false.
	 */
	protected static function _save($class) {

		$fields = static::$_configurations['fields'];

		$class::applyFilter('save', function($self, $params, $chain) use ($fields) {
			$entity = $params['entity'];

			if ($params['data']) {
				$entity->set($params['data']);
			}
			$data = $entity->data();
			$keywords = array();

			foreach ($fields as $field){
				if (strpos($field, '.') === false) {
					if (isset($data[$field])) {
						$keywords = array_merge(explode(' ', $data[$field]), $keywords);
					}
				}
				// If a dot exists we need to enumerate it to find what we need
				else{
					$path = explode('.', $field);
					$length = count($path) - 1;
					$current = $data;
					for ($i = 0; $i <= $length; $i++) {
						$key = $path[$i];
						// Likely to be the array we want
						if (!isset($current[$key]) && isset ($current[0])) {
							$content = array_map(function($row) use ($key){
								if(isset($row[$key])) {
									return $row[$key];
								}
								return null;	
							}, $current);
							foreach ($content as $keyword) {
								$keywords = array_merge(explode(' ', $keyword), $keywords);
							}
						}
						elseif(isset($current[$key])) {
							$current = $current[$key];
						}
					}
				}
			}
			$entity->_keywords = array_map('strtolower', $keywords);
			$params['entity'] = $entity;
			return $chain->next($self, $params, $chain);
		});
	}
}

?>