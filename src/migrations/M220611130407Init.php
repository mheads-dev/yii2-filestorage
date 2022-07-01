<?php

namespace mheads\filestorage\migrations;

use yii\db\Migration;

class M220611130407Init extends Migration
{
	public $tableName = '{{%file}}';
	public $tableOptions;

	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->createTable($this->tableName, [
			'id'            => $this->primaryKey(18),
			'store_name'    => $this->string(255)->null(),
			'external_id'   => $this->string(255)->null(),
			'group_name'    => $this->string(50)->null(),
			'is_private'    => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Is private file'),
			'relative_path' => $this->string(1000)->null(),
			'original_name' => $this->string(255)->null(),
			'height'        => $this->integer(18)->null(),
			'width'         => $this->integer(18)->null(),
			'file_size'     => $this->bigInteger(20)->null(),
			'content_type'  => $this->string(255)->null(),
			'description'   => $this->string(255)->null(),
			'updated_at'    => $this->integer(11)->null(),
			'created_at'    => $this->integer(11)->null(),
		], $this->tableOptions);

		$this->createIndex('store_name', $this->tableName, 'store_name');
		$this->createIndex('group_name', $this->tableName, 'group_name');
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropTable($this->tableName);
	}
}
