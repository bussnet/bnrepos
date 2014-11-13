<?php

namespace BNRepo\Repository;


use BNRepo\Repository\Adapter\UrlAware;
use Gaufrette\Adapter;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;
use Gaufrette\Filesystem;

/**
 * Class Repository
 * BaseClass for the different Repositories
 * @package BNRepo\Repository
 */
class Repository extends Filesystem {

	/**
	 * @var \BNRepo\Repository\Adapter\Adapter
	 */
	protected $adapter;

	/**
	 * @var array settings of the adapter
	 */
	protected $config;


	public function __construct($cfg) {
		$this->config = $cfg;
		parent::__construct($this->createAdapter($cfg));
	}

	/**
	 * Create the Adapter
	 * @param $cfg
	 * @throws \Exception
	 * @return Adapter
	 */
	protected function createAdapter($cfg) {
        throw new \Exception('implement abstract method');
    }

	/**
	 * @param $key
	 * @throws FileNotFound
	 */
	protected function assertHasFile($key) {
		if (!$this->has($key)) {
			throw new FileNotFound($key);
		}
	}

	/**
	 * Uploads a Local file
	 *
	 * @param string $localFile
	 * @param string $targetKey
	 * @param boolean $overwriteRemoteFile
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function push($localFile, $targetKey, $overwriteRemoteFile=false) {
		if (!file_exists($localFile))
			throw new FileNotFound($localFile);

		if (!$overwriteRemoteFile && $this->has($targetKey)) {
			throw new UnexpectedFile($targetKey);
		}

		if (!$this->adapter->push($localFile, $targetKey)) {
			throw new \RuntimeException(sprintf('Could not upload the localfile "%s" to "%s".', $localFile, $targetKey));
		}

		return true;
	}

	/**
	 * Normalize the key to an Path
	 *
	 * @param string $key The key for which to normalize the path
	 *
	 * @return string
	 */
	protected function normalizePath($key) {
		return substr(preg_replace('~/+~', '/', '/' . $key . '/'), 1, -1);
	}

	/**
	 * Pull a file to Local
	 *
	 * @param string $sourceKey
	 * @param string $localTargetFile
	 * @param boolean $overwriteLocalFile
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function pull($sourceKey, $localTargetFile, $overwriteLocalFile=false) {
		$this->assertHasFile($sourceKey);

		if (!$overwriteLocalFile && file_exists($localTargetFile))
			throw new UnexpectedFile($localTargetFile);

		if (!$this->adapter->pull($sourceKey, $localTargetFile, $overwriteLocalFile)) {
			throw new \RuntimeException(sprintf('Could not download the file "%s" to local "%s".', $sourceKey, $localTargetFile));
		}
		return true;
	}

	/**
	 * build an URI for a given Key
	 * @param $key
	 * @return string
	 */
	public function getUriForKey($key) {
		return RepositoryLinker::getInstance()->getScheme() . '://'.$this->config['id'].'/'.$this->normalizePath($key);
	}

	/**
	 * {@inheritDoc}
	 */
	public function createFile($key) {
		if ($this->adapter instanceof Adapter\FileFactory) {
			return $this->adapter->createFile($key, $this);
		}

		return new File($key, $this);
	}

	/**
	 * Returns the created time of the specified file
	 *
	 * @param string $key
	 *
	 * @return integer An UNIX like timestamp
	 */
	public function ctime($key) {
		$this->assertHasFile($key);

		return $this->adapter->ctime($key);
	}

	/**
	 * Returns the last accessed time of the specified file
	 *
	 * @param string $key
	 *
	 * @return integer An UNIX like timestamp
	 */
	public function atime($key) {
		$this->assertHasFile($key);

		return $this->adapter->atime($key);
	}

	/**
	 * Return an array of all keys
	 * @param null $prefix
	 * @param bool $withDirectories
	 * @return array
	 */
	public function keys($prefix = null, $withDirectories = false) {
		return $this->adapter->keysWithPrefix($prefix, $withDirectories);
	}

	/**
	 * Return an Iterator over all keys
	 * @param null $prefix
	 * @param bool $withDirectories
	 * @return array
	 */
	public function getKeyIterator($prefix = null, $withDirectories = false) {
		return $this->adapter->getKeyIterator($prefix, $withDirectories);
	}

	/**
	 * Returns the mimeType of the given key
	 * @param $key
	 * @return string
	 */
	public function contentType($key) {
		return $this->adapter->getContentType($key);
	}

	/**
	 * Sends the Content from File with DownloadHeaders and EXIT
	 * @param $key
	 * @param null $downloadFileName if TRUE, forceDownload with filename from $key | string for special DownloadFilename
	 * @param null $contentType overwrite autodetect contentType
	 * @throws \Exception if headers already sent
	 */
	public function downloadFile($key, $downloadFileName=null, $contentType=null) {
		if (headers_sent())
			throw new \Exception('could not send download headers - headers already sent');

		if ($this->adapter instanceof UrlAware) {
			$options = array();
			if ($downloadFileName !== null)
				$options['filename'] = $downloadFileName;
			if ($contentType !== null)
				$options['content_type'] = $contentType;
			$url = $this->getUrl($key, null, $options);
			header('Location: ' . $url);
		} else {
			$file = $this->get($key, false);
			$contentType = $contentType ? : $file->getContentType();
			// some special headers for IE
			header("Pragma: private");
			header("Cache-control: private, must-revalidate");
			header("Content-Type: {$contentType}");
			header("Content-Length: {$file->getSize()}; ");
			if ($downloadFileName === true)
				$downloadFileName = $file->getName();

			if (!empty($downloadFileName)) {
				header("Content-Disposition: attachment; filename=\"{$downloadFileName}\"");
			}
			header("Content-Transfer-Encoding: binary");
			echo $this->read($key);
		}
	}

	/**
	 * Generates a URL to the File to Download/View
	 *
	 * @param string $key
	 * @param string $downloadUrl URL where the File could download (Controller with Repository::download())
	 *               NEEDED for all adapters which have no direct Urlaccess (Local, Ftp, SFTP etc.) -> these throw an exception
	 *               download_url could also be set throw the repoconfig as param
	 * @param array $options
	 *
	 * @return string Generated URL
	 * @throws \RuntimeException        Url cannot generated
	 */
	public function getUrl($key, $downloadUrl = null, $options = array()) {
		if ($this->adapter instanceof UrlAware) {
			if (array_key_exists('download_url', $this->config))
				$options['download_url'] = $this->generateDownloadUrl($key, $this->config['download_url']);
			$downloadUrl = $this->adapter->getUrl($key,$downloadUrl, $options);
		}
		return $this->generateDownloadUrl($key, $downloadUrl);
	}

	/**
	 * Generate DownloadURL - replace Placeholder
	 * {FILENAME} => only Filename from $key
	 * {PATH} => only path from $key
	 * {FULL_PATH} => complete path with FILENAME from $key
	 * {SCHEME} => active scheme http|https
	 * @param $key
	 * @param null $url
	 * @return mixed
	 * @throws \RuntimeException
	 */
	private function generateDownloadUrl($key, $url=null) {
		if (empty($url) && isset($this->config['download_url']))
			$url  = $this->config['download_url'];
		if (empty($url))
			throw new \RuntimeException('adapter dont support UrlAware - $download_url needed');
		$url = str_replace('{FULL_PATH}', ltrim($key, '/'), $url);
		$url = str_replace('{PATH}', ltrim(dirname($key), '/'), $url);
		$url = str_replace('{FILENAME}', basename($key), $url);
		$url = str_replace('{SCHEME}', $this->getActiveScheme() , $url);
		return $url;
	}

	/**
	 * find the active Scheme (http|https) and return
	 * @return string
	 */
	protected function getActiveScheme() {
		$isHttps = false;
		if (array_key_exists('HTTPS', $_SERVER))
			$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
		else if (array_key_exists('SCRIPT_URI', $_SERVER))
			$isHttps = strpos($_SERVER['SCRIPT_URI'], 'https://') === 0;
		else if (array_key_exists('SERVER_PORT', $_SERVER))
			$isHttps = $_SERVER['SERVER_PORT'] == 443;
		else if (array_key_exists('SERVER_PROTOCOL', $_SERVER))
			$isHttps = strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') === 0;
		return $isHttps ? 'https' : 'http';
	}

	/**
	 * Get an FileObj of the given Key
	 * @param string $key
	 * @param bool $create
	 * @return File
	 */
	public function get($key, $create = false) {
		// Overwrite to change the phpdoc @return to BNRepo File
		return parent::get($key, $create);
	}

	/**
	 * Appends the given content on the file
	 *
	 * @param string  $key       Key of the file
	 * @param string  $content   Content to append on the file
	 *
	 * @return integer The number of bytes that were written into the file
	 */
	public function append($key, $content) {
		$numBytes = $this->adapter->append($key, $content);

		if (false === $numBytes) {
			throw new \RuntimeException(sprintf('Could not write the "%s" key content.', $key));
		}

		return $numBytes;
	}

	/**
	 * return the adapter settings
	 * @return mixed
	 */
	public function getConfig($key = null, $default = null) {
		return empty($key) ? $this->config : (@$this->config[$key] ?: $default);
	}

}
