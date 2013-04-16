<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 11:25
 */

namespace BNRepo\Repository;

use BNRepo\Repository\Adapter\AdapterFtp;

class RepositoryFtp extends Repository {

	public function createAdapter($cfg) {
		if (!isset($cfg['dir']) || empty($cfg['dir']))
			$cfg['dir'] = '/';
		if (!isset($cfg['host']) || empty($cfg['host']))
			throw new ParamNotFoundException('param host in ftp-repo not set');

		$options = isset($cfg['options']) ? $cfg['options'] : array();
		if (isset($cfg['port']))
			$options['port'] = $cfg['port'];
		if (isset($cfg['username']))
			$options['username'] = $cfg['username'];
		if (isset($cfg['password']))
			$options['password'] = $cfg['password'];
		if (isset($cfg['create']))
			$options['create'] = $cfg['create'];
		if (isset($cfg['mode']))
			$options['mode'] = $cfg['mode'];

		return new AdapterFtp($cfg['dir'], $cfg['host'], $options);
	}
}
