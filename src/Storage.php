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

namespace mheads\filestorage;

use mheads\filestorage\exceptions\AddException;
use mheads\filestorage\exceptions\RemoveException;
use mheads\filestorage\stores\IStore;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\di\Instance;
use function Symfony\Component\String\u;

class Storage extends Component
{
	/**
	 * @var Connection|array|string
	 */
	public $db = 'db';

	/**
	 * @var string table name
	 */
	public $fileTableName = '{{%file}}';

	/**
	 * @var IStore[]|array[]|string[] - index by store name
	 */
	public array $stores = [];

	public string $defaultStoreName = 'default';
	public string $defaultGroupName = 'common';

	public bool $strictRemove = false;

	public function init()
	{
		$this->db = Instance::ensure($this->db, Connection::class);
		$this->defaultGroupName = trim($this->defaultGroupName, '/');
	}

	/**
	 * @throws InvalidConfigException
	 * @throws AddException
	 */
	public function add(File $file): void
	{
		if(!$file->getIsNewRecord())
		{
			throw new AddException('The file must be new record');
		}

		$store = $file->getStore();
		$store->addFile($file);

		try
		{
			$saved = $file->save();
		}
		catch(\Exception|\Throwable $e)
		{
			try
			{
				$store->removeFile($file);
			}
			catch(RemoveException $e){}

			throw $e;
		}

		if(!$saved)
		{
			try
			{
				$store->removeFile($file);
			}
			catch(RemoveException $e){}

			throw new AddException('Add record error.');
		}
	}

	/**
	 * @throws InvalidConfigException
	 * @throws RemoveException
	 */
	public function remove(File $file): void
	{
		try
		{
			$store = $file->getStore();
		}
		catch(\Exception|\Throwable $e)
		{
			if($this->strictRemove) throw $e;
		}

		$file::getDb()->transaction(function () use ($file, $store) {
			if($file->delete())
			{
				try
				{
					$store->removeFile($file);
				}
				catch(\Exception|\Throwable $e)
				{
					if($this->strictRemove) throw $e;
				}
			}
			else
			{
				if($this->strictRemove)
				{
					throw new RemoveException('Remove record error.');
				}
			}
		});
	}

	public function getStore(string $name): IStore
	{
		if(empty($store = $this->stores[$name]))
		{
			throw new InvalidConfigException(sprintf('Store «%s» is not defined', $name));
		}

		if(!($store instanceof IStore) && (is_array($store) || is_string($store)))
		{
			$store = \Yii::createObject($store);
			$this->stores[$name] = $store;
		}

		if(!$store instanceof IStore)
		{
			throw new InvalidConfigException(sprintf('Store «%s» is not an %s', $name, IStore::class));
		}

		return $store;
	}
}
