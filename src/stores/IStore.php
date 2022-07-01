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

namespace mheads\filestorage\stores;

use mheads\filestorage\exceptions\AddException;
use mheads\filestorage\exceptions\RemoveException;
use mheads\filestorage\File;

interface IStore
{
	/**
	 * @throws AddException
	 */
	public function addFile(File $file): void;

	/**
	 * @throws RemoveException
	 */
	public function removeFile(File $file): void;

	public function getFileUrl(File $file): ?string;

	public function getFileContent(File $file): ?string;
}
