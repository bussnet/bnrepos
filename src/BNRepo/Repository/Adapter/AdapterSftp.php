<?php

namespace BNRepo\Repository\Adapter;


use Gaufrette\Adapter\Sftp;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

class AdapterSftp extends Sftp implements Adapter {

	/**
	 * Returns the created time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function ctime($key) {
		$this->initialize();
		return filectime($this->sftp->getUrl($this->computePath($key)));
	}

	/**
	 * Returns the last accessed time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function atime($key) {
		$this->initialize();
		return fileatime($this->sftp->getUrl($this->computePath($key)));
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
		return copy($localFile, $this->sftp->getUrl($this->computePath($targetKey)));
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
		return copy($this->sftp->getUrl($this->computePath($sourceKey)), $localTargetFile);
	}


	/**
	 * OVERWRITE CAUSE ERROR ON DELETING DIRECTORY
	 * {@inheritDoc}
	 */
	public function delete($key) {
		// If unlink not work, try rmdir
		return parent::delete($key)
			?: rmdir($this->sftp->getUrl($this->computePath($key)));
	}

	/**
	 * {@inheritDoc}
	 */
	public function getKeyIterator($prefix = null) {
		throw new \RuntimeException('Adapter does not support getIteratorKey function.');
	}


	/**
	 * {@inheritDoc}
	 */
	public function keysWithPrefix($prefix, $withDirectories=false) {
		$this->initialize();

		$path = $prefix;
		if (substr($path, -1) != '/') {
			$path = ltrim(dirname($prefix), '.');
			$fileSearch = basename($prefix);
		}

		// add slash to beginning and remove from end
		$path = rtrim(preg_replace('/^[\/]*([^\/].*)[\/]?$/', '/$1', $path), '/');

		$results = $this->sftp->listDirectory($this->directory.$path, true);
		$files = array_map(array($this, 'computeKey'), $results['files']);

		$prefixLen = strlen($path);
		$keys = array();
		$dirs = array();
		foreach ($files as $file) {
			$file = substr($file, $prefixLen);
			$dir = ltrim(dirname($file),'.');

			if (!empty($fileSearch)) { // filter for fileNames
				// if  subfolder dont start with fileSearch or no subFolder and fileName dont starts with -> ignore
				if ((empty($dir) && strpos($file, $fileSearch) === false) || (!empty($dir) && strpos($dir, $fileSearch) === false)) continue;
			}

			if (!empty($dir))
				$dirs[] = $dir;
			$keys[] = $file;
		}

		if ($withDirectories)
			$keys = array_merge(array_unique($dirs), $keys);

		sort($keys);

		return $keys;
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
		return $finfo->file($this->sftp->getUrl($this->computePath($key)), FILEINFO_MIME_TYPE);
	}

	/**
	 * {@inheritDoc}
	 */
	public function append($key, $content) {
		return $this->write($key, $this->read($key).$content);
	}

}
