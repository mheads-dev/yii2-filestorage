<?php
/**
 * Created by PhpStorm.
 * User: Alexeenko Sergey Aleksandrovich
 * Phone: +79231421947
 * Email: sergei_alekseenk@list.ru
 * Company: http://machineheads.ru
 * Date: 12.06.2022
 * Time: 13:04
 */

namespace mheads\filestorage\behaviors;

use mheads\filestorage\File;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\web\UploadedFile;

class FileUploadBehavior extends Behavior
{
	public string $attribute = '';

	/** @var string - Если не указать, то будет подставлено значение из $attribute */
	public string $targetAttribute = '';

	/** @var ?string - Если не указать, то будет подставлено значение из \alse\filestorage\Storage::$defaultStoreName */
	public ?string $storeName = NULL;

	/** @var ?string - Если не указать, то будет подставлено значение из \alse\filestorage\Storage::$defaultGroupName */
	public ?string $groupName = NULL;

	public bool $isPrivate = false;

	/** @var string|File - classname */
	public string $fileClass = File::class;

	public bool $afterDelete = true;

	/**
	 * @var Model|BaseActiveRecord the owner of this behavior.
	 */
	public $owner;

	/** @var File[] */
	private array $addedFiles = [];

	public function init()
	{
		if(empty($this->attribute))
		{
			throw new InvalidConfigException(
				'\common\entities\behaviors\FileUploadBehavior::$attribute cannot be empty.'
			);
		}

		if(empty($this->targetAttribute))
		{
			$this->targetAttribute = $this->attribute;
		}

		register_shutdown_function([$this, 'cleaning']);
	}

	public function events()
	{
		return [
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
			ActiveRecord::EVENT_AFTER_INSERT  => 'afterSave',
			ActiveRecord::EVENT_AFTER_UPDATE  => 'afterSave',
			ActiveRecord::EVENT_BEFORE_DELETE => 'afterDelete',
		];
	}

	/**
	 * @throws InvalidConfigException
	 * @throws \mheads\filestorage\exceptions\AddException
	 */
	public function beforeSave(): void
	{
		if($this->owner->getAttribute($this->attribute) instanceof UploadedFile)
		{
			$file = $this->fileClass::create(
				$this->owner->getAttribute($this->attribute),
				$this->groupName,
				$this->storeName,
			);
			$file->setIsPrivate($this->isPrivate);
			$file->add();
			$this->addedFiles[] = $file;
			$this->owner->setAttribute($this->targetAttribute, $file->getId());
		}
	}

	public function afterSave(AfterSaveEvent $event): void
	{
		$this->addedFiles = [];
		if(
			isset($event->changedAttributes[$this->targetAttribute])
			&& !empty($fileId = $event->changedAttributes[$this->targetAttribute])
			&& is_numeric($fileId)
		)
		{
			$file = $this->fileClass::findOne($fileId);
			if($file) $file->remove();
		}
	}

	public function afterDelete(): void
	{
		if(!$this->afterDelete) return;
		if(!empty($fileId = $this->owner->getAttribute($this->targetAttribute)) && is_numeric($fileId))
		{
			$file = $this->fileClass::findOne($fileId);
			if($file) $file->remove();
		}
	}

	public function cleaning()
	{
		if($this->addedFiles)
		{
			foreach($this->addedFiles as $file)
			{
				$file->remove();
			}
		}
	}
}
