<?php

namespace BNRepo\Repository;

use BNRepo\Repository\Adapter\AdapterLocal;

class RepositoryLocal extends Repository {

	protected function createAdapter($cfg) {
		if (!isset($cfg['dir']) || empty($cfg['dir']))
			$cfg['dir'] = '/';
		return new AdapterLocal($cfg['dir'], isset($cfg['create']) ? $cfg['create'] : false);
	}

}
