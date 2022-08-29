<?php

namespace mheads\filestorage\migrations;

use yii\db\Migration;

class M220829060403Modify extends Migration
{
	public $tableName = '{{%file}}';
	public $tableOptions;

	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->alterColumn(
			$this->tableName,
			'external_id',
			$this->string(1000)->null()
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->alterColumn(
			$this->tableName,
			'external_id',
			$this->string(255)->null()
		);
	}
}
