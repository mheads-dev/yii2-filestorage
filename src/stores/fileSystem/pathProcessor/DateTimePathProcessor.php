<?php

namespace mheads\filestorage\stores\fileSystem\pathProcessor;

use DateTimeImmutable;
use mheads\filestorage\exceptions\BaseException;
use yii\base\Component;
use yii\helpers\FileHelper;

class DateTimePathProcessor extends Component implements PathProcessorInterface
{

	public function generateDirectoryPath(
		string $groupDirName,
		string $fileName,
		string $basePath
	): string
	{
		/**
		 * Формируем по умолчанию двухуровневое хранение: группа/(дата-хеш)/(минута-хеш)/файл
		 */
		$path = $groupDirName .'/'. static::generateDayBucketPrefix() .'/'. static::generateTimeBucketPrefix();

		if (file_exists(FileHelper::normalizePath($basePath.'/'.$path.'/'.$fileName)))
		{
			$i = 1;
			do
			{
				$path .= DIRECTORY_SEPARATOR . static::randomHexString($i + 1); //на каждой итерации размерность префикса будет возрастать
				$i++;
				if ($i > 16)
				{
					//по идее сюда никогда попасть не должны
					throw new BaseException("Error generating a unique path");
				}
			} while(file_exists(FileHelper::normalizePath($basePath.'/'.$path.'/'.$fileName)));
		}

		return $path;
	}

	/**
	 * Генерирует 3-символьный hex-префикс, привязанный ко времени суток.
	 *
	 * Чтобы файлы, сохраненные в пределах одного временного бакета,
	 * (с высокой долей вероятности) получали один и тот же префикс/подкаталог
	 */
	protected static function generateTimeBucketPrefix(): string
	{
		$prefixTime = new DateTimeImmutable();

		// Секунды с начала суток
		$secondsInDay = $prefixTime->getTimestamp() % 86400;

		// Номер бакета внутри суток (файлы в рамках минуты попадают в один бакет)
		$bucket = intdiv($secondsInDay, 60);

		// Хешируем бакет для более равномерного распределения итоговых значений
		$unsignedHash = (int) sprintf('%u', crc32((string)$bucket));

		// Случайное расщепление (файлы из одного бакета дополнительно "размазываются" на окно в 8 вариантов)
		// для сглаживания случаев, когда большая масса файлов генерится в пределах нескольких секунд (например в крон-задачах)
		$slot = mt_rand(0, 7);

		$prefixValue = ($unsignedHash + $slot) % 1024; //ограничиваем число подкаталогов лимитом в 1024

		return str_pad(dechex($prefixValue), 3, '0', STR_PAD_LEFT);
	}

	/**
	 * Генерирует 2-символьный hex-префикс, привязанный к номеру дня в году.
	 */
	protected static function generateDayBucketPrefix(): string
	{
		$prefixTime = new DateTimeImmutable();

		// Секунд с условного начала года
		$secondsFromYearBegin = $prefixTime->getTimestamp() % 31536000;

		// Номер бакета внутри года (файлы в рамках одного дня попадают в один бакет)
		$bucket = intdiv($secondsFromYearBegin, 86400);

		// Хешируем бакет для более равномерного распределения итоговых значений
		$unsignedHash = (int) sprintf('%u', crc32((string)$bucket));

		$prefixValue = $unsignedHash % 255; //ограничиваем число подкаталогов первого уровня лимитом в 255

		return str_pad(dechex($prefixValue), 2, '0', STR_PAD_LEFT);
	}

	protected static function randomHexString(int $length): string
	{
		$chars = 'abcdef1234567890';
		$n = strlen($chars) - 1;

		$result = '';

		for($i = 0; $i < $length; $i++)
		{
			$result .= $chars[random_int(0, $n)];
		}

		return $result;
	}
}