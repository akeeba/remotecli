# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Akeeba Remote CLI is a PHP command-line tool for remotely managing backups via the Akeeba Backup JSON API. It supports Akeeba Backup for Joomla, Akeeba Backup for WordPress, and Akeeba Solo. Distributed as a PHAR archive, native binaries (via PHPacker), and Docker image.

## Build Commands

Dependencies install to `remotecli/vendor/` (non-standard path):
```bash
composer install
```

Build the PHAR package (requires Phing and external `buildfiles` repo at `../buildfiles`):
```bash
phing remotecli
```

Build everything (PHAR + native binaries + docs):
```bash
phing all
```

Run the tool locally during development:
```bash
php remotecli/remote.php <command> [options]
```

## Architecture

### Entry Point Flow

`remotecli/remote.php` is the main entry point (not a typical `bin/` script). It:
1. Loads Composer autoloader and version info from `arccli_version.php`
2. Parses CLI input and merges `~/.akeebaremotecli` config file data
3. Sets up CA certificates (extracted to temp file for cURL compatibility with PHAR)
4. Creates a `Dispatcher` with all registered command classes, then dispatches

`remotecli/index.php` is the PHAR stub that bootstraps `remote.php` inside a PHAR context.

### Command Dispatcher Pattern

The `Dispatcher` (`Application/Kernel/Dispatcher.php`) instantiates all command classes, matches the requested command name (from first positional arg or legacy `--action` option) to a class by lowercase class name comparison, then calls `prepare()` followed by `execute()`.

### Command Structure

All commands live in `remotecli/Application/Command/` and extend `AbstractCommand`, which provides:
- `assertConfigured()` — validates `--host` and `--secret` are present
- `getApiObject()` — creates an `Akeeba\BackupJsonApi\Connector` with auto-detection of API version
- `getDownloadOptions()` — builds download config from CLI flags
- Short option aliasing (`-h` → `--host`, `-s` → `--secret`, `-m` → `--machine-readable`)

To add a new command: create a class extending `AbstractCommand` in the Command directory, implement `prepare()` and `execute()`, then register it in the `$dispatcher` array in `remote.php`.

### Input/Output Layers

- **Input** (`Application/Input/`): `Cli` parses `$_SERVER['argv']` into named options and positional arguments. `Filter` provides typed access (`getInt`, `getBool`, `getCmd`, `getPath`, etc.). Config from `~/.akeebaremotecli` (INI format with per-host sections) is merged via `LocalFile`.
- **Output** (`Application/Output/`): Adapter pattern — `Console` for colored terminal output, `Machine` for pipe-friendly structured output. Selected via `--machine-readable` flag.
- **Logging** (`Application/Logger/`): PSR-3 compliant. `OutputLogger` writes to console; `FileLogger` writes to `remotecli_log.txt` when `--debug` is used. `MultiLogger` combines both.

### API Integration

The tool delegates all API communication to the `akeeba/json-backup-api` library (separate repo at `github.com/akeeba/json-backup-api`, included as a Composer `@dev` dependency). The `Connector` class handles API v1 (unencrypted) and v2 (encrypted) with auto-detection.

## Code Conventions

- **Namespace**: `Akeeba\RemoteCLI\` maps to `remotecli/` (PSR-4, see `composer.json`)
- **PHP version**: 8.0+ (platform locked to 8.0.999 in Composer config)
- **Brace style**: Allman (opening brace on its own line for classes, methods, and control structures)
- **Copyright header**: Every PHP file starts with the `@package AkeebaRemoteCLI` docblock
- **No unit tests**: Testing is done via integration testing against live Akeeba Backup installations (PhpStorm run configs in `.run/`)

## Build System Notes

- Build uses **Phing** (`build.xml`) which imports shared build files from `../buildfiles/phing/common.xml`
- Version string is templated: `build/templates/arccli_version.php` → `remotecli/arccli_version.php` (tokens `##VERSION##`, `##DATE##` replaced at build time)
- Native binaries built with PHPacker (configured in `phpacker.json`)
- Build artifacts go to `release/`

## Git Workflow

- **development** branch: active development (default for PRs)
- **main** branch: releases only
