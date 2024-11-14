<?php

namespace mheads\filestorage;

class Migration extends \yii\db\Migration
{
	protected function getFileTableName(): string
	{
		return File::tableName();
	}
}