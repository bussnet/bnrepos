<?php

namespace BNRepo\Repository;


use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;
use Gaufrette\Stream;

/**
 * Class RepositoryLinker
 * designed to interact with more Repositories (copy, move)
 * or simpler usage with URIs instead of filePaths (bnrepo://REPO_ID/path/to/file.ext)
 * @package BNRepo\Repository
 */
class RepositoryLinker {

	/**
	 * @var RepositoryLinker
	 */
	static $instance;

	/**
	 * scheme for Links: $scheme://REPO-ID/Path/to/file.ext
	 * @var string
	 */
	public $scheme = 'bnrepo';

	/**
	 * UriCache, so every uri schould parse once
	 * @var array
	 */
	protected $uri_cache = array();

	/**
	 * @return RepositoryLinker
	 */
	public static function getInstance() {
	    if (!isset(static::$instance)) {
	        static::$instance = new static();
	    }
	    return static::$instance;
	}

	/**
	 * @param string $scheme
	 * @return $this
	 */
	public function setScheme($scheme) {
		$this->scheme = $scheme;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getScheme() {
		return $this->scheme;
	}


//	/**
//   * Parse the URI and returns an Obj with Repo, RepoID und Dir
//   * Alternative concept to getRepositoryFromUri() with $uri as ReferenceParam
//	 * @param $uri
//	 * @return \ArrayObject
//	 * @throws NoValidLinkerScheme
//	 */
//	public function parseRepositoryUri($uri) {
//		if (!isset($this->uri_cache[$uri])) {
//			$arr = parse_url($uri);
//			if ($arr['scheme'] != $this->scheme)
//				throw new NoValidLinkerScheme(sprintf('%s is not a valid linker scheme - need %s', $arr['scheme'], $this->scheme));
//			$this->uri_cache[$uri] = new \ArrayObject(array(
//				'repo' => $arr['host'],
//				'dir' => $arr['path'],
//				'obj' => RepositoryManager::getRepository($arr['repo'])
//			));
//		}
//		return $this->uri_cache[$uri];
//	}

	/**
	 * Parse the URI and split into parts
	 * @param $uri
	 * @return mixed
	 * @throws NoValidLinkerScheme
	 */
	protected function parseUri($uri) {
		// Validate and Cache Uri
		if (!isset($this->uri_cache[$uri])) {
			$c = parse_url($uri);
			if (!isset($c['scheme']))
				throw new NoValidLinkerScheme('linker scheme is missed');
			if ($c['scheme'] != $this->scheme)
				throw new NoValidLinkerScheme(sprintf('%s is not a valid linker scheme - need %s', $c['scheme'], $this->scheme));
			$this->uri_cache[$uri] = array(
				'repo' => $c['host'],
				'path' => $c['path'],
				'file' => basename($c['path']),
				'dir' => dirname($c['path']),
			);
		}
		return $this->uri_cache[$uri];
	}

	/**
	 * return Repo, parsed from repoUlr (bnrepo://REPO_ID/path/to/file.ext)
	 * ATTENION: $uri would change to the real Path - the scheme and host was striped from the uri
	 * @param $uri
	 * @return Repository
	 * @throws NoValidLinkerScheme
	 */
	protected function getRepositoryFromUri(&$uri) {
		$c = $this->parseUri($uri);
		// change $uri to the path, so the var could use to work with the repo
		$uri = $c['path'];
		return RepositoryManager::getRepository($c['repo']);
	}

	/**
	 * @param $uri
	 * @return mixed
	 */
	protected function getPathFromUri($uri) {
		$c = $this->parseUri($uri);
		return $c['path'];
	}


	/**
	 * Check if 2URIs have the same Repo
	 * @param $uri1
	 * @param $uri2
	 * @return bool
	 */
	protected function urisHaveSameRepository($uri1, $uri2) {
		$u1 = $this->parseUri($uri1);
		$u2 = $this->parseUri($uri2);
		return $u1['repo'] == $u2['repo'];
	}

	/**
	 * Check if 2URIs are equal
	 * @param $uri1
	 * @param $uri2
	 * @return bool
	 */
	protected function urisAreEqual($uri1, $uri2) {
		return $this->urisHaveSameRepository($uri1, $uri2) && $this->getPathFromUri($uri1) == $this->getPathFromUri($uri2);
	}

	/**
	 * Check an URI if is Valid
	 * @param $uri
	 * @return string
	 */
	public function isValidUri($uri) {
		return strtolower(substr($uri, 0, strlen($this->scheme . '://'))) == $this->scheme . '://';
	}

	/**
	 * copies a file
	 *
	 * @param string $sourceKey
	 * @param string $targetKey
	 * @param boolean $overwrite Whether to overwrite the file if exists
	 *
	 * @return boolean                  TRUE if the copy was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot copy
	 */
	public function copy($sourceKey, $targetKey, $overwrite = false) {
		return $this->write($targetKey, $this->read($sourceKey), $overwrite) !== false;
	}

	/**
	 * Indicates whether the file matching the specified key exists
	 *
	 * @param string $key
	 *
	 * @return boolean TRUE if the file exists, FALSE otherwise
	 */
	public function has($key) {
		return $this->getRepositoryFromUri($key)->has($key);
	}

	/**
	 * moves a file
	 *
	 * @param string $sourceKey
	 * @param string $targetKey
	 *
	 * @return boolean                  TRUE if the move was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot move
	 */
	public function move($sourceKey, $targetKey) {
		if ($this->copy($sourceKey, $targetKey)) {
			return $this->delete($sourceKey);
		}
		return false;
	}

	/**
	 * Returns the file matching the specified key
	 *
	 * @param string  $key    Key of the file
	 * @param boolean $create Whether to create the file if it does not exist
	 *
	 * @throws FileNotFound
	 * @return File
	 */
	public function get($key, $create = false) {
		return $this->getRepositoryFromUri($key)->get($key, $create);
	}

	/**
	 * Deletes the file matching the specified key
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function delete($key) {
		return $this->getRepositoryFromUri($key)->delete($key);
	}

	/**
	 * Returns an array of all keys
	 *
	 * @param $uri
	 * @param bool $withDirectories
	 * @return array
	 */
	public function keys($uri, $withDirectories=false) {
		return $this->getRepositoryFromUri($uri)->keys($uri, $withDirectories);
	}

	/**
	 * Lists keys beginning with given prefix
	 * (no wildcard / regex matching)
	 *
	 * if adapter implements ListKeysAware interface, adapter's implementation will be used,
	 * in not, ALL keys will be requested and iterated through.
	 *
	 * @param  string $uri
	 * @return array
	 */
	public function listKeys($uri) {
		return $this->getRepositoryFromUri($uri)->listKeys($uri);
	}

	/**
	 * Returns the last modified time of the specified file
	 *
	 * @param string $key
	 *
	 * @return integer An UNIX like timestamp
	 */
	public function mtime($key) {
		return $this->getRepositoryFromUri($key)->mtime($key);
	}

	/**
	 * Returns the checksum of the specified file's content
	 *
	 * @param string $key
	 *
	 * @return string A MD5 hash
	 */
	public function checksum($key) {
		return $this->getRepositoryFromUri($key)->checksum($key);
	}

	/**
	 * Creates a new stream instance of the specified file
	 *
	 * @param string $key
	 *
	 * @return Stream
	 */
	public function createStream($key) {
		return $this->getRepositoryFromUri($key)->createFile($key);
	}

	/**
	 * Creates a new File instance and returns it
	 *
	 * @param string     $key
	 *
	 * @return File
	 */
	public function createFile($key) {
		return $this->getRepositoryFromUri($key)->createFile($key);
	}

	/**
	 * Reads the content from the file
	 *
	 * @param  string                 $key Key of the file
	 * @throws FileNotFound when file does not exist
	 * @throws \RuntimeException      when cannot read file
	 *
	 * @return string
	 */
	public function read($key) {
		return $this->getRepositoryFromUri($key)->read($key);
	}

	/**
	 * Writes the given content into the file
	 *
	 * @param string  $key       Key of the file
	 * @param string  $content   Content to write in the file
	 * @param boolean $overwrite Whether to overwrite the file if exists
	 *
	 * @return integer The number of bytes that were written into the file
	 */
	public function write($key, $content, $overwrite = false) {
		return $this->getRepositoryFromUri($key)->write($key, $content, $overwrite);
	}

	/**
	 * Uploads a Local file
	 *
	 * @param string $localFile
	 * @param string $targetKey
	 * @param boolean $overwriteLocalFile
	 *
	 * @return boolean                  TRUE if the upload was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot upload
	 */
	public function push($localFile, $targetKey, $overwriteRemoteFile = false) {
		return $this->getRepositoryFromUri($targetKey)->push($localFile, $targetKey, $overwriteRemoteFile);
	}

	/**
	 * Downloads a file to Local
	 *
	 * @param string $sourceKey
	 * @param string $localTargetFile
	 * @param boolean $overwriteLocalFile
	 *
	 * @return boolean                  TRUE if the download was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot download
	 */
	public function pull($sourceKey, $localTargetFile, $overwriteLocalFile = false) {
		return $this->getRepositoryFromUri($sourceKey)->pull($sourceKey, $localTargetFile, $overwriteLocalFile);
	}

	/**
	 * Renames a file
	 *
	 * @param string $sourceKey
	 * @param string $targetKey
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function rename($sourceKey, $targetKey) {
		if (!$this->urisHaveSameRepository($sourceKey, $targetKey))
			return $this->move($sourceKey, $targetKey);
		return $this->getRepositoryFromUri($sourceKey)->rename($sourceKey, $this->getPathFromUri($targetKey));
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
	 * @throws FileNotFound   when key does not exist
	 * @throws \RuntimeException        Url cannot generated
	 */
	public function getUrl($key, $downloadUrl = null, $options = array()) {
		return $this->getRepositoryFromUri($key)->getUrl($key, $downloadUrl, $options);
	}


	/**
	 * Sends the Content from File with DownloadHeaders and EXIT
	 * @param $key
	 * @param null $downloadFileName if TRUE, forceDownload with filename from $key | string for special DownloadFilename
	 * @param null $contentType overwrite autodetect contentType
	 * @throws \Exception if headers already sent
	 */
	public function downloadFile($key, $downloadFileName = null, $contentType=null) {
		$this->getRepositoryFromUri($key)->downloadFile($key, $downloadFileName, $contentType);
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
		return $this->getRepositoryFromUri($key)->append($key, $content);
	}

}

class NoValidLinkerScheme extends \Exception {}
class AdapterDontSupportThisMethod extends \Exception {}