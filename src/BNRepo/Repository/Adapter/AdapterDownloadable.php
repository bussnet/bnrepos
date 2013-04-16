<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 13:19
 */

namespace BNRepo\Repository\Adapter;


use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

interface AdapterDownloadable {

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
	public function download($sourceKey, $localTargetFile);

}
