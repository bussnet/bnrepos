<?php
/**
 * User: thorsten
 * Date: 09.11.15
 * Time: 10:28
 */

namespace BNRepo\Tests\Repository;


use BNRepo\Repository\RepositoryManager;
use Symfony\Component\Yaml\Yaml;

class RepositoryS3Test extends \PHPUnit_Framework_TestCase {


	function __construct() {
		RepositoryManager::reset();
		$yml_file = __DIR__ . '/../../../../../tibidono/app/config/repositories.yml';
		RepositoryManager::importRepositoriesFromYamlFile($yml_file);
		$this->cfg = Yaml::parse($yml_file);
	}

	public function testDirectoryList() {
		$repo = RepositoryManager::getRepository('tibidono-public');
		print_r($repo->keys('/shop_uploads/'));
		print_r($repo->keys('/shop_voucher_body/756/'));
	}

	public function testDirectoryList2() {
		$repo = RepositoryManager::getRepository('bnrepo-test-local');
		print_r($repo->keys('/shop_uploads/'));
	}


}
