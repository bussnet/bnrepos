<?php
/**
 * User: thorsten
 * Date: 22.04.13
 * Time: 11:04
 */

namespace BNRepo\Repository\Adapter;


interface UrlAware {

	/**
	 * Generates a URL to the File to Download/View
	 *
	 * @param string $key
	 * @param string $downloadUrl URL where the File could download (Controller with Repository::download())
	 * @param array $options
	 *
	 * @return string Generated URL
	 * @throws \Gaufrette\Exception\FileNotFound   when key does not exist
	 * @throws \RuntimeException        Url cannot generated
	 */
	public function getUrl($key, $downloadUrl = null, $options = array());

}