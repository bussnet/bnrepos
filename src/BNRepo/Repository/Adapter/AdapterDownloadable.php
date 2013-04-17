<?php

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
	 * @return boolean                  TRUE if the download was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot download
	 */
	public function download($sourceKey, $localTargetFile);

}
