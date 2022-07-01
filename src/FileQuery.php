<?php
/**
 * Created by PhpStorm.
 * User: Alexeenko Sergey Aleksandrovich
 * Phone: +79231421947
 * Email: sergei_alekseenk@list.ru
 * Company: http://machineheads.ru
 * Date: 11.06.2022
 * Time: 20:33
 */

namespace mheads\filestorage;

use yii\db\ActiveQuery;

class FileQuery extends ActiveQuery
{
	/**
	 * @return File[]|array
	 */
	public function all($db = NULL)
	{
		return parent::all($db);
	}

	/**
	 * @return File|array|null
	 */
	public function one($db = NULL)
	{
		return parent::one($db);
	}
}
