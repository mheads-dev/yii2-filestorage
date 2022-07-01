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

use yii\helpers\FileHelper;

class UploadedFile extends \yii\web\UploadedFile
{
	/** @var bool */
	public $isCreatedByPath = false;

	/**
	 * @param string $filePath
	 * @return static
	 */
	public static function createByPath($filePath)
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

	public function saveAs($file, $deleteTempFile = true)
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
