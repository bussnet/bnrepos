<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 13:19
 */

namespace BNRepo\Repository\Adapter;


use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

interface AdapterUploadable {
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
	public function upload($localFile, $targetKey);
}
