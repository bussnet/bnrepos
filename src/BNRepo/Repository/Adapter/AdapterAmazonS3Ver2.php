<?php

namespace BNRepo\Repository\Adapter;


use Aws\S3\S3Client;
use Gaufrette\Adapter\AmazonS3;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\UnexpectedFile;
use Gaufrette\Adapter as GaufretteAdapter;
use Aws\Result;

class AdapterAmazonS3Ver2 extends AmazonS3 implements GaufretteAdapter, UrlAware {

	/**
	 * @var S3Client
	 */
    protected $service;
    protected $bucket;
    protected $ensureBucket = false;
    protected $metadata;
    protected $options;

    public function __construct(S3Client $service, $bucket, $options = array()) {
        $this->service = $service;
        $this->bucket = $bucket;
        $this->options = array_replace_recursive(
            array('directory' => '', 'create' => false, 'default_acl' => 'bucket-owner-full-control'),
            $options
        );
        // Set Directory Explicit, cause validation/correction rules
        $this->setDirectory($options['directory']);
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
		return $this->write($targetKey, fopen($localFile, 'r'));
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
        try {
            $this->getObject($sourceKey, array(
                'SaveAs' => $localTargetFile
            ));
        } catch (\Exception $e) {
            return false;
        }
        return true;
	}


	/**
	 * Retrieve the S3 object URL for the given key.
	 *
	 * @param string $key
	 * @param integer|string $validTime seconds or strtotime() string | 0=> endless
	 * @param array $opt Look at \AmazonS3::get_object_url() docs
	 *
	 * @see \AmazonS3::get_object_url()
	 *
	 * @return string The S3 object URL
	 */
	public function getUrl($key, $validTime = 0, $options = array()) {
		// for CloudFront or static Domain, set download_url in Repositories.yml
		if (array_key_exists('download_url', $options)) {
			return sprintf($options['download_url'], $key);
		}

		// Public Access, or Signed URL
		if (in_array($this->options['default_acl'], array('public-read', 'public-read-write'))) {
			return $this->service->getObjectUrl($this->bucket, $this->computePath($key));
		} else {

			$cmd = $this->service->getCommand('GetObject', [
				'Bucket' => $this->bucket,
				'Key' => $this->computePath($key)
			]);

			if (empty($validTime))
				$validTime = '+ 1 week'; // max expire time
			elseif (is_numeric($validTime))
				$validTime = '+' . $validTime . ' seconds';

			$request = $this->service->createPresignedRequest($cmd, $validTime);

			// Get the actual presigned-url
			return (string)$request->getUri();
		}
	}

	/**
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

        if ($this->service->doesBucketExist($this->bucket)) {
            $this->ensureBucket = true;
            return;
        }

        if (!$this->options['create']) {
            throw new \RuntimeException(sprintf(
                'The configured bucket "%s" does not exist.',
                $this->bucket
            ));
        }

        try {
            /** @var $result Result */
            $result = $this->service->createBucket(array(
                'Bucket' => $this->bucket,
                'ACL' => $this->options['default_acl'],
	            'CreateBucketConfiguration' => [
                    'LocationConstraint' => $this->options['region']
	            ]
            ));
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'Failed to create the configured bucket "%s".',
                $this->bucket
            ), 0, $e);
        }

        $this->ensureBucket = true;
    }

    /**
     * {@inheritDoc}
     * @return Result
     */
	public function getKeyIterator($prefix = null) {
		$this->ensureBucketExists();

		// add slash to beginning and remove from end
		$prefix = rtrim(preg_replace('/^[\/]*([^\/].*)[\/]?$/', '$1', $prefix), '/');

		return $this->service->getIterator('ListObjects', array(
            'Bucket' => $this->bucket,
            'Prefix' => $this->getDirectory().$prefix
        ));
    }

    /**
     * Add Directory-Prefix and remove fullPath and emptyDirLine from output for similar response to local/ftp etc
     * {@inheritDoc}
     */
	public function keysWithPrefix($prefix = null, $withDirectories = false) {
		$this->ensureBucketExists();

		// add slash to beginning and remove from end
		$prefix = preg_replace('/^[\/]*([^\/].*)[\/]?$/', '/$1', $prefix);

		$iterator = $this->getKeyIterator($prefix);

        $keys = array();
        $paths = array();
		$prefix_dir = rtrim(substr($prefix, -1) != '/'?dirname($prefix):$prefix, '/');
		$dirLength = ltrim(strlen($this->getDirectory() . $prefix_dir), '/'); // remove the starting slash
		foreach ($iterator as $item) {
			$file = substr($item['Key'], $dirLength);
			if (!$file) continue;
			$dir = dirname($file);
			// Directory
			if (strlen($dir) > 0 && $dir != '.')
				$paths[$dir] = true;
			// File
			$keys[] = $file;
		}
		if ($withDirectories)
			$keys = array_merge(array_keys($paths), $keys);
		sort($keys);

        return $keys;
    }


    /**
     * OVERWRITE CAUSE NO CHANGABLE PUBLIC_ACL
     * {@inheritDoc}
     */
    public function write($key, $content) {
        $this->ensureBucketExists();

        $options = array_replace_recursive(
            array('ACL' => $this->options['default_acl']),
            $this->getMetadata($key),
            array(
                'Body' => $content,
                'Bucket' => $this->bucket,
                'Key' => $this->computePath($key),
            )
        );

        try {
            /** @var $response Result */
            $response = $this->service->putObject($options);
        } catch (\Exception $e) {
            return false;
        }

        if (is_string($content))
            return mb_strlen($content);
        return true;
    }

    // *********************

    /**
     * Set the base directory the user will have access to
     *
     * @param string $directory
     */
    public function setDirectory($directory) {
        // strip double slashes and secure that the dir not start and ends with an slash
        $this->options['directory'] = substr(preg_replace('~/+~', '/', '/' . $directory . '/'), 1, -1);
    }

    /**
     * Get the directory the user has access to
     *
     * @return string
     */
    public function getDirectory() {
        return $this->options['directory'];
    }

    /**
     * {@inheritDoc}
     */
    public function read($key) {
        $this->ensureBucketExists();

        try {
            /** @var $response Result */
            $response = $this->getObject($key);
        } catch (\Exception $e) {
            return false;
        }

        return $response['Body'];
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey) {
        $this->ensureBucketExists();

        try {
            /** @var $response Result */
            $response = $this->service->copyObject(array(
                'Bucket' => $this->bucket,
                'Key' => $this->computePath($targetKey),
                'CopySource' => urlencode($this->bucket.'/'.$this->computePath($sourceKey)),
            ));
        } catch (\Exception $e) {
            return false;
        }

        $this->delete($sourceKey);
        return true;
    }


    /**
     * {@inheritDoc}
     */
    public function exists($key) {
        $this->ensureBucketExists();

        return $this->service->doesObjectExist(
            $this->bucket,
            $this->computePath($key)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key) {
        $this->ensureBucketExists();
	    $obj = $this->getObject($key);
        return strtotime($obj['LastModified']);
    }


    /**
     * {@inheritDoc}
     */
    public function delete($key) {
        $this->ensureBucketExists();

        try {
            /** @var $response Result */
            $response = $this->service->deleteObject(array(
                'Bucket' => $this->bucket,
                'Key' => $this->computePath($key)
            ));
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isDirectory($key) {
        // Check is not good, but the only solution
        // If the $key is from the keys() function and not exists, its an Directory
        return !$this->service->doesObjectExist(
            $this->bucket,
            $this->computePath($key)
        );
    }

    /**
     * @param $key
     * @param array $options
     * @return Result
     */
    public function getObject($key, $options=array()) {
        $options = array_merge($options, array(
            'Bucket' => $this->bucket,
            'Key' => $this->computePath($key)
        ));
        return $this->service->getObject($options);
    }

	/**
	 * Returns the MimeType of the given Key
	 * @param $key
	 * @return mixed
	 */
	public function getContentType($key) {
		$obj = $this->getObject($key);
		return $obj['ContentType'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function append($key, $content) {
		return $this->write($key, $this->read($key).$content);
	}

}
