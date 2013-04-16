<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 11:25
 */

namespace BNRepo\Repository;

use BNRepo\Repository\Adapter\AdapterSftp;

class RepositorySftp extends Repository {

	public function createAdapter($cfg) {
		if (!isset($cfg['dir']) || empty($cfg['dir']))
			$cfg['dir'] = '/';
		if (!isset($cfg['host']) || empty($cfg['host']))
			throw new ParamNotFoundException('param host in sftp-repo not set');
		if (!isset($cfg['username']) || empty($cfg['username']))
			throw new ParamNotFoundException('param username in sftp-repo not set');

		$configuration = new \Ssh\Configuration($cfg['host']);
		if (isset($cfg['port']))
			$configuration->setPort($cfg['port']);
		if (isset($cfg['methods']))
			$configuration->setMethods($cfg['methods']);
		if (isset($cfg['callbacks']))
			$configuration->setCallbacks($cfg['callbacks']);

		if (array_key_exists('public_key', $cfg)) {
			$authentication = new \Ssh\Authentication\PublicKeyFile($cfg['username'], $cfg['public_key'], isset($cfg['private_key']) ? $cfg['private_key'] : null, isset($cfg['password']) ? $cfg['password'] : null);
		} elseif (array_key_exists('password', $cfg)) {
			$authentication = new \Ssh\Authentication\Password($cfg['username'], $cfg['password']);
		} else {
			$authentication = new \Ssh\Authentication\None($cfg['username']);
		}
		$session = new \Ssh\Session($configuration, $authentication);
		return new AdapterSftp(new \Ssh\Sftp($session), $cfg['dir'], isset($cfg['create']) ? $cfg['create'] : false);
	}
}

