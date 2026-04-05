<?php
/**
 * Created by PhpStorm.
 * User: Alexeenko Sergey Aleksandrovich
 * Phone: +79231421947
 * Email: sergei_alekseenk@list.ru
 * Company: http://machineheads.ru
 * Date: 11.06.2022
 * Time: 20:24
 */

namespace mheads\filestorage\stores\fileSystem;

use DateTimeImmutable;
use InvalidArgumentException;
use mheads\filestorage\exceptions\AddException;
use mheads\filestorage\File;
use mheads\filestorage\stores\IStore;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;

class FileSystemStore extends Component implements IStore
{
	/** @var string - базовый путь к папке для хранения файлов в публичной папке, доступной из под WEB */
	public string $basePath = '@webroot/upload';

	/** @var string - базовый путь к папке для хранения файлов в приватной папке, не доступной из под WEB */
	public string $basePrivatePath;

	/** @var string - базовый url к файлу */
	public string $baseUrl = '@web/upload';

	/** @var string - Домен сайта используется при генерации URL файла */
	public string $host;

	/** @var bool - Включить протокол https. Используется при генерации URL файла */
	public bool $isHttps = false;

	public function init()
	{
		if(strlen($this->basePath)) $this->basePath = rtrim($this->basePath, '/');
		if(strlen($this->basePrivatePath)) $this->basePrivatePath = rtrim($this->basePrivatePath, '/');
		if(strlen($this->baseUrl)) $this->baseUrl = rtrim($this->baseUrl, '/');
	}

	/**
	 * @throws \yii\base\Exception
	 * @throws InvalidConfigException
	 * @throws AddException
	 */
	public function addFile(File $file): void
	{
		$groupDirName = trim($file->getGroupName(), '/');
		$basePath = $this->obtainBasePath($file->isPrivate());
		$fileName = preg_replace('/[^a-zA-Z0-9-_.\s]+/u', '', Inflector::transliterate($file->getOriginalName()));
		$fileName = preg_replace('/\s/u', '_', $fileName);
		$fileName = static::randomString(6) . '-' . $fileName;

		$directoryPath = self::generateDirectoryPath(
			$groupDirName,
			$fileName,
			$basePath
		);

		if(!empty($directoryPath) && !is_dir($basePath.'/'.$directoryPath))
		{
			FileHelper::createDirectory($basePath.'/'.$directoryPath);
		}

		if(empty($directoryPath) || !$file->getUploadedFile()->saveAs($basePath.'/'.$directoryPath.'/'.$fileName))
		{
			throw new AddException('File save error');
		}

		$file->setRelativePath($directoryPath.'/'.$fileName);
	}

	/**
	 * @throws InvalidConfigException
	 */
	public function removeFile(File $file): void
	{
		$filePath = $this->_getFilePath($file);

		FileHelper::unlink($filePath);

		$directoryPath = dirname($filePath);
		$this->removeEmptyDirectory($directoryPath);
	}

	public function getFileUrl(File $file): ?string
	{
		$url = \Yii::getAlias($this->baseUrl).'/'.ltrim($file->getRelativePath(), '/');
		if($this->host) $url = ($this->isHttps ? 'https':'http').'://'.$this->host.$url;
		return $url;
	}

	/**
	 * @throws InvalidConfigException
	 */
	public function getFileContent(File $file): ?string
	{
		return file_get_contents($this->_getFilePath($file));
	}

	public function getFileResource(File $file)
	{
		return fopen($this->_getFilePath($file), 'r');
	}

	/**
	 * @throws InvalidConfigException
	 */
	protected function _getFilePath(File $file): string
	{
		return FileHelper::normalizePath(
			$this->obtainBasePath($file->isPrivate()).'/'.$file->getRelativePath()
		);
	}

	protected function obtainBasePath(bool $isPrivate): string
	{
		$basePath = !$isPrivate ? $this->basePath:$this->basePrivatePath;
		if(!$basePath)
		{
			throw new InvalidConfigException(
				__CLASS__."::".(!$isPrivate ? 'basePath':'basePrivatePath')." is not configured"
			);
		}

		return \Yii::getAlias($basePath);
	}

	protected static function generateDirectoryPath(
		string $groupDirName,
		string $fileName,
		string $basePath
	): ?string
	{
		$path = $groupDirName.'/'.static::generateTimeBucketPrefix();
		if (file_exists(FileHelper::normalizePath($basePath.'/'.$path.'/'.$fileName)))
		{
			$i = 0;
			do
			{
				$path = $groupDirName.'/'.static::generateTimeBucketPrefix().'/'.static::randomString(4);
				++$i;
				if ($i > 0xFFFF)
				{
					return NULL;
				}
			} while(file_exists(FileHelper::normalizePath($basePath.'/'.$path.'/'.$fileName)));
		}

		return $path;
	}

	protected static function randomString(int $length): string
	{
		$chars = 'abcdef1234567890';
		$n = strlen($chars) - 1;

		$result = '';

		for($i = 0; $i < $length; $i++)
		{
			$result .= $chars[mt_rand(0, $n)];
		}

		return $result;
	}

	/**
	 * Генерирует 3-символьный hex-префикс, привязанный ко времени суток.
	 *
	 * Чтобы файлы, сохраненные в пределах одного временного бакета,
	 * (с высокой долей вероятности) получали один и тот же префикс/подкаталог
	 */
	protected static function generateTimeBucketPrefix(): string
	{
		$prefixTime = new DateTimeImmutable();

		// Секунды с начала суток
		$secondsInDay = $prefixTime->getTimestamp() % 86400;

		// Номер бакета внутри суток (файлы в рамках минуты попадают в один бакет)
		$bucket = intdiv($secondsInDay, 60);

		// Хешируем бакет для более равномерного распределения итоговых значений
		$unsignedHash = (int) sprintf('%u', crc32((string)$bucket));

		// Случайное расщепление (файлы из одного бакета дополнительно "размазываются" на окно в 8 вариантов)
		// для сглаживания случаев, когда большая масса файлов генерится в пределах нескольких секунд (например в крон-задачах)
		$slot = mt_rand(0, 7);

		$prefixValue = ($unsignedHash + $slot) % 1024; //ограничиваем число подкаталогов лимитом в 1024

		return str_pad(dechex($prefixValue), 3, '0', STR_PAD_LEFT);
	}


	protected function removeEmptyDirectory(string $directoryPath): void
	{
		$directoryPath = FileHelper::normalizePath($directoryPath);

		if(!is_dir($directoryPath))
		{
			return;
		}

		if($this->isDirectoryEmpty($directoryPath))
		{
			FileHelper::removeDirectory($directoryPath);
		}
	}

	protected function isDirectoryEmpty(string $path): bool
	{
		if(!is_dir($path))
		{
			return true;
		}

		$iterator = new \FilesystemIterator(
			$path,
			\FilesystemIterator::SKIP_DOTS
		);

		return !$iterator->valid();
	}
}
