<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 13:19
 */

namespace BNRepo\Repository\Adapter;


interface AdapterLinkable {

	/**
	 * Downloads a file to Local
	 *
	 * @param string $key
	 * @param int $validTime
	 * @param array $options
	 *
	 * @return string Generated URL
	 * @throws \Gaufrette\Exception\FileNotFound   when key does not exist
	 * @throws \RuntimeException        Url cannot generated
	 */
	public function getUrl($key, $validTime=0, $options=array());

}
