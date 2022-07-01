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

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use mheads\dbfiles\UploadedFile;
use yii\helpers\FileHelper;

class ImageCompressor
{
	private string $filePath;
	private string $fileName;
	private string $mimeType;
	private string $extension;

	private ?int $maxWidth      = NULL;
	private ?int $maxHeight     = NULL;
	private int  $quality       = 75;

	static array $formatToMimeType = [
		'jpeg' => 'image/jpeg',
		'jpg'  => 'image/jpeg',
		'gif'  => 'image/gif',
		'png'  => 'image/png',
		'wbmp' => 'image/vnd.wap.wbmp',
		'xbm'  => 'image/xbm',
		'webp' => 'image/webp',
	];

	public function __construct(
		string $filePath,
		?string $fileName = NULL,
		?string $mimeType = NULL,
		?string $extension = NULL
	)
	{
		$pathInfo = pathinfo($filePath);

		if(!$fileName)
		{
			$fileName = $pathInfo['basename'];
		}

		if(!$mimeType)
		{
			$mimeType = FileHelper::getMimeType($filePath);
			if(!$mimeType)
			{
				throw new \LogicException('Mimetype detect error.');
			}
		}
		if(!$mimeType)
		{
			throw new \LogicException('Mimetype is undefined.');
		}
		elseif(!in_array($mimeType, self::$formatToMimeType))
		{
			throw new \LogicException('Mimetype "'.$mimeType.'" is not supported.');
		}

		if(!$extension)
		{
			$extension = $pathInfo['extension'];
			if($extension && !isset(self::$formatToMimeType[$extension]))
			{
				$extension = NULL;
			}
		}
		if(!$extension)
		{
			$extension = array_search($mimeType, self::$formatToMimeType);
		}
		if(!$extension)
		{
			throw new \LogicException('Extension is undefined.');
		}
		if(!isset(self::$formatToMimeType[$extension]))
		{
			throw new \LogicException('Extension "'.$extension.'" not supported.');
		}

		$this->filePath = $filePath;
		$this->fileName = $fileName;
		$this->mimeType = $mimeType;
		$this->extension = $extension;
	}

	public static function validateMimeType(string $mimeType): bool
	{
		return in_array($mimeType, self::$formatToMimeType);
	}

	public static function validateExtenstion(string $extension): bool
	{
		return isset(self::$formatToMimeType[$extension]);
	}

	public function save($path): self
	{
		$this->saveImage($this->createTransformedImage(), $path);
		return $this;
	}

	public function get(?string $extension = NULL): string
	{
		if($extension === NULL || !isset(self::$formatToMimeType[$extension]))
		{
			$extension = $this->extension;
		}

		return $this->createTransformedImage()->get($extension, ['quality' => $this->quality]);
	}

	public function makeUploadedFile(bool $convertToJpeg = false): UploadedFile
	{
		$file = new UploadedFile();
		$file->isCreatedByPath = true;
		$file->name = $this->fileName;
		$file->tempName = tempnam(sys_get_temp_dir(), 'mheads');
		$file->type = $this->mimeType;

		$extension = $file->extension;

		if(!in_array($extension, ['jpg', 'jpeg']) && $convertToJpeg)
		{
			$file->name = pathinfo($file->name, PATHINFO_FILENAME).'.jpeg';
			$file->type = '*/*';
			$extension = 'jpeg';
		}

		$this->saveImage($this->createTransformedImage(), $file->tempName, $extension);
		$file->size = filesize($file->tempName);

		return $file;
	}

	private function saveImage(ImageInterface $image, $path, ?string $extension = NULL): void
	{
		$extension = $extension ?:pathinfo($path, PATHINFO_EXTENSION);
		if(!isset(self::$formatToMimeType[$extension]))
		{
			$extension = $this->extension;
		}

		$image->save($path, ['format' => $extension, 'quality' => $this->quality]);
	}

	public function createTransformedImage(): ImageInterface
	{
		$imagine = \Yii::$container->get(ImagineInterface::class);
		$image = $imagine->open($this->filePath);

		if($this->maxWidth === NULL && $this->maxHeight === NULL)
		{
			return $image;
		}

		$size = $image->getSize();
		$scale = 1;

		if($this->maxWidth !== NULL && $size->getWidth() > $this->maxWidth)
		{
			$scale = min($scale, $this->maxWidth / $size->getWidth());
		}

		if($this->maxHeight !== NULL && $size->getHeight() > $this->maxHeight)
		{
			$scale = min($scale, $this->maxHeight / $size->getHeight());
		}

		if($scale < 1)
		{
			$image->resize($size->scale($scale));
		}

		return $image;
	}

	public function getMaxWidth(): ?int
	{
		return $this->maxWidth;
	}

	public function setMaxWidth(?int $maxWidth): self
	{
		$this->maxWidth = $maxWidth;
		return $this;
	}

	public function getMaxHeight(): ?int
	{
		return $this->maxHeight;
	}

	public function setMaxHeight(?int $maxHeight): self
	{
		$this->maxHeight = $maxHeight;
		return $this;
	}

	public function getQuality(): int
	{
		return $this->quality;
	}

	public function setQuality(int $quality): self
	{
		$this->quality = $quality;
		return $this;
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}

	public function getFileName()
	{
		return $this->fileName;
	}

	public function getMimeType(): string
	{
		return $this->mimeType;
	}

	public function getExtension()
	{
		return $this->extension;
	}
}
