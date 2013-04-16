<?php

namespace BNRepo\Repository\Adapter;


use Gaufrette\Adapter\Local;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

class AdapterLocal extends Local implements AdapterUploadable, AdapterDownloadable {

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
	public function upload($localFile, $targetKey) {
		return copy($localFile, $this->computePath($targetKey));
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
	public function download($sourceKey, $localTargetFile) {
		return copy($this->computePath($sourceKey), $localTargetFile);
	}

}
