<?php
/**
 * User: thorsten
 * Date: 15.04.13
 * Time: 11:25
 */

namespace BNRepo\Repository;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;
use BNRepo\Repository\Adapter\AdapterLocal;

class RepositoryLocal extends Repository {

	protected function createAdapter($cfg) {
		if (!isset($cfg['dir']) || empty($cfg['dir']))
			$cfg['dir'] = '/';
		return new AdapterLocal($cfg['dir'], isset($cfg['create']) ? $cfg['create'] : false);
	}

}
