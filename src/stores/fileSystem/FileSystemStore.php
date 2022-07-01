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

		$directoryPath = self::generateDirectoryPath(
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
