<?php

namespace BNRepo\Tests\Repository;


use BNRepo\Repository\RepositoryManager;

class RepositorySftpTest extends RepositoryTest {

	protected $cfg_id = 'bnrepo-test-sftp';

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

    public function testCorrectFileStruture() {
        $this->_testCorrectFileStruture($this->repo());
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

}
