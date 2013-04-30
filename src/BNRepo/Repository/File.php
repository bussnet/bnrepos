<?php
/**
 * User: thorsten
 * Date: 18.04.13
 * Time: 13:59
 */

namespace BNRepo\Repository;


class File extends \Gaufrette\File {

	public $content_type;

	/**
	 * @var Repository
	 */
	protected $filesystem;

	/**
	 * File date created
	 * @var int ctime
	 */
	protected $ctime = null;

	/**
	 * File date accessed
	 * @var int atime
	 */
	protected $atime = null;


	public function __construct($key, Repository $filesystem) {
		parent::__construct($key, $filesystem);
		// BUGFIX for Gaufrette\File where $name == $key
		$this->name = basename($key);
	}

	/**
	 * Returns the file created time
	 *
	 * @return int
	 */
	public function getCtime() {
		return $this->ctime = $this->filesystem->ctime($this->key);
	}

	/**
	 * Returns the file created time
	 *
	 * @return int
	 */
	public function getAtime() {
		return $this->atime = $this->filesystem->atime($this->key);
	}

	/**
	 * @param bool $humanReadable return as humanreadable string with unit
	 * @return int size of the file
	 */
	public function getSize($humanReadable=false) {
		$bytes = parent::getSize();
		if (!$humanReadable)
			return $bytes;
		if ($bytes < 1024) {
			return $bytes . ' bytes';
		} else if ($bytes < 1024 * 1024) {
			return number_format($bytes / 1024, 0, ',', '.') . ' KB';
		} else {
			return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
		}
	}

	/**
	 * Return the mimetype of the File
	 * @return mixed
	 */
	public function getContentType() {
		return $this->content_type = $this->filesystem->contentType($this->key);
	}

	/**
	 * @param string $downloadUrl URL where the File could download (Controller with Repository::download())
	 *               NEEDED for all adapters which have no direct Urlaccess (Local, Ftp, SFTP etc.) -> these throw an exception
	 *               download_url could also be set throw the repoconfig as param
	 * @param array $options
	 * @return string URL
	 */
	public function getUrl($downloadUrl=null, $options=array()) {
		return $this->filesystem->getUrl($this->key, $downloadUrl, $options);
	}
}