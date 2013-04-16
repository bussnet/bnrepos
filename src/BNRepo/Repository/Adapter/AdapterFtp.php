<?php

namespace BNRepo\Repository\Adapter;


use Gaufrette\Adapter\Ftp;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

class AdapterFtp extends Ftp implements AdapterUploadable, AdapterDownloadable {
    public function __construct($directory, $host, $options = array()) {
        // strip double slashes and secure that the dir start with an slash and not end with one
        $directory = substr(preg_replace('~/+~', '/', '/' . $directory . '/'), 0, -1);
        parent::__construct($directory, $host, $options);
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

}
