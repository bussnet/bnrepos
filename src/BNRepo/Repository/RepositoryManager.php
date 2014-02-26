<?php

namespace BNRepo\Repository;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class RepositoryManager
 * Manage Repositories in a lazy way
 * @package BNRepo\Repository
 */
class RepositoryManager {

	static $config = array();
	static $repositories = array();

	/**
	 * @param $id
	 * @return Repository
	 * @throws RepositoryNotFoundException
	 */
	public static function getRepository($id) {
		if (!array_key_exists($id, self::$repositories)) {
            if (!array_key_exists($id, self::$config))
				throw new RepositoryNotFoundException();
			$cfg = self::$config[$id];
			self::$repositories[$id] = self::createRepository($cfg['type'], $cfg);
		}

		return self::$repositories[$id];
	}

	/**
	 * @param $resource
	 * @throws InvalidResourceException
	 * @throws NotFoundResourceException
	 */
	public static function importRepositoriesFromYamlFile($resource) {
		if (!stream_is_local($resource)) {
			throw new InvalidResourceException(sprintf('This is not a local file "%s".', $resource));
		}

		if (!file_exists($resource)) {
			throw new NotFoundResourceException(sprintf('File "%s" not found.', $resource));
		}

		try {
			$config = Yaml::parse($resource);
		} catch (ParseException $e) {
			throw new InvalidResourceException('Error parsing YAML.', 0, $e);
		}

		// empty file
		if (null === $config) {
			$config = array();
		}

		// not an array
		if (!is_array($config)) {
			throw new InvalidResourceException(sprintf('The file "%s" must contain a YAML array.', $resource));
		}

		self::addRepositories($config);
	}

	/**
	 * @param $repositories array with repositoryConfigs
	 */
	public static function addRepositories($repositories) {
		foreach ($repositories as $id => $repository) {
            // ID entweder als Key in der Liste, oder als parameter "id"
            if (isset($repository['id']))
                $id = $repository['id'];
            else
                $repository['id'] = $id;
            self::$config[$id] = $repository;
		}
	}

	/**
	 * @param $cfg Repository Config
	 */
	public static function addRepository($cfg) {
		self::addRepositories(array($cfg));
	}

	/**
	 * @param $type
	 * @param $cfg
	 * @return mixed
	 * @throws RepositoryTypeNotFoundException
	 */
	private static function createRepository($type, $cfg) {
		$cls = __NAMESPACE__.'\Repository' . ucfirst($type);
		if (!class_exists($cls))
			throw new RepositoryTypeNotFoundException();
		return new $cls($cfg);
	}

	/**
	 * Reset the Repository-Cache and -Config
	 */
	public static function reset() {
		self::$config = array();
		self::$repositories = array();
	}

	/**
	 * Gets the Linker, which can work over all repositories
	 * @return RepositoryLinker
	 */
	public static function getLinker() {
		return RepositoryLinker::getInstance();
	}
}

class InvalidResourceException extends \Exception {}
class NotFoundResourceException extends \Exception {}
class RepositoryNotFoundException extends \Exception {}
class RepositoryTypeNotFoundException extends \Exception {}
class ParamNotFoundException extends \Exception {}
