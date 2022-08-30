<?php
/**
 * Created by PhpStorm.
 * User: Alexeenko Sergey Aleksandrovich
 * Phone: +79231421947
 * Email: sergei_alekseenk@list.ru
 * Company: http://machineheads.ru
 * Date: 12.06.2022
 * Time: 0:26
 */

namespace mheads\filestorage\stores\dummy;

use mheads\filestorage\File;
use mheads\filestorage\stores\IStore;
use yii\base\Component;

class DummyStore extends Component implements IStore
{
	public function addFile(File $file): void
	{
		$file->setRelativePath(\Yii::$app->security->generateRandomString(5).'/'.$file->getOriginalName());
		$file->setExternalId(\Yii::$app->security->generateRandomString(5));
	}

	public function removeFile(File $file): void
	{
		// TODO: Implement removeFile() method.
	}

	public function getFileUrl(File $file): ?string
	{
		return $file->getRelativePath();
	}

	public function getFileContent(File $file): ?string
	{
		return print_r($file->toArray(), 1);
	}

	public function getFileResource(File $file)
	{
		return NULL;
	}
}
