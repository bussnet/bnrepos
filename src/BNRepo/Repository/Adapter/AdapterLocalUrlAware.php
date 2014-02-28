<?php
/**
 * User: thorsten
 * Date: 28.02.14
 * Time: 14:45
 */

namespace BNRepo\Repository\Adapter;


class AdapterLocalUrlAware extends AdapterLocal implements Adapter, UrlAware {

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
	public function getUrl($key, $downloadUrl = null, $options = array()) {
		// genereic downloadmanager for whole repository, set download_url in Repositories.yml
		if (array_key_exists('download_url', $options)) {
			return sprintf($options['download_url'], $key);
		}
		return 'file://'.$this->computePath($key);
	}
}