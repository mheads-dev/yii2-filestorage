<?php

namespace mheads\filestorage\stores\fileSystem\pathProcessor;

interface PathProcessorInterface
{
	public static function generateDirectoryPath(
		string $groupDirName,
		string $fileName,
		string $basePath
	): string;
}