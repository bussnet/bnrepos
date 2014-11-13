<?php

namespace BNRepo\Repository\Adapter;


use Gaufrette\Adapter\Ftp;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

class AdapterFtp extends Ftp implements Adapter {

	public function __construct($directory, $host, $options = array()) {
        // strip double slashes and secure that the dir start with an slash and not end with one
        $directory = substr(preg_replace('~/+~', '/', '/' . $directory . '/'), 0, -1);
        parent::__construct($directory, $host, $options);
    }

	/**
	 * Returns the created time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function ctime($key) {
		throw new \RuntimeException('Adapter does not support ctime function.');
//		$curl = curl_init();
//		curl_setopt($curl, CURLOPT_URL, "ftp://server/file");
//
//		curl_setopt($curl, CURLOPT_USERPWD, "user:pass");
//		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//		curl_setopt($curl, CURLOPT_NOBODY, 1);
//
//		curl_setopt($curl, CURLOPT_FILETIME, TRUE);
//
//		$result = curl_exec($curl);
//		$time = curl_getinfo($curl, CURLINFO_FILETIME);
//		print date('d/m/y H:i:s', $time);
	}

	/**
	 * Returns the last accessed time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function atime($key) {
		throw new \RuntimeException('Adapter does not support atime function.');
	}

	/**
	 * Uploads a Local file
	 *
	 * @param string $localFile
	 * @param string $targetKey
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function push($localFile, $targetKey) {
		return $this->write($targetKey, file_get_contents($localFile));
	}


	/**
	 * Downloads a file to Local
	 *
	 * @param string $sourceKey
	 * @param string $localTargetFile
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function pull($sourceKey, $localTargetFile) {
		return file_put_contents($localTargetFile, $this->read($sourceKey));
	}

	/**
	 * Returns an iterator over all keys (files and directories)
	 *
	 * @param null $prefix
	 * @return mixed
	 */
	/**
	 * {@inheritDoc}
	 */
	public function getKeyIterator($prefix = null) {
		throw new \RuntimeException('Adapter does not support getKeyIterator function.');
	}

	/**
	 * Add Directory-Prefix and remove fullPath and emptyDirLine from output for similar response to local/ftp etc
	 * {@inheritDoc}
	 */
	public function keysWithPrefix($prefix, $withDirectories = false) {
		return $this->fetchKeys($prefix, $withDirectories);
	}

	public function fetchKeys($prefix = null, $withDirectories = false, $active_dir='') {
		$path = $prefix;
		if (substr($path, -1) != '/') {
			$path = ltrim(dirname($prefix), '.');
			$fileSearch = basename($prefix);
		}

		// add slash to beginning and remove from end
		$path = rtrim(preg_replace('/^[\/]*([^\/].*)[\/]?$/', '/$1', $path), '/');

		$items = $this->listDirectory($path.$active_dir);

		$keys = $withDirectories?$items['dirs']:array();
		foreach ($items['dirs'] as $dir) {
			// if subfolder dont start with fileSearch -> ignore
			if (!empty($fileSearch) && strpos($dir, $fileSearch) === false) continue;
			$keys = array_merge($keys, $this->fetchKeys($path, $withDirectories, $dir));
		}
		if ($withDirectories)
			$keys = array_merge($items['dirs'], $keys);
		$keys = array_merge($items['keys'], array_unique($keys));

		// remove path
		if (!empty($prefix)) {
			$pathLen = mb_strlen($path);
			array_walk($keys, function(&$file) use($pathLen) {
				$file = substr($file, $pathLen);
			});
			if (!empty($fileSearch))
				$keys = array_filter($keys, function($file) use($fileSearch) {
					return strpos($file, $fileSearch) !== false;
				});
		}
		return $keys;
	}

	/**
	 * Computes the path for the given key
	 *
	 * @param string $key
	 */
	protected function computePath($key) {
		return rtrim($this->directory, '/') . '/' . $key;
	}

	/**
	 * Returns the MimeType of the given Key
	 * @param $key
	 * @return mixed
	 */
	public function getContentType($key) {
		/* List of Options
		 * FILEINFO_NONE => 'PHP script, ASCII text'
		 * FILEINFO_MIME_TYPE => 'text/x-php'
		 * FILEINFO_MIME => 'text/x-php; charset=us-ascii'
		 */
		$finfo = new \finfo(); // return mime type ala mimetype extension
		return $finfo->file($this->getFtpUrl($key), FILEINFO_MIME_TYPE);
	}

	/**
	 * Build the FTP Url with this Data
	 * @param $key
	 * @return string
	 */
	protected function getFtpUrl($key) {
		return 'ftp://' . $this->username . ':' . $this->password . '@' . $this->host . $this->computePath($key);
	}

	/**
	 * {@inheritDoc}
	 */
	public function append($key, $content) {
		// @todo make better if private method getConnection() in GaufretteAdapter is changed
		return $this->write($key, $this->read($key) . $content);
//		$path = $this->computePath($key);
//		$directory = dirname($path);
//
//		$this->ensureDirectoryExists($directory, true);
//
//		$temp = fopen('php://temp', 'a');
//		$size = fwrite($temp, $content);
//
//		if (!ftp_fput($this->getConnection(), $path, $temp, $this->mode)) {
//			fclose($temp);
//			return false;
//		}
//
//		fclose($temp);
//
//		return $size;
	}

}
