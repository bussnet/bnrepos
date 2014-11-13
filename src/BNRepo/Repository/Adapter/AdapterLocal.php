<?php

namespace BNRepo\Repository\Adapter;


use Gaufrette\Adapter\Local;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

class AdapterLocal extends Local implements Adapter {

	/**
	 * Returns the created time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function ctime($key) {
		return filectime($this->computePath($key));
	}

	/**
	 * Returns the last accessed time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function atime($key) {
		return fileatime($this->computePath($key));
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
		$path = $this->computePath($targetKey);
		$this->ensureDirectoryExists(dirname($path), true);
		return copy($localFile, $path);
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
		return copy($this->computePath($sourceKey), $localTargetFile);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getKeyIterator($prefix=null) {
		$this->ensureDirectoryExists($this->directory, false);

		// add slash to beginning and remove from end
		$prefix = rtrim(preg_replace('/^[\/]*([^\/].*)[\/]?$/', '/$1', $prefix), '/');
		$path = $this->directory .$prefix;

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator(
					$path,
					\FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
				)
			);
		} catch (\Exception $e) {
			$iterator = new \EmptyIterator;
		}
		return $iterator;
	}

	/**
	 * {@inheritDoc}
	 */
	public function keysWithPrefix($prefix, $withDirectories=false) {

		$path = $prefix;
		if (substr($path, -1) != '/') {
			$path = ltrim(dirname($prefix), '.');
			$fileSearch = basename($prefix);
		}
		$iterator = $this->getKeyIterator($path);

		// add slash to beginning and remove from end
		$path = rtrim(preg_replace('/^[\/]*([^\/].*)[\/]?$/', '/$1', $path), '/');

		$keys = array();
		$paths = array();
		$dirLength = strlen($this->directory.$path)+1; //+1 to remove the starting slash
		/** @var $item \SplFileInfo */
		foreach ($iterator as $item) {
			$p = substr($item->getPath(), $dirLength);
			if (!empty($fileSearch)) { // filter for fileNames
				// if  subfolder dont start with fileSearch or no subFolder and fileName dont starts with -> ignore
				if ((!$p && strpos($item->getFilename(), $fileSearch) === false) || ($p && strpos($p, $fileSearch) === false)) continue;
			}
			if (strlen($item->getPath()) > $dirLength)
				$paths[$p] = true;
			$keys[] = (!empty($p)?($p.'/'):'').$item->getFilename();
		}
		if ($withDirectories)
			$keys = array_merge(array_keys($paths), $keys);
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
		return $finfo->file($this->computePath($key), FILEINFO_MIME_TYPE);
	}

	/**
	 * {@inheritDoc}
	 */
	public function append($key, $content) {
		$path = $this->computePath($key);
		$this->ensureDirectoryExists(dirname($path), true);

		return file_put_contents($path, $content, FILE_APPEND);
	}

}


