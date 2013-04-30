<?php
/**
 * User: thorsten
 * Date: 18.04.13
 * Time: 14:03
 */

namespace BNRepo\Repository\Adapter;


use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

interface Adapter extends \Gaufrette\Adapter {
	/**
	 * Returns the created time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function ctime($key);

	/**
	 * Returns the last accessed time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function atime($key);


	/**
	 * Returns an iterator over all keys (files and directories)
	 *
	 * @param null $prefix
	 * @return mixed
	 */
	public function getKeyIterator($prefix = null);

	/**
	 * Returns an array of all keys (files and directories)
	 *
	 * @param null $prefix
	 * @return array
	 */
	public function keys($prefix=null, $withDirectories=false);

	/**
	 * Returns the MimeType of the given Key
	 * @param $key
	 * @return mixed
	 */
	public function getContentType($key);

	/**
	 * Downloads a file to Local
	 *
	 * @param string $sourceKey
	 * @param string $localTargetFile
	 *
	 * @return boolean                  TRUE if the download was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot download
	 */
	public function pull($sourceKey, $localTargetFile);

	/**
	 * Uploads a Local file
	 *
	 * @param string $localFile
	 * @param string $targetKey
	 *
	 * @return boolean                  TRUE if the upload was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot upload
	 */
	public function push($localFile, $targetKey);

	/**
	 * Appends the given content on the file
	 *
	 * @param string $key
	 * @param string $content
	 *
	 * @return integer|boolean The number of bytes that were written into the file
	 */
	public function append($key, $content);
}