# Akeeba Remote CLI test suites

Two layers, deliberately kept apart.

**Unit tests** run in-process, touch no network and no Docker, and cover the parts of the application which can
honestly be tested in isolation: command line parsing, the configuration file, option handling, and the credential and
host normalisation logic every command inherits from `AbstractCommand`.

**Integration tests** run Remote CLI as a program. It runs in a throwaway container, under a PHP version chosen by the
matrix, and talks over real HTTP to a throwaway web server which speaks all three versions of the Akeeba Backup JSON
API. Nothing is loaded in-process; the suite watches the tool the way a shell does — arguments in, exit code and output
out.

Neither suite ships. The PHAR is built from the `remotecli/` directory alone, so everything here is outside it by
construction, and `.gitattributes` keeps it out of `git archive` exports as well.

## Requirements

* **PHPUnit, installed globally.** It is deliberately *not* a dependency of this project: the PHAR we ship must not
  carry a test framework inside it.

  ```bash
  composer global require phpunit/phpunit
  export PATH="$PATH:$(composer global config bin-dir --absolute)"
  ```

* `composer install`, for the application's own dependencies.
* **Docker**, for the integration suite only.

## Running the unit suite

```bash
phpunit
```

That is all. It reads `phpunit.xml`, runs everything under `tests/Unit`, and finishes in well under a second.

## Running the integration suite

```bash
tests/docker/run.sh
```

`run.sh` is the only supported entry point. Do not compose the stack or invoke `phpunit -c phpunit-integration.xml`
by hand: the suite needs `tests/config.php`, which `run.sh` generates, and a stack whose state has been scrubbed.

It scrubs any previous stack, builds the images, brings them up, runs the suite from the host against them, and tears
the stack down again. A run which fails or is interrupted never leaves a stack behind.

| Option               | What it does                                                                       |
|----------------------|------------------------------------------------------------------------------------|
| `--php <version>`    | Run Remote CLI under this PHP version instead of the default.                       |
| `--matrix`           | Run the suite once under every PHP version in `E2E_PHP_MATRIX`.                     |
| `--filter <pattern>` | Passed straight through to PHPUnit.                                                 |
| `--skip-build`       | Reuse the images as they are instead of rebuilding them.                            |
| `--keep-containers`  | Leave the stack up afterwards, and reuse it if it is already up *and* `--skip-build`.|
| `--down`             | Tear the stack down and exit.                                                       |
| `--no-tests`         | Bring the stack up and provision it, but do not run the suite.                       |

The fast iteration loop is `--keep-containers --skip-build`: provisioning is the slow part, and re-running against a
stack which is already up takes a couple of seconds. Reuse deliberately requires *both* flags, because the fixture is
baked into the image and reusing a stack after editing it would silently test the previous version. The fixture
directory is also mounted over the image, so an edit to the fixture takes effect on the next request either way.

Settings live in `tests/docker/.env`, copied from `env.dist` on the first run.

## Why the matrix contains what it does

`composer.json` declares `"php": ">=8.2.0 <8.7"`. The matrix is every released version in that range:

| PHP | Why it is in the matrix                                                                     |
|-----|---------------------------------------------------------------------------------------------|
| 8.2 | The declared minimum. The floor is enforced at startup, so this is the oldest supported run. |
| 8.3 | Interior version.                                                                            |
| 8.4 | Interior version.                                                                            |
| 8.5 | The newest released version, and the day-to-day default.                                     |

8.6 is absent only because it has not been released. Add it to `E2E_PHP_MATRIX` when it is; nothing else changes.
The declared upper bound is the *first unsupported* version, 8.7, which `PhpVersionGuardTest` checks is actually
refused rather than merely declared.

A green single-version run proves less than it looks, and this is one of the cases where that matters: the interior
versions are here because the tool's own floor moved recently, not because any version-gated code path is known to
differ between them. Treat a green 8.5-only run as "the logic works", not as "the supported range works".

## The fixture

`tests/docker/fixture/` is a small web application which answers like Akeeba Backup's JSON API: the same response
shapes, authentication rules, error statuses and endpoints, across v1, v2 and v3. It takes no backups and writes no
real archives, and it does not need to — everything the suite asserts about Remote CLI is observable from the far end
of the conversation.

Using a real Joomla site with Akeeba Backup Professional instead is not something a test suite can rely on. The
Professional package is licence-gated, so nobody could run the suite from a fresh checkout, and a real site cannot be
asked on demand to hand out a restricted API token, bury its answer in PHP warnings, or forget how to speak v3 — all of
which are behaviours the suite needs.

`control.php` is the fixture's back door. It resets state between tests, arms those behaviours, and reports every
request the server actually received. That last one is what makes assertions like "the credential never appeared in a
URL" or "the refused delete did not delete anything" statements about the world rather than about a log line.

## Tests which are skipped, and why

Four tests are skipped because they document real bugs rather than test-writing mistakes: one in the unit suite and
three in the integration suite. Each carries the diagnosis and the fix in its docblock, and each is written to pass
once the bug is fixed, so unskipping is the whole change. Two of the four are bugs in this project; two are in the
`akeeba/json-backup-api` dependency.

| Test                                              | Bug                                                                            |
|---------------------------------------------------|--------------------------------------------------------------------------------|
| `CliParsingTest::testCommandLineBeatsMergedData`  | The configuration file overrules the command line. The manual documents the opposite. |
| `HostNormalisationTest::testUnreachableHostExitsNonZero` | A network failure exits 0, so an unattended run reports success.        |
| `ConnectionTest::testTrailingJunkIsTolerated`     | In `akeeba/json-backup-api`: junk *after* the JSON is not stripped.             |
| `ProfileTest::testExportedProfileCanBeImported`   | In `akeeba/json-backup-api`: `profileimport` cannot work against a real site.   |
