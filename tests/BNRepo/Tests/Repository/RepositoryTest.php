<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 12:22
 */

namespace BNRepo\Tests\Repository;


use BNRepo\Repository\Repository;
use BNRepo\Repository\RepositoryManager;
use Symfony\Component\Yaml\Yaml;

class RepositoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Local Dir for Testings
	 */
	const DIR = '/tmp/repositoryTest/';

	protected $cfg;
	protected $test_content = 'Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit...';
	protected $cfg_id;

	protected $fileList = array(
        'subdir',
        'subdir/testFile.txt',
        'testFile.txt',
        'testImg.png'
    );


    public function __construct($name = NULL, array $data = array(), $dataName = '') {
	    parent::__construct($name, $data, $dataName);
	    $yml_file = __DIR__ . '/../../../../config/repositories.yml';
	    if (isset($_ENV['repositories']))
		    $yml_file = $_ENV['repositories'];
	    RepositoryManager::importRepositoriesFromYamlFile($yml_file);
        $this->cfg = Yaml::parse($yml_file);
    }

	protected function setUp() {
		if (empty($this->cfg_id))
			$this->markTestSkipped('$this->cfg_id not set');
		try {
			$this->repo();
			if (file_exists(self::DIR))
				shell_exec('rm -fr ' . self::DIR);
			mkdir(self::DIR);
			parent::setUp();
		} catch (\Exception $e) {
			$this->markTestSkipped('RepositoryConfiguration not exists - create ' . $this->cfg_id);
		}
	}

	/**
     * @return Repository
     */
    protected function repo() {
	    return RepositoryManager::getRepository($this->cfg_id);
    }

    protected function tearDown() {
        shell_exec('rm -fr ' . self::DIR);
	    $this->_tearDown($this->repo());
        parent::tearDown();
    }

    protected function _tearDown(Repository $repo) {
        if ($repo->has('trg.txt'))
            $repo->delete('trg.txt');
        if ($repo->has('src.txt'))
            $repo->delete('src.txt');

        if ($repo->has('testFile.txt'))
            $repo->delete('testFile.txt');
        if ($repo->has('testImg.png'))
            $repo->delete('testImg.png');
        if ($repo->has('subdir/testFile.txt'))
            $repo->delete('subdir/testFile.txt');
        if ($repo->has('subdir'))
            $repo->delete('subdir');
	}

	protected function _testUploadFileSuccessfully(Repository $repo) {
        $local = self::DIR . 'src.txt';
		file_put_contents($local, $this->test_content);
		$key = 'trg.txt';
		$repo->upload($local, $key);

		$this->assertTrue($repo->has($key), 'Uploaded File exists');
		$this->assertEquals($this->test_content, $repo->read($key), 'Uploaded File Content is Equal');

		// Download ExampleImg for BinaryTest
		$tmpImg = self::DIR .'testImg.png';
		file_put_contents($tmpImg, file_get_contents('http://www.google.de/images/srpr/logo4w.png'));
		$repo->upload($tmpImg, 'testImg.png');
		$repo->download('testImg.png', $tmpImg.'2');
		$this->assertFileEquals($tmpImg, $tmpImg . '2', 'Uploaded BinaryFile is Equal');
		unlink($tmpImg);
		unlink($tmpImg.'2');
	}

	protected function _testUploadFileSuccessfullyOverwrite(Repository $repo) {
		$local = self::DIR . 'src.txt';
		file_put_contents($local, $this->test_content);
		$key = 'trg.txt';
		$repo->upload($local, $key);

		// Overwrite Download
        file_put_contents($local, 'TestOverwrite');
		$this->assertTrue($repo->upload($local, $key, true), 'ForceUpload with existing file wo Exception');
		$this->assertEquals('TestOverwrite', $repo->read($key), 'Uploaded File Content ist Equal');
	}

	protected function _testUploadFileTargetFileExistsException(Repository $repo) {
		$local = self::DIR . 'src.txt';
		$key = 'trg.txt';
		file_put_contents($local, $this->test_content);
        $repo->write($key, $this->test_content);

		$this->setExpectedException('\Gaufrette\Exception\UnexpectedFile');
		$repo->upload($local, $key);
	}

	protected function _testUploadFileSourceFileNotExistsException(Repository $repo) {
		$local = self::DIR . 'src.txt';
		$key = 'trg.txt';
		$this->setExpectedException('\Gaufrette\Exception\FileNotFound');
		$repo->upload($local, $key);
	}


	protected function _testDownloadFileSuccessfully(Repository $repo) {
		$local = self::DIR . 'trg.txt';
		$key = 'src.txt';
        $repo->write($key, $this->test_content);
		$repo->download($key, $local);

		$this->assertFileExists($local, 'Downloaded File exists');
		$this->assertEquals($this->test_content, file_get_contents($local), 'Downloaded File Content ist Equal');
	}

	protected function _testDownloadFileSuccessfullyOverwrite(Repository $repo) {
		$local = self::DIR . 'trg.txt';
		$key = 'src.txt';
        $repo->write($key, $this->test_content);
		$repo->download($key, $local);

		// Overwrite Download
        $repo->write($key, 'TestOverwrite', true);
		$this->assertTrue($repo->download($key, $local, true), 'ForceUpload with existing file wo Exception');
		$this->assertEquals('TestOverwrite', file_get_contents($local), 'Downloaded File Content ist Equal');
	}

	protected function _testDownloadFileTargetFileExistsException(Repository $repo) {
		$local = self::DIR . 'trg.txt';
		$key = 'src.txt';
        $repo->write($key, $this->test_content);
		file_put_contents($local, $this->test_content);

		$this->setExpectedException('\Gaufrette\Exception\UnexpectedFile');
		$repo->download($key, $local);
	}

	protected function _testDownloadFileSourceFileNotExistsException(Repository $repo) {
		$local = self::DIR . 'trg.txt';
		$key = 'src.txt';

		$this->setExpectedException('\Gaufrette\Exception\FileNotFound');
		$repo->download($key, $local);
	}


    protected function _testCorrectFileStruture(Repository $repo) {
        $this->assertEquals(array(), $repo->keys(), 'directory structure empty');

	    $repo->write('testFile.txt', $this->test_content);
	    $repo->write('testImg.png', $this->test_content);
        $repo->write('/subdir/testFile.txt', $this->test_content);

	    $ls = $repo->keys();
	    $this->assertEquals(sort($this->fileList), sort($ls), 'directory structure correct');
    }

    protected function _testDeleteFileSuccessfully(Repository $repo) {
        $repo->write('subdir/testFile.txt', $this->test_content);
        $this->assertTrue($repo->delete('subdir/testFile.txt'), 'delete file');
        // Difference between Local/Ftp/Sftp etc and ex S3, where no Dir Exists
        if ($repo->has('subdir'))
            $this->assertTrue($repo->delete('subdir'), 'delete directory');
    }

    protected function _testRenameFileSuccessfully(Repository $repo) {
        $repo->write('src.txt', $this->test_content);
        $repo->rename('src.txt', 'trg.txt');
        $this->assertFalse($repo->has('src.txt'), 'old File deleted');
        $this->assertTrue($repo->has('trg.txt'), 'new File exists');
        $this->assertEquals($this->test_content, $repo->read('trg.txt'), 'Content Equal');
    }

    protected function _testLastModifiedDate(Repository $repo) {
        // test createdTime +/-10sec
        $before_created = time()-10;
        $repo->write('src.txt', $this->test_content);
        $after_created = time()+10;
        $this->assertGreaterThanOrEqual($before_created, $repo->mtime('src.txt'), 'Check Timestamp1');
        $this->assertLessThanOrEqual($after_created, $repo->mtime('src.txt'), 'Check Timestamp2');
    }

    protected function _testIsDirectory(Repository $repo) {
        $repo->write('subdir/testFile.txt', $this->test_content);
        $repo->getAdapter()->isDirectory('subdir');
        $this->assertTrue($repo->getAdapter()->isDirectory('subdir'), 'check isDirectory');
    }

}
