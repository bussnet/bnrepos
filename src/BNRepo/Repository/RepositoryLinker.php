<?php

namespace BNRepo\Repository;


use BNRepo\Repository\Adapter\AdapterDownloadable;
use BNRepo\Repository\Adapter\AdapterUploadable;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;
use Gaufrette\File;
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
	 * return Repo, parsed from repoUlr (bnrepo://REPO_ID/path/to/file.ext)
	 * ATTENION: $uri would change to the real Path - the scheme and host was striped from the uri
	 * @param $uri
	 * @return Repository
	 * @throws NoValidLinkerScheme
	 */
	protected function getRepositoryFromUri(&$uri) {
		// Validate and Cache Uri
		if (!isset($this->uri_cache[$uri])) {
			$c = parse_url($uri);
			if (!isset($c['scheme']))
				throw new NoValidLinkerScheme('linker scheme is missed');
			if ($c['scheme'] != $this->scheme)
				throw new NoValidLinkerScheme(sprintf('%s is not a valid linker scheme - need %s', $c['scheme'], $this->scheme));
			$this->uri_cache[$uri] = $c;
		}
		$c = &$this->uri_cache[$uri];
		// change $uri to the path, so the var could use to work with the repo
		$uri = $c['path'];
		return RepositoryManager::getRepository($c['host']);
	}

	/**
	 * copies a file
	 *
	 * @param string $sourceKey
	 * @param string $targetKey
	 *
	 * @return boolean                  TRUE if the copy was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot copy
	 */
	public function copy($sourceKey, $targetKey) {
		return $this->write($targetKey, $this->read($sourceKey)) !== false;
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
	 * @return array
	 */
	public function keys($uri) {
		return $this->getRepositoryFromUri($uri)->keys();
	}

	/**
	 * Lists keys beginning with given prefix
	 * (no wildcard / regex matching)
	 *
	 * if adapter implements ListKeysAware interface, adapter's implementation will be used,
	 * in not, ALL keys will be requested and iterated through.
	 *
	 * @param  string $prefix
	 * @return array
	 */
	public function listKeys($uri, $prefix = '') {
		return $this->getRepositoryFromUri($uri)->listKeys($prefix);
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
	public function upload($localFile, $targetKey, $overwriteRemoteFile = false) {
		return $this->getRepositoryFromUri($targetKey)->upload($localFile, $targetKey, $overwriteRemoteFile);
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
	public function download($sourceKey, $localTargetFile, $overwriteLocalFile = false) {
		return $this->getRepositoryFromUri($sourceKey)->download($sourceKey, $localTargetFile, $overwriteLocalFile);
	}

}

class NoValidLinkerScheme extends \Exception {}
class AdapterDontSupportThisMethod extends \Exception {}