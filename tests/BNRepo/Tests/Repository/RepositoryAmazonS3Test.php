<?php

namespace BNRepo\Tests\Repository;


use BNRepo\Repository\RepositoryS3;

class RepositoryAmazonS3Test extends RepositoryTest {

	protected $cfg_id = 'bnrepo-test-s3';

	protected function tearDown() {
        parent::tearDown();
	    if ($this->repo()->has('public.txt'))
	        $this->repo()->delete('public.txt');
    }

    public function testUploadFileSuccessfully() {
        $this->_testUploadFileSuccessfully($this->repo());
	}

	public function testUploadFileSuccessfullyOverwrite() {
        $this->_testUploadFileSuccessfullyOverwrite($this->repo());
	}

	public function testUploadFileTargetFileExistsException() {
        $this->_testUploadFileTargetFileExistsException($this->repo());
	}

	public function testUploadFileSourceFileNotExistsException() {
        $this->_testUploadFileSourceFileNotExistsException($this->repo());
	}

	public function testDownloadFileSuccessfully() {
        $this->_testDownloadFileSuccessfully($this->repo());
    }

	public function testDownloadFileSuccessfullyOverwrite() {
        $this->_testDownloadFileSuccessfullyOverwrite($this->repo());
    }

	public function testDownloadFileTargetFileExistsException() {
        $this->_testDownloadFileTargetFileExistsException($this->repo());
    }

	public function testDownloadFileSourceFileNotExistsException() {
        $this->_testDownloadFileSourceFileNotExistsException($this->repo());
    }

    public function testCorrectFileStructure() {
        $this->_testCorrectFileStructure($this->repo());
    }

	public function testCorrectFileStructureWithDirectories() {
		$this->_testCorrectFileStructureWithDirectories($this->repo());
	}

	public function testDeleteFileSuccessfully() {
        $this->_testDeleteFileSuccessfully($this->repo());
    }

    public function testRenameFileSuccessfully() {
        $this->_testRenameFileSuccessfully($this->repo());
    }

    public function testLastModifiedDate() {
        $this->_testLastModifiedDate($this->repo());
    }

    public function testIsDirectory() {
        $this->_testIsDirectory($this->repo());
    }

	public function testGetContentType() {
		$this->_testGetContentType($this->repo());
	}

	public function testAppending() {
		$this->_testAppending($this->repo());
	}

	public function testGetUrl() {
		// test repo (public access)
		$repo = $this->repo();
		$this->_testGetUrl($repo);

		// test same repo (private access)
		$cfg = $repo->getConfig();
		$cfg['options']['default_acl'] = 'private';
		$cls = get_class($repo);
		$repo_private = new $cls($cfg);
		$this->_testGetUrl($repo_private);
	}

	public function testGetPublicUrl() {
	    /** @var $repo RepositoryS3 */
        $repo = $this->repo();
        $repo->write('public.txt', $this->test_content, true);
        $url = $repo->getUrl('public.txt', 10);
        $this->assertEquals($this->test_content, file_get_contents($url), 'check Equal Content');
        $repo->delete('public.txt');
    }

}
