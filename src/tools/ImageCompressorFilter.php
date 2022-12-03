<?php
/**
 * Created by PhpStorm.
 * User: Alexeenko Sergey Aleksandrovich
 * Phone: +79231421947
 * Email: sergei_alekseenk@list.ru
 * Company: http://machineheads.ru
 * Date: 11.06.2022
 * Time: 20:19
 */

namespace mheads\filestorage\tools;

use yii\validators\Validator;
use yii\web\UploadedFile;

class ImageCompressorFilter extends Validator
{
	public ?int $maxWidth                   = NULL;
	public ?int $maxHeight                  = NULL;
	public ?int $quality                    = 75;
	public bool $skipNotImages              = true;
	public bool $convertToJpeg              = false;
	public bool $useOriginalIfNotCompressed = false;

	public function validateAttribute($model, $attribute)
	{
		$file = $model->{$attribute};
		if($file instanceof UploadedFile)
		{
			$model->{$attribute} = $this->compress($file);
		}
		elseif(is_array($files = $file))
		{
			foreach($files as $n => $file)
			{
				if($file instanceof UploadedFile)
				{
					$files[$n] = $this->compress($file);
				}
			}
			$model->{$attribute} = $files;
		}
	}

	/**
	 * @return UploadedFile|\mheads\filestorage\tools\UploadedFile
	 */
	private function compress(UploadedFile $file)
	{
		if($this->skipNotImages)
		{
			if(
				!ImageCompressor::validateMimeType($file->type)
				&& !ImageCompressor::validateExtenstion($file->extension)
			)
			{
				return $file;
			}
		}

		$compressor = new ImageCompressor(
			$file->tempName,
			$file->name,
			$file->type !== '*/*' ? $file->type:NULL,
			$file->extension
		);

		$resizedFile = $compressor
			->setMaxWidth($this->maxWidth)
			->setMaxHeight($this->maxHeight)
			->setQuality($this->quality)
			->makeUploadedFile($this->convertToJpeg);

		if($this->useOriginalIfNotCompressed && $resizedFile->size >= $file->size)
		{
			return $file;
		}

		return $resizedFile;
	}
}
