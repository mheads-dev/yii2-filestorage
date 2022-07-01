# yii2-filestorage

## Базовая конфигурация

Добавьте миграции в ваше приложение, отредактируйте конфигурационный файл консоли, чтобы настроить миграцию пространства имен:

```php
'controllerMap' => [
    // ...
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationPath' => null,
        'migrationNamespaces' => [
            // ...
            'mheads\filestorage\migrations',
        ],
    ],
],
```

Базовая конфигурация приложения:

```php
const MHEADS_FILE_STORAGE_COMPONENT_NAME = 'fileStorage';
 
$config = [
    //...
    'components' => [
        //...
        MHEADS_FILE_STORAGE_COMPONENT_NAME => [
            'class' => \mheads\filestorage\Storage::class,
            'stores'           => [
                'upload' => [
                    'class'           => \mheads\filestorage\stores\fileSystem\FileSystemStore::class,
                    'baseUrl'         => '/upload',
                    'basePath'        => __DIR__.'/../../upload',
                    'basePrivatePath' => __DIR__.'/../../private_upload',
                    'isHttps'         => true,
                    'host'            => 'static.example.com',
                ],
            ],
            'defaultStoreName' => 'upload',
            'strictRemove'     => false,
        ],
    ]
];
```

## Базовое использование 

Добавление файла:
```php
$uploadedFile = \yii\web\UploadedFile::getInstanceByName('picture');
$file = \mheads\filestorage\File::create(
	$uploadedFile,
	'product-pictures',
);
$file->add();
```

Получение файла:
```php
use mheads\filestorage\File;

$file = File::find()->where([File::field_id => 123])->one();

$url = $file->getUrl();
$content = $file->getContent();
```

Удаление файла:
```php
use mheads\filestorage\File;

$file = File::find()->where([File::field_id => 123])->one();
$file->remove();
```

## Интеграция в Active record через поведение

```php
use mheads\filestorage\File;
use mheads\filestorage\behaviors\FileUploadBehavior;
use \yii\web\UploadedFile;

/**
 * @property ?int $id
 * @property ?string $name
 * @property ?UploadedFile|int $picture_id
 */
class Product extends \yii\db\ActiveRecord
{
    //......
    
    public function setPicture(?UploadedFile $file): void
    {
        $this->setAttribute('picture_id', $file);
    }
    
    public function getPicture()
    {
        return $this->hasOne(File::class, [File::field_id => 'picture_id']);
    }
    
    public function behaviors(): array
    {
        return [
            'pictureUpload' => [
                'class'     => FileUploadBehavior::class,
                'attribute' => 'picture_id',
                'isPrivate' => false,
                'groupName' => 'product-pictures',
            ],
        ];
    }
    
    //......
}
```

## Поддержка нескольких хранилищ

Конфиг:
```php
const MHEADS_FILE_STORAGE_COMPONENT_NAME = 'fileStorage';
 
$config = [
    //...
    'components' => [
        //...
        MHEADS_FILE_STORAGE_COMPONENT_NAME => [
            'class' => \mheads\filestorage\Storage::class,
            'stores'           => [
                'upload' => [
                    'class'           => \mheads\filestorage\stores\fileSystem\FileSystemStore::class,
                    'baseUrl'         => '/upload',
                    'basePath'        => __DIR__.'/../../upload',
                    'basePrivatePath' => __DIR__.'/../../private_upload',
                    'isHttps'         => true,
                    'host'            => 'static.example.com',
                ],
                'upload2' => [
                    'class'           => \mheads\filestorage\stores\fileSystem\FileSystemStore::class,
                    'baseUrl'         => '/upload2',
                    'basePath'        => '/media/store/data/upload',
                    'basePrivatePath' => '/media/store/data/private_upload',
                    'isHttps'         => true,
                    'host'            => 'static2.example.com',
                ],
            ],
            'defaultStoreName' => 'upload',
            'strictRemove'     => false,
        ],
    ],
];
```

Добавление файла с указанием хранилища:
```php
$uploadedFile = \yii\web\UploadedFile::getInstanceByName('picture');
$file = \mheads\filestorage\File::create(
	$uploadedFile,
	'product-pictures',
	'upload2', // store name
);
$file->add();
```

Поведение Active Record с указанием хранилища:
```php
public function behaviors(): array
{
    return [
        'pictureUpload' => [
            'class'     => FileUploadBehavior::class,
            'attribute' => 'picture_id',
            'isPrivate' => false,
            'groupName' => 'product-pictures',
            'storeName' => 'upload2', // store name
        ],
    ];
}
```
