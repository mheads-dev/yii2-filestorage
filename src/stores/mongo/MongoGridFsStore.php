<?php
/**
 * Created by PhpStorm.
 * User: Alexeenko Sergey Aleksandrovich
 * Phone: +79231421947
 * Email: sergei_alekseenk@list.ru
 * Company: http://machineheads.ru
 * Date: 29.08.2022
 * Time: 16:19
 */

namespace mheads\filestorage\stores\mongo;

use mheads\filestorage\exceptions\AddException;
use mheads\filestorage\exceptions\RemoveException;
use mheads\filestorage\File;
use mheads\filestorage\stores\IStore;
use yii\base\Component;
use yii\di\Instance;
use yii\mongodb\Connection;

class MongoGridFsStore extends Component implements IStore
{
	/** @var Connection|array|string */
	public $connection;

	public function init()
	{
		$this->connection = Instance::ensure($this->connection, Connection::class);
	}

	/**
	 * @throws \yii\mongodb\Exception
	 */
	public function addFile(File $file): void
	{
		$upload = $this->connection
			->getFileCollection()
			->createUpload()
			->addFile($file->getUploadedFile()->tempName);

		$upload->filename = $file->getOriginalName();
		$document = $upload->complete();
		if(!empty($document['_id']))
		{
			$file->setExternalId((string)$document['_id']);
			return;
		}
		throw new AddException('Save file error.');
	}

	/**
	 * @throws \yii\mongodb\Exception
	 */
	public function removeFile(File $file): void
	{
		$this->connection->getFileCollection()->delete($file->getExternalId());
	}

	public function getFileUrl(File $file): ?string
	{
		return NULL;
	}

	public function getFileContent(File $file): ?string
	{
		$download = $this->connection->getFileCollection()->get($file->getExternalId());
		if($download)
		{
			return $download->toString();
		}
		return NULL;
	}

	/**
	 * @param File $file
	 * @return resource|null
	 * @throws \yii\mongodb\Exception
	 */
	public function getFileResource(File $file)
	{
		$download = $this->connection->getFileCollection()->get($file->getExternalId());
		if($download)
		{
			return $download->toResource();
		}
		return NULL;
	}
}
