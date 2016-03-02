<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 12:22
 */

namespace BNRepo\Tests\Repository;


use BNRepo\Repository\Adapter\UrlAware;
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

    public function __construct($name = NULL, array $data = array(), $dataName = '') {
	    RepositoryManager::reset();
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
		return parent::setUp();
	}

	/**
     * @return Repository
     */
    protected function repo() {
	    return RepositoryManager::getRepository($this->cfg_id, true);
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
        if ($repo->has('public.txt'))
            $repo->delete('public.txt');
        if ($repo->has('testImg.png'))
            $repo->delete('testImg.png');
        if ($repo->has('subdir/testFile.txt'))
            $repo->delete('subdir/testFile.txt');
        if ($repo->has('subdir/testFile1.txt'))
            $repo->delete('subdir/testFile1.txt');
        if ($repo->has('subdir/testFile2.txt'))
            $repo->delete('subdir/testFile2.txt');
	    if ($repo->has('subdir/subsubdir/testFile1.txt'))
		    $repo->delete('subdir/subsubdir/testFile1.txt');
	    if ($repo->has('subdir/subsubdir'))
            $repo->delete('subdir/subsubdir');
	    if ($repo->has('subdir'))
            $repo->delete('subdir');
	}

	protected function _testUploadFileSuccessfully(Repository $repo) {
        $local = self::DIR . 'src.txt';
		file_put_contents($local, $this->test_content);
		$key = 'trg.txt';
		$repo->push($local, $key);

		$this->assertTrue($repo->has($key), 'Uploaded File exists');
		$this->assertEquals($this->test_content, $repo->read($key), 'Uploaded File Content is Equal');

		// Download ExampleImg for BinaryTest
		$tmpImg = self::DIR .'testImg.png';
		file_put_contents($tmpImg, file_get_contents('http://www.google.de/images/srpr/logo4w.png'));
		$repo->push($tmpImg, 'testImg.png');
		$repo->pull('testImg.png', $tmpImg.'2');
		$this->assertFileEquals($tmpImg, $tmpImg . '2', 'Uploaded BinaryFile is Equal');
		unlink($tmpImg);
		unlink($tmpImg.'2');
	}

	protected function _testUploadFileSuccessfullyOverwrite(Repository $repo) {
		$local = self::DIR . 'src.txt';
		file_put_contents($local, $this->test_content);
		$key = 'trg.txt';
		$repo->push($local, $key);

		// Overwrite Download
        file_put_contents($local, 'TestOverwrite');
		$this->assertTrue($repo->push($local, $key, true), 'ForceUpload with existing file wo Exception');
		$this->assertEquals('TestOverwrite', $repo->read($key), 'Uploaded File Content ist Equal');
	}

	protected function _testUploadFileTargetFileExistsException(Repository $repo) {
		$local = self::DIR . 'src.txt';
		$key = 'trg.txt';
		file_put_contents($local, $this->test_content);
        $repo->write($key, $this->test_content);

		$this->setExpectedException('\Gaufrette\Exception\UnexpectedFile');
		$repo->push($local, $key);
	}

	protected function _testUploadFileSourceFileNotExistsException(Repository $repo) {
		$local = self::DIR . 'src.txt';
		$key = 'trg.txt';
		$this->setExpectedException('\Gaufrette\Exception\FileNotFound');
		$repo->push($local, $key);
	}


	protected function _testDownloadFileSuccessfully(Repository $repo) {
		$local = self::DIR . 'trg.txt';
		$key = 'src.txt';
        $repo->write($key, $this->test_content);
		$repo->pull($key, $local);

		$this->assertFileExists($local, 'Downloaded File exists');
		$this->assertEquals($this->test_content, file_get_contents($local), 'Downloaded File Content ist Equal');
	}

	protected function _testDownloadFileSuccessfullyOverwrite(Repository $repo) {
		$local = self::DIR . 'trg.txt';
		$key = 'src.txt';
        $repo->write($key, $this->test_content);
		$repo->pull($key, $local);

		// Overwrite Download
        $repo->write($key, 'TestOverwrite', true);
		$this->assertTrue($repo->pull($key, $local, true), 'ForceUpload with existing file wo Exception');
		$this->assertEquals('TestOverwrite', file_get_contents($local), 'Downloaded File Content ist Equal');
	}

	protected function _testDownloadFileTargetFileExistsException(Repository $repo) {
		$local = self::DIR . 'trg.txt';
		$key = 'src.txt';
        $repo->write($key, $this->test_content);
		file_put_contents($local, $this->test_content);

		$this->setExpectedException('\Gaufrette\Exception\UnexpectedFile');
		$repo->pull($key, $local);
	}

	protected function _testDownloadFileSourceFileNotExistsException(Repository $repo) {
		$local = self::DIR . 'trg.txt';
		$key = 'src.txt';

		$this->setExpectedException('\Gaufrette\Exception\FileNotFound');
		$repo->pull($key, $local);
	}


    protected function _testCorrectFileStructure(Repository $repo) {
        $this->assertEquals(array(), $repo->keys(), 'directory structure empty');

	    $repo->write('testFile.txt', $this->test_content);
	    $repo->write('testImg.png', $this->test_content);
        $repo->write('subdir/testFile1.txt', $this->test_content);
	    $repo->write('subdir/testFile2.txt', $this->test_content);
	    $repo->write('subdir/subsubdir/testFile1.txt', $this->test_content);

	    $fileList = array(
		    'subdir/testFile1.txt',
		    'subdir/testFile2.txt',
		    'subdir/subsubdir/testFile1.txt',
		    'testFile.txt',
		    'testImg.png'
	    );
	    sort($fileList);
	    $ls = $repo->keys(null, false);
	    sort($ls);
	    $this->assertEquals($fileList, $ls, 'directory structure correct');

	    $fileListWithPrefix = array(
			'subdir/subsubdir/testFile1.txt',
		    'subdir/testFile1.txt',
		    'subdir/testFile2.txt',
	    );
	    sort($fileListWithPrefix);

	    $ls = $repo->keys('subdir', false);
	    sort($ls);
	    $this->assertEquals($fileListWithPrefix, $ls, 'directory structure with prefix correct [without slashes]');

	    $ls = $repo->keys('/subdir', false);
	    sort($ls);
	    $this->assertEquals($fileListWithPrefix, $ls, 'directory structure with prefix correct [preSlash]');

	    // Check Prefix (show SubdirPrefix)
	    $fileListWithPrefix = array(
		    'subsubdir/testFile1.txt',
		    'testFile1.txt',
		    'testFile2.txt',
	    );
	    sort($fileListWithPrefix);

	    $ls = $repo->keys('subdir/', false);
	    sort($ls);
	    $this->assertEquals($fileListWithPrefix, $ls, 'directory structure with prefix correct [postSlash]');

	    $ls = $repo->keys('/subdir/', false);
	    sort($ls);
	    $this->assertEquals($fileListWithPrefix, $ls, 'directory structure with prefix correct [bothSlashes]');

    }
    protected function _testCorrectFileStructureWithDirectories(Repository $repo) {
        $this->assertEquals(array(), $repo->keys(), 'directory structure empty');

	    $repo->write('testFile.txt', $this->test_content);
	    $repo->write('testImg.png', $this->test_content);
        $repo->write('subdir/testFile1.txt', $this->test_content);
	    $repo->write('subdir/testFile2.txt', $this->test_content);
	    $repo->write('subdir/subsubdir/testFile1.txt', $this->test_content);

	    // All Files
	    $fileList = array(
		    'subdir',
		    'subdir/testFile1.txt',
		    'subdir/testFile2.txt',
		    'subdir/subsubdir',
		    'subdir/subsubdir/testFile1.txt',
		    'testFile.txt',
		    'testImg.png'
	    );
	    sort($fileList);
	    $ls = $repo->keys(null, true);
	    sort($ls);
	    $this->assertEquals($fileList, $ls, 'directory structure correct');


	    // PrefixFiles
	    $fileList = array(
		    'testFile1.txt',
		    'testFile2.txt',
	    );
	    sort($fileList);
	    $ls = $repo->keys('subdir/test', true);
	    sort($ls);
	    $this->assertEquals($fileList, $ls, 'directory structure with prefix (Dir+File) correct');


	    // Check Prefix (show SubdirPrefix)
	    $fileListWithPrefix = array(
		    'subdir',
		    'subdir/subsubdir',
		    'subdir/subsubdir/testFile1.txt',
		    'subdir/testFile1.txt',
		    'subdir/testFile2.txt',
	    );
	    sort($fileListWithPrefix);

	    $ls = $repo->keys('subdir', true);
	    sort($ls);
	    $this->assertEquals($fileListWithPrefix, $ls, 'directory structure with prefix correct [without slashes]');

	    $ls = $repo->keys('/subdir', true);
	    sort($ls);
	    $this->assertEquals($fileListWithPrefix, $ls, 'directory structure with prefix correct [preSlash]');


	    // Check SubDirPrefix (hidePrefix)
	    $fileListWithPrefix = array(
		    'subsubdir',
		    'subsubdir/testFile1.txt',
		    'testFile1.txt',
		    'testFile2.txt',
	    );
	    sort($fileListWithPrefix);

	    $ls = $repo->keys('/subdir/', true);
	    sort($ls);
	    $this->assertEquals($fileListWithPrefix, $ls, 'directory structure with prefix correct [bothSlashes]');

	    $ls = $repo->keys('subdir/', true);
	    sort($ls);
	    $this->assertEquals($fileListWithPrefix, $ls, 'directory structure with prefix correct [postSlash]');
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

	protected function _testGetContentType(Repository $repo) {
		$key = 'src.txt';
		$repo->write($key, $this->test_content);
		$this->assertTrue($repo->has($key), 'written File exists');
		$ct = $repo->contentType($key);
		// some Adapter (s3) dont check that this is an textFile
		if ($ct == 'text/plain') {
			$this->assertEquals('text/plain', $ct, 'Check ContentType TXT');
		} else {
			if ($ct == 'binary/octet-stream')
				$this->assertEquals('binary/octet-stream', $ct, 'Check ContentType TXT (FALLBACK binary/octet-stream)');
			else
				$this->assertEquals('application/octet-stream', $ct, 'Check ContentType TXT (FALLBACK application/octet-stream)');
		}

		// Download ExampleImg for BinaryTest
		$key2 = 'testImg.png';
		$tmpImg = self::DIR . $key2;
		file_put_contents($tmpImg, file_get_contents('http://www.google.de/images/srpr/logo4w.png'));
		$repo->push($tmpImg, $key2);
		$this->assertTrue($repo->has($key2), 'uploaded File exists');
		$this->assertEquals('image/png', $repo->contentType($key2), 'Check ContentType IMG');
		unlink($tmpImg);
	}

	protected function _testGetUrl(Repository $repo) {
		$key = 'subdir/testFile.txt';

		if ($repo->has($key))
			$repo->delete($key);

		$repo->write($key, $this->test_content);
		$this->assertTrue($repo->has($key), 'check file exists');

		$options = array();
		if (!$repo->getAdapter() instanceof UrlAware) {
			$baseUrl = 'Test_%s_Test';

			// test url building
			$downloadUrl = sprintf($baseUrl, basename($key));
			$url = sprintf($baseUrl, '{FILENAME}');
			$this->assertEquals($downloadUrl, $repo->getUrl($key, $url, $options), 'check DownloadUrl {FILENAME}');

			$downloadUrl = sprintf($baseUrl, ltrim(dirname($key), '/'));
			$url = sprintf($baseUrl, '{PATH}');
			$this->assertEquals($downloadUrl, $repo->getUrl($key, $url, $options), 'check DownloadUrl {PATH}');

			$downloadUrl = sprintf($baseUrl, ltrim($key, '/'));
			$url = sprintf($baseUrl, '{FULL_PATH}');
			$this->assertEquals($downloadUrl, $repo->getUrl($key, $url, $options), 'check DownloadUrl {FULL_PATH}');

			$downloadUrl = sprintf($baseUrl, 'http');
			$url = sprintf($baseUrl, '{SCHEME}');
			$this->assertEquals($downloadUrl, $repo->getUrl($key, $url, $options), 'check DownloadUrl {SCHEME}');

			if ($repo->getConfig('download_url')) {
				$baseUrl = $repo->getConfig('download_url');
				// download file
				$local = self::DIR . 'trg.txt';
				$url = $repo->getUrl($key, $baseUrl);
				file_put_contents($local, file_get_contents($url));

				$this->assertFileExists($local, 'Downloaded File exists');
				$this->assertEquals($this->test_content, file_get_contents($local), 'Downloaded File Content ist Equal');
			}

		} else {
			$local = self::DIR . 'trg.txt';
			$url = $repo->getUrl($key);
			file_put_contents($local, file_get_contents($url));

			$this->assertFileExists($local, 'Downloaded File exists');
			$this->assertEquals($this->test_content, file_get_contents($local), 'Downloaded File Content ist Equal');
		}

	}

	protected function _testAppending(Repository $repo) {
		$repo->write('src.txt', $this->test_content);
		$repo->append('src.txt', $this->test_content);
		$this->assertEquals($this->test_content. $this->test_content, $repo->read('src.txt'), 'Content Equal');
	}

}
