<?php

namespace mheads\filestorage\migrations;

use mheads\filestorage\Migration;

class M220829060403Modify extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->alterColumn(
			$this->getFileTableName(),
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
			$this->getFileTableName(),
			'external_id',
			$this->string(255)->null()
		);
	}
}
