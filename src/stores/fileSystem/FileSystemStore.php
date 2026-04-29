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

use mheads\filestorage\exceptions\AddException;
use mheads\filestorage\File;
use mheads\filestorage\stores\fileSystem\pathProcessor\PathProcessorInterface;
use mheads\filestorage\stores\fileSystem\pathProcessor\RandomPathProcessor;
use mheads\filestorage\stores\IStore;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\di\Instance;
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

	/** @var string|array|PathProcessorInterface path-процессор, который будет использоваться в хранилище */
	public string|array|PathProcessorInterface $pathProcessor = RandomPathProcessor::class;

	/**
	 * @throws InvalidConfigException
	 */
	public function init(): void
	{
		if(strlen($this->basePath)) $this->basePath = rtrim($this->basePath, '/');
		if(strlen($this->basePrivatePath)) $this->basePrivatePath = rtrim($this->basePrivatePath, '/');
		if(strlen($this->baseUrl)) $this->baseUrl = rtrim($this->baseUrl, '/');

		$this->pathProcessor = Instance::ensure($this->pathProcessor,	PathProcessorInterface::class);
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

		$directoryPath = $this->generateDirectoryPath(
			$groupDirName,
			$fileName,
			$basePath
		);

		if(!is_dir($basePath.'/'.$directoryPath))
		{
			FileHelper::createDirectory($basePath.'/'.$directoryPath);
		}

		if(!$file->getUploadedFile()->saveAs($basePath.'/'.$directoryPath.'/'.$fileName))
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

		//очистка каталогов, если после удаления они опустели
		$groupDirName = trim($file->getGroupName(), '/');
		$this->cleanPath(
			$this->obtainBasePath($file->isPrivate()).'/'.$groupDirName,
			dirname($filePath)
		);
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

	protected function generateDirectoryPath(
		string $groupDirName,
		string $fileName,
		string $basePath
	): string
	{
		return $this->pathProcessor->generateDirectoryPath($groupDirName, $fileName, $basePath);
	}

	/**
	 * Удаляет пустые подкаталоги, поднимаясь от $path до $rootDir
	 * $roorDir не удаляется, даже если пуста
	 * @param string $rootDir корневой путь
	 * @param string $path дочерний путь
	 * @return void
	 */
	protected function cleanPath(string $rootDir, string $path): void
	{
		$rootDir = rtrim(FileHelper::normalizePath($rootDir), DIRECTORY_SEPARATOR);
		$path = rtrim(FileHelper::normalizePath($path), DIRECTORY_SEPARATOR);

		if($path === $rootDir) //дошли до корневого пути
		{
			return;
		}

		//проверка что корневой путь - строго часть дочернего
		if(strpos($path . DIRECTORY_SEPARATOR, $rootDir . DIRECTORY_SEPARATOR) !== 0)
		{
			return;
		}

		while($path !== $rootDir)
		{
			if(!is_dir($path))
			{
				$path = dirname($path);
				continue;
			}

			if(!$this->isDirectoryEmpty($path))
			{
				break;
			}

			FileHelper::removeDirectory($path);
			$path = dirname($path);
		}
	}

	protected function isDirectoryEmpty(string $path): bool
	{
		if(!is_dir($path))
		{
			return false;
		}

		$iterator = new \FilesystemIterator(
			$path,
			\FilesystemIterator::SKIP_DOTS
		);

		return !$iterator->valid();
	}
}
