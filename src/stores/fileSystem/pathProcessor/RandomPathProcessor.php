<?php

namespace mheads\filestorage\stores\fileSystem\pathProcessor;

use yii\helpers\FileHelper;

class RandomPathProcessor implements PathProcessorInterface
{

	public static function generateDirectoryPath(
		string $groupDirName,
		string $fileName,
		string $basePath
	): string
	{
		$i = 0;
		do
		{
			$path = $groupDirName.'/'.static::randomString(3);
			++$i;
			if($i > 100000)
			{
				return static::generateDirectoryPath(
					$groupDirName.'/'.static::randomString(3),
					$fileName,
					$basePath
				);
			}
		} while(file_exists(FileHelper::normalizePath($basePath.'/'.$path.'/'.$fileName)));

		return $path;
	}

	protected static function randomString(int $length): string
	{
		$chars = 'qwertyuiopasdfghjklzxcvbnm1234567890';
		$n = strlen($chars) - 1;

		$result = '';

		for($i = 0; $i < $length; $i++)
		{
			$result .= $chars[mt_rand(0, $n)];
		}

		return $result;
	}

}