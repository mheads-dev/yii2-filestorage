<?php
/**
 * Created by PhpStorm.
 * User: Alexeenko Sergey Aleksandrovich
 * Phone: +79231421947
 * Email: sergei_alekseenk@list.ru
 * Company: http://machineheads.ru
 * Date: 11.06.2022
 * Time: 20:25
 */

namespace mheads\filestorage;

use mheads\filestorage\stores\IStore;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class File extends ActiveRecord
{
	const field_id            = 'id';
	const field_store_name    = 'store_name';
	const field_external_id   = 'external_id';
	const field_group_name    = 'group_name';
	const field_is_private    = 'is_private';
	const field_relative_path = 'relative_path';
	const field_original_name = 'original_name';
	const field_height        = 'height';
	const field_width         = 'width';
	const field_file_size     = 'file_size';
	const field_content_type  = 'content_type';
	const field_description   = 'description';
	const field_updated_at    = 'updated_at';
	const field_created_at    = 'created_at';

	protected ?UploadedFile $uploadedFile = NULL;

	public static function create(
		UploadedFile $uploadedFile,
		?string $groupName = NULL,
		?string $storeName = NULL
	): self
	{
		$storage = static::getStorage();

		$file = new static();
		$file->setStoreName($storeName ?? $storage->defaultStoreName);
		$file->setGroupName($groupName ?? $storage->defaultGroupName);
		$file->setIsPrivate(false);

		$file->uploadedFile = $uploadedFile;
		$file->fillFileAttributes($uploadedFile);

		return $file;
	}

	/**
	 * @throws exceptions\RemoveException
	 * @throws \yii\base\InvalidConfigException
	 * @throws exceptions\AddException
	 */
	public function add(): void
	{
		static::getStorage()->add($this);
	}

	/**
	 * @throws exceptions\RemoveException
	 * @throws \yii\base\InvalidConfigException
	 */
	public function remove(): void
	{
		static::getStorage()->remove($this);
	}

	/**
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getUrl(): ?string
	{
		if($this->isPrivate()) return NULL;
		return $this->getStore()->getFileUrl($this);
	}

	/**
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getContent(): ?string
	{
		return $this->getStore()->getFileContent($this);
	}

	/**
	 * @throws \yii\base\InvalidConfigException
	 * @return resource|null
	 */
	public function getResource()
	{
		return $this->getStore()->getFileResource($this);
	}

	public function getUploadedFile(): ?UploadedFile
	{
		return $this->uploadedFile;
	}

	protected function fillFileAttributes(UploadedFile $file): void
	{
		$this->setOriginalName($file->name);
		$this->setContentType(FileHelper::getMimeType($file->tempName));
		$this->setFileSize($file->size);

		$imageInfo = @getimagesize($file->tempName);
		if($imageInfo)
		{
			[$width, $height] = $imageInfo;

			$this->setWidth((int)$width);
			$this->setHeight((int)$height);
		}
		else
		{
			$this->setWidth(NULL);
			$this->setHeight(NULL);
		}
	}

	public function getId(): ?int
	{
		return $this->getAttribute(self::field_id);
	}

	public function getStoreName(): string
	{
		return $this->getAttribute(self::field_store_name);
	}

	public function setStoreName(string $value): void
	{
		$this->setAttribute(self::field_store_name, $value);
	}

	public function getExternalId(): ?string
	{
		return $this->getAttribute(self::field_external_id);
	}

	public function setExternalId(?string $value): void
	{
		$this->setAttribute(self::field_external_id, $value);
	}

	public function getGroupName(): string
	{
		return $this->getAttribute(self::field_group_name);
	}

	public function setGroupName(string $value): void
	{
		$this->setAttribute(self::field_group_name, $value);
	}

	public function isPrivate(): bool
	{
		return (bool)$this->getAttribute(self::field_is_private);
	}

	public function setIsPrivate(bool $value): void
	{
		$this->setAttribute(self::field_is_private, $value ? 1:0);
	}

	public function getRelativePath(): ?string
	{
		return $this->getAttribute(self::field_relative_path);
	}

	public function setRelativePath(?string $value): void
	{
		$this->setAttribute(self::field_relative_path, $value);
	}

	public function getOriginalName(): string
	{
		return $this->getAttribute(self::field_original_name);
	}

	public function setOriginalName(string $value): void
	{
		$value = str_replace(["\n\r", "\n", "\r"], " ", $value);
		$this->setAttribute(self::field_original_name, $value);
	}

	public function getHeight(): ?int
	{
		return $this->getAttribute(self::field_height);
	}

	public function setHeight(?int $value): void
	{
		$this->setAttribute(self::field_height, $value);
	}

	public function getWidth(): ?int
	{
		return $this->getAttribute(self::field_width);
	}

	public function setWidth(?int $value): void
	{
		$this->setAttribute(self::field_width, $value);
	}

	public function getFileSize(): ?int
	{
		return $this->getAttribute(self::field_file_size);
	}

	public function setFileSize(?int $value): void
	{
		$this->setAttribute(self::field_file_size, $value);
	}

	public function getContentType(): ?string
	{
		return $this->getAttribute(self::field_content_type);
	}

	public function setContentType(?string $value): void
	{
		$this->setAttribute(self::field_content_type, $value);
	}

	public function getDescription(): ?string
	{
		return $this->getAttribute(self::field_description);
	}

	public function setDescription(?string $value): void
	{
		$this->setAttribute(self::field_description, $value);
	}

	public function getUpdatedAt(): ?int
	{
		return $this->getAttribute(self::field_updated_at);
	}

	public function getCreatedAt(): ?int
	{
		return $this->getAttribute(self::field_created_at);
	}

	/**
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getStore(): IStore
	{
		return static::getStorage()->getStore($this->getStoreName());
	}

	public static function tableName(): string
	{
		return static::getStorage()->fileTableName;
	}

	public static function getDb()
	{
		return static::getStorage()->db;
	}

	public static function getStorage(): Storage
	{
		return \Yii::$app->get(MHEADS_FILE_STORAGE_COMPONENT_NAME);
	}

	public static function find(): FileQuery
	{
		return new FileQuery(get_called_class());
	}

	public function behaviors()
	{
		return [
			'timestamp' => [
				'class' => TimestampBehavior::class,
			],
		];
	}
}
