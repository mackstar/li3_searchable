<?php

namespace li3_searchable\tests\mocks;

class UsersMock extends \li3_behaviors\extensions\Model {

	protected $_actsAs = array(
		'Searchable' => array(
			'fields' => array('name', 'nickname', 'username', 'locales.localized')
	));
}

?>