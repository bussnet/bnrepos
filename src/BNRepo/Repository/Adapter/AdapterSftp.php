<?php

namespace BNRepo\Repository\Adapter;


use Gaufrette\Adapter\Sftp;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

class AdapterSftp extends Sftp implements AdapterUploadable, AdapterDownloadable {

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
	public function download($sourceKey, $localTargetFile) {
		return file_put_contents($localTargetFile, $this->read($sourceKey));
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

}
