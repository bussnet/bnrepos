<?php
/**
 * User: thorsten
 * Date: 28.02.14
 * Time: 14:49
 */

namespace BNRepo\Repository;

use BNRepo\Repository\Adapter\AdapterLocalUrlAware;

class RepositoryLocalUrlAware extends RepositoryLocal {

	protected function createAdapter($cfg) {
		if (!isset($cfg['dir']) || empty($cfg['dir']))
			$cfg['dir'] = '/';
		return new AdapterLocalUrlAware($cfg['dir'], isset($cfg['create']) ? $cfg['create'] : false);
	}

}
