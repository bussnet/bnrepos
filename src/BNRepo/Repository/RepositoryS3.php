<?php

namespace BNRepo\Repository;


use Aws\S3\S3Client;
use BNRepo\Repository\Adapter\AdapterAmazonS3;
use BNRepo\Repository\Adapter\AdapterAmazonS3Ver2;

class RepositoryS3 extends Repository {

	protected function createAdapter($cfg) {
		if (!isset($cfg['aws_key']) || empty($cfg['aws_key']))
			throw new ParamNotFoundException('param aws_key in S3-repo not set');
		if (!isset($cfg['aws_region']) || empty($cfg['aws_region']))
			throw new ParamNotFoundException('param aws_region in S3-repo not set');
		if (!isset($cfg['aws_secret']) || empty($cfg['aws_secret']))
			throw new ParamNotFoundException('param aws_secret in S3-repo not set');
		if (!isset($cfg['bucket']) || empty($cfg['bucket']))
			throw new ParamNotFoundException('param bucket in S3-repo not set');

		// OPtions für AmazonClient
		$aws_options = array(
			'credentials' => [
				'key' => $cfg['aws_key'],
				'secret' => $cfg['aws_secret'],
			],
			'version' => @$cfg['aws_version'] ?: 'latest',
			'region' => $cfg['aws_region'],
		);
        if (isset($cfg['aws_options']) && is_array($cfg['aws_options']))
			$aws_options = array_merge($cfg['aws_options'], $aws_options);

        // Options für FileSystem
		$fs_options = isset($cfg['options'])? $cfg['options']: array();
		if (isset($cfg['dir']) && !empty($cfg['dir']))
			$fs_options['directory'] = $cfg['dir'];
		if (isset($cfg['create']) && !empty($cfg['create']))
			$fs_options['create'] = $cfg['create'];
		if (isset($cfg['region']) && !empty($cfg['region']))
			$fs_options['region'] = $cfg['region'];
		elseif (isset($cfg['host']) && !empty($cfg['host']))
			$fs_options['region'] = $cfg['host'];
		if (empty($fs_options['region']))
			$fs_options['region'] = $aws_options['region'];
		// if new SDK not exists, switch automatically to old one
		if (!class_exists('\Aws\S3\S3Client'))
			$cfg['use_old_version'] = true;


		if (isset($cfg['use_old_version']) && $cfg['use_old_version'] === true) {
			$service = new \AmazonS3($aws_options);
			return new AdapterAmazonS3($service, $cfg['bucket'], $fs_options);
		} else {
			$service = new S3Client($aws_options);
			return new AdapterAmazonS3Ver2($service, $cfg['bucket'], $fs_options);
		}
	}

}
