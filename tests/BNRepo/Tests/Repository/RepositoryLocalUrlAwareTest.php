<?php
/**
 * User: thorsten
 * Date: 28.02.14
 * Time: 14:49
 */

namespace BNRepo\Tests\Repository;


class RepositoryLocalUrlAwareTest extends RepositoryLocalTest {

	protected $cfg_id = 'bnrepo-test-local-url-aware';

	public function testGetUrl() {
		$this->_testGetUrl($this->repo());
	}

	public function testGetPublicUrl() {
		/** @var $repo \BNRepo\Repository\RepositoryLocalUrlAware */
		$repo = $this->repo();
		$repo->write('public.txt', $this->test_content, true);
		$url = $repo->getUrl('public.txt');
		$this->assertEquals($this->test_content, file_get_contents($url), 'check Equal Content');
		$repo->delete('public.txt');
	}

}
