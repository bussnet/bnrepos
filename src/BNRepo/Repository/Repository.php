<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 11:24
 */

namespace BNRepo\Repository;


use Gaufrette\Adapter;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;
use Gaufrette\Filesystem;
use BNRepo\Repository\Adapter\AdapterDownloadable;
use BNRepo\Repository\Adapter\AdapterUploadable;

class Repository extends Filesystem {

	public function __construct($cfg) {
		$this->config = $cfg;
		parent::__construct($this->createAdapter($cfg));
	}

	/**
	 * Create the Adapter
	 * @param $cfg
	 * @throws \Exception
	 * @return Adapter
	 */
	protected function createAdapter($cfg) {
        throw new \Exception('implement abstract method');
    }

	/**
	 * @param $key
	 * @throws FileNotFound
	 */
	protected function assertHasFile($key) {
		if (!$this->has($key)) {
			throw new FileNotFound($key);
		}
	}

	/**
	 * Uploads a Local file
	 *
	 * @param string $localFile
	 * @param string $targetKey
	 * @param boolean $overwriteRemoteFile
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function upload($localFile, $targetKey, $overwriteRemoteFile=false) {
		if (!file_exists($localFile))
			throw new FileNotFound($localFile);

		if (!$overwriteRemoteFile && $this->has($targetKey)) {
			throw new UnexpectedFile($targetKey);
		}

		if ($this->adapter instanceof AdapterUploadable) {
			if (!$this->adapter->upload($localFile, $targetKey)) {
				throw new \RuntimeException(sprintf('Could not upload the localfile "%s" to "%s".', $localFile, $targetKey));
			}
		} else {
			if (!$this->adapter->write($targetKey, file_get_contents($localFile))) {
				throw new \RuntimeException(sprintf('Could not upload the localfile "%s" to "%s".', $localFile, $targetKey));
			}
		}

		return true;
	}


	/**
	 * Downloads a file to Local
	 *
	 * @param string $sourceKey
	 * @param string $localTargetFile
	 * @param boolean $overwriteLocalFile
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function download($sourceKey, $localTargetFile, $overwriteLocalFile=false) {
		$this->assertHasFile($sourceKey);

		if (!$overwriteLocalFile && file_exists($localTargetFile))
			throw new UnexpectedFile($localTargetFile);

		if ($this->adapter instanceof AdapterDownloadable) {
			if (!$this->adapter->download($sourceKey, $localTargetFile, $overwriteLocalFile)) {
				throw new \RuntimeException(sprintf('Could not download the file "%s" to local "%s".', $sourceKey, $localTargetFile));
			}
		} else {
			$content = $this->adapter->read($sourceKey);

			if (false === $content) {
				throw new \RuntimeException(sprintf('Could not read the "%s" key.', $sourceKey));
			}

			file_put_contents($localTargetFile, $content);
		}

		return true;
	}
}
