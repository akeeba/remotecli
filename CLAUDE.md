# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build Commands

Build the PHAR package (requires Phing and external `buildfiles` repo at `../buildfiles`):
```bash
phing remotecli
```

Build everything (PHAR + native binaries + docs). Unlike other Akeeba projects, `phing all` here does *not* publish to live users:
```bash
phing all
```

Run the tool locally during development (the entry point is `remotecli/remote.php`, not a `bin/` script):
```bash
php remotecli/remote.php <command> [options]
```

## Code Conventions

- **Brace style**: Allman (opening brace on its own line for classes, methods, and control structures)
- **Copyright header**: Every PHP file starts with the `@package AkeebaRemoteCLI` docblock
- **No unit tests**: Testing is done via integration testing against live Akeeba Backup installations (PhpStorm run configs in `.run/`)
- **Adding a command**: create a class extending `AbstractCommand` in `remotecli/Application/Command/`, implement `prepare()` and `execute()`, then register it in the `$dispatcher` array in `remote.php` — an unregistered command silently never runs
- **API communication** lives in the separate `akeeba/json-backup-api` repo (Composer `@dev` dependency); fix API-layer bugs there, not here

## Build System Notes

- Never edit `remotecli/arccli_version.php` directly — it is regenerated at build time from `build/templates/arccli_version.php` (tokens `##VERSION##`, `##DATE##`)

## Git Workflow

- **development** branch: active development (default for PRs)
- **main** branch: releases only
