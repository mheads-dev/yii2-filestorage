# Changelog

Все заметные изменения в проекте будут документироваться в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
а проект следует [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.9] - 2026-04-29

### Added
- Добавлен `PathProcessorInterface` для настраиваемой генерации путей в `FileSystemStore`.
- Добавлен `RandomPathProcessor` как стратегия случайной генерации путей по умолчанию.
- Добавлен `DateTimePathProcessor` с генерацией путей на основе временных бакетов и защитой от коллизий.

### Changed
- Обновлён `FileSystemStore`: добавлена поддержка настраиваемого `pathProcessor` (`string|array|PathProcessorInterface`).
- Генерация пути директории в `FileSystemStore::addFile()` переведена на делегирование в процессор путей.
- Добавлено DI-разрешение процессора путей через `yii\di\Instance::ensure()`.
- В `composer.json` добавлено ограничение версии PHP: `^8.0`.

### Fixed
- Добавлена очистка пустых директорий после удаления файла в `FileSystemStore::removeFile()`.
- Добавлены проверки границ при очистке директорий, чтобы исключить удаление путей вне корня группы.
