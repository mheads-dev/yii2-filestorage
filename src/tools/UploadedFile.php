<?php
/**
 * Created by PhpStorm.
 * User: Alexeenko Sergey Aleksandrovich
 * Email: sergei_alekseenk@list.ru
 * Company: http://machineheads.ru
 * @see https://github.com/mheads-dev/yii2-dbfiles
 * Date: 11.06.2022
 * Time: 20:19
 */

namespace mheads\filestorage\tools;

use mheads\filestorage\File;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

class UploadedFile extends \yii\web\UploadedFile
{
	/** @var bool */
	public $isCreatedByPath = false;

	/**
	 * @param File $fromFile
	 * @return static
	 * @throws InvalidConfigException
	 */
	public static function createByFile(File $fromFile): UploadedFile
	{
		return static::createByContent(
			$fromFile->getContent(),
			$fromFile->getOriginalName()
		);
	}

	/**
	 * @param string $content
	 * @param string $name
	 * @return static
	 * @throws InvalidConfigException
	 */
	public static function createByContent(string $content, string $name): UploadedFile
	{
		$file = new static();
		$file->name = $name;
		$file->tempName = tempnam(sys_get_temp_dir(), 'yii');
		file_put_contents($file->tempName, $content);
		$file->type = FileHelper::getMimeType($file->tempName);
		$file->size = filesize($file->tempName);
		$file->isCreatedByPath = true;

		return $file;
	}

	/**
	 * @param string $filePath
	 * @return static
	 * @throws InvalidConfigException
	 */
	public static function createByPath(string $filePath): UploadedFile
	{
		$file = new static();
		$file->name = pathinfo($filePath, PATHINFO_BASENAME);
		$file->tempName = tempnam(sys_get_temp_dir(), 'yii');
		file_put_contents($file->tempName, file_get_contents($filePath));
		$file->type = FileHelper::getMimeType($file->tempName);
		$file->size = filesize($file->tempName);
		$file->isCreatedByPath = true;

		return $file;
	}

	public function saveAs($file, $deleteTempFile = true): bool
	{
		if($this->isCreatedByPath)
		{
			if(copy($this->tempName, $file))
			{
				if($deleteTempFile) @unlink($this->tempName);
				return true;
			}
		}
		else
		{
			return parent::saveAs($file, $deleteTempFile);
		}

		return false;
	}

	public function __destruct()
	{
		if($this->isCreatedByPath)
		{
			@unlink($this->tempName);
		}
	}
}
