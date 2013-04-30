<?php

namespace BNRepo\Repository\Adapter;


use Gaufrette\Adapter\AmazonS3;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;

/**
 * Class AdapterAmazonS3
 * @package BNRepo\Repository\Adapter
 * @deprecated
 */
class AdapterAmazonS3 extends AmazonS3 implements Adapter {

	/**
	 * @var \AmazonS3
	 */
	protected $service;

	public function __construct(\AmazonS3 $service, $bucket, $options = array()) {
		throw new \RuntimeException('Adapter AWS-S3 SDKver1 not longer supported');
        if (isset($options['directory'])) { // strip double slashes and secure that the dir not start and ends with an slash
            $options['directory'] = substr(preg_replace('~/+~', '/', '/'. $options['directory'].'/'), 1, -1);
        }
        if (!isset($options['default_acl']))
            $options['default_acl'] = \AmazonS3::ACL_OWNER_FULL_CONTROL;
        parent::__construct($service, $bucket, $options);
    }

	/**
	 * Returns the created time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function ctime($key) {
		throw new \RuntimeException('Adapter does not support ctime function.');
	}

    /**
	 * Returns the last accessed time
	 *
	 * @param string $key
	 *
	 * @return integer|boolean An UNIX like timestamp or false
	 */
	public function atime($key) {
		throw new \RuntimeException('Adapter does not support atime function.');
	}

	/**
	 * Uploads a Local file
	 *
	 * @param string $localFile
	 * @param string $targetKey
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function push($localFile, $targetKey) {
		return $this->write($targetKey, file_get_contents($localFile));
	}


	/**
	 * Downloads a file to Local
	 *
	 * @param string $sourceKey
	 * @param string $localTargetFile
	 *
	 * @return boolean                  TRUE if the rename was successful
	 * @throws FileNotFound   when sourceKey does not exist
	 * @throws UnexpectedFile when targetKey exists
	 * @throws \RuntimeException        when cannot rename
	 */
	public function pull($sourceKey, $localTargetFile) {
		return file_put_contents($localTargetFile, $this->read($sourceKey));
	}


	/**
	 * Retrieve the S3 object URL for the given key.
	 *
	 * @param string $key
	 * @param integer|string $preauth Look at \AmazonS3::get_object_url() docs
	 * @param array $opt Look at \AmazonS3::get_object_url() docs
	 *
	 * @see \AmazonS3::get_object_url()
	 *
	 * @return string The S3 object URL
	 */
	public function getUrl($key, $validTime = 0, $options = array()) {
		if (is_numeric($validTime))
			$validTime = '+' . $validTime . ' seconds';
		return $this->service->get_object_url(
			$this->bucket,
			$this->computePath($key),
			$validTime,
			$options
		);
	}

	/**
     * OVERWRITE CAUSE PRIVATE FLAG
     *
	 * Computes the path for the specified key taking the bucket in account
	 *
	 * @param string $key The key for which to compute the path
	 *
	 * @return string
	 */
	protected function computePath($key) {
		$directory = $this->getDirectory();
		if (null === $directory || '' === $directory) {
            return substr(preg_replace('~/+~', '/', '/' .$key . '/'), 1, -1);
        }
        return substr(preg_replace('~/+~', '/', '/' . $directory.'/'. $key . '/'), 1, -1);
	}


    /**
     * OVERWRITE CAUSE PRIVATE FLAG
     * Ensures the specified bucket exists. If the bucket does not exists
     * and the create parameter is set to true, it will try to create the
     * bucket
     *
     * @throws \RuntimeException if the bucket does not exists or could not be
     *                          created
     */
    protected function ensureBucketExists() {
        if ($this->ensureBucket) {
            return;
        }

        if (isset($this->options['region'])) {
            $this->service->set_region($this->options['region']);
        }

        if ($this->service->if_bucket_exists($this->bucket)) {
            $this->ensureBucket = true;

            return;
        }

        if (!$this->options['create']) {
            throw new \RuntimeException(sprintf(
                'The configured bucket "%s" does not exist.',
                $this->bucket
            ));
        }

        $response = $this->service->create_bucket(
            $this->bucket,
            $this->options['region']
        );

        if (!$response->isOK()) {
            throw new \RuntimeException(sprintf(
                'Failed to create the configured bucket "%s".',
                $this->bucket
            ));
        }

        $this->ensureBucket = true;
    }

    /**
     * Add Directory-Prefix and remove fullPath and emptyDirLine from output for similar response to local/ftp etc
     * {@inheritDoc}
     * @deprecated
     */
    public function keys($prefix=null, $withDirectories=false) {
	    //@todo: implement $prefix and $withDirectories
        $this->ensureBucketExists();

        $list = $this->service->get_object_list($this->bucket, array(
            'prefix' => $this->getDirectory()
        ));

	    $keys = array();
        $dirLength = strlen($this->getDirectory())+1; //+1 to remove the starting slash
        foreach ($list as $file) {
            if (strlen(dirname($file)) > $dirLength)
                $keys[] = substr(dirname($file), $dirLength);
            elseif (strlen($file) > $dirLength)
                $keys[] = substr($file, $dirLength);
        }
        sort($keys);

        return $keys;
    }


    /**
     * OVERWRITE CAUSE NO CHANGABLE PUBLIC_ACL
     * {@inheritDoc}
     */
    public function write($key, $content) {
        $this->ensureBucketExists();

        $opt = array_replace_recursive(
            array('acl' => $this->options['default_acl']),
            $this->getMetadata($key),
            array('body' => $content)
        );

        $response = $this->service->create_object(
            $this->bucket,
            $this->computePath($key),
            $opt
        );

        if (!$response->isOK()) {
            return false;
        }
        ;

        return intval($response->header["x-aws-requestheaders"]["Content-Length"]);
    }

	/**
	 * {@inheritDoc}
	 */
	public function isDirectory($key) {
		// Check is not good, but the only solution
		// If the $key is from the keys() function and not exists, its an Directory
		return !$this->service->if_object_exists(
			$this->bucket,
			$this->computePath($key)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getKeyIterator($prefix = null) {
		throw new \RuntimeException('function getKeyIterator not support by this adapter');
	}


	/**
	 * Returns the MimeType of the given Key
	 * @param $key
	 * @return mixed
	 */
	public function getContentType($key) {
		// @todo implement
		throw new \RuntimeException('function getContentType not support by this adapter');
	}

	/**
	 * Appends the given content on the file
	 *
	 * @param string $key
	 * @param string $content
	 *
	 * @return integer|boolean The number of bytes that were written into the file
	 */
	public function append($key, $content) {
		// TODO: Implement append() method.
		throw new \RuntimeException('function getContentType not support by this adapter');
	}


}
