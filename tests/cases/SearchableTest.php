<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_searchable\tests\cases;

use li3_searchable\tests\mocks\UsersMock;

class SearchableTest extends \lithium\test\Unit {

	public function setUp(){
		UsersMock::remove();
	}

	public function testInitiallyWorking() {

		$user = UsersMock::create(array(
			'name' => 'Ricky Macky'
		));
		$this->assertTrue($user->save());

		$users = UsersMock::search('all', array('q'=>'Ricky'));
		$this->assertEqual(1, count($users));

		$users = UsersMock::search('all', array('q'=>'rick'));
		$this->assertEqual(1, count($users));

		$users = UsersMock::search('all', array('q'=>'mack'));
		$this->assertEqual(1, count($users));

		$users = UsersMock::search('all', array('q'=>'rick mack'));
		$this->assertEqual(1, count($users));

		$users = UsersMock::search('all', array('q'=>'rick quack'));
		$this->assertEqual(1, count($users));

		$users = UsersMock::search('all', array('q'=>'boo hoo'));
		$this->assertEqual(0, count($users));

		UsersMock::remove();

		$user = UsersMock::create(array(
			'name' => 'Ricky', 'locales' => array(
				array('localized' => '友達', 'term' => 'friend'),
				array('localized' => 'Matey', 'term' => 'friend')
		)));

		$this->assertTrue($user->save());

		$user = UsersMock::first()->data();
		$this->assertTrue(in_array('matey', $user['_keywords']));
		$this->assertFalse(in_array('Matey', $user['_keywords']));

		$users = UsersMock::search('all', array('q'=>'友達'));
		$this->assertEqual(1, count($users));
		$this->assertEqual('Ricky', $users->first()->name);

		$users = UsersMock::search('all', array('q'=>'friend'));
		$this->assertEqual(0, count($users));

	}
}

?>