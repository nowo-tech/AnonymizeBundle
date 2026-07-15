# Test proposal to achieve 100% coverage

Goal: cover code that is currently uncovered in classes below 100%.

**Current coverage (after latest tests):** Lines 91,94 % (3283/3571), Classes 85,71 % (60/70). AnonymizeCommand 77,33 %; DatabaseExportService 66,99 % (140/209).

## Table of contents

- [1. AnonymizationHistoryCommand (96,84 %)](#1-anonymizationhistorycommand-9684-)
- [2. ExportDatabaseCommand (95,06 %)](#2-exportdatabasecommand-9506-)
- [3. AnonymizeCommand (54,42 %)](#3-anonymizecommand-5442-)
- [4. AnonymizeInfoCommand (100 %)](#4-anonymizeinfocommand-100-)
- [5. GenerateAnonymizedColumnCommand (97,17 %)](#5-generateanonymizedcolumncommand-9717-)
- [6. GenerateMongoAnonymizedFieldCommand (94,12 %)](#6-generatemongoanonymizedfieldcommand-9412-)
- [7. DbalHelper (89,47 %)](#7-dbalhelper-8947-)
- [8. KernelParameterBagAdapter (78,57 %)](#8-kernelparameterbagadapter-7857-)
- [9. AnonymizationHistoryService (100 %)](#9-anonymizationhistoryservice-100-)
- [10. AnonymizeService (82,86 %)](#10-anonymizeservice-8286-)
- [11. DatabaseExportService (66,99 %)](#11-databaseexportservice-6699-)
- [12. PatternMatcher (95,40 %)](#12-patternmatcher-9540-)
- [Summary of implemented tests (recent iterations)](#summary-of-implemented-tests-recent-iterations)
- [Recommended next steps](#recommended-next-steps)

---

## 1. AnonymizationHistoryCommand (96,84 %)

**Implemented:**
- `testExecuteListsRunsWithRunsDisplaysTable`, `testExecuteWithLimitWhenRunsExist`, `testExecuteRunIdWithJsonOutput`, `testGetHistoryDirFromEnvWhenNoParameterBag`, `testGetHistoryDirResolvesKernelProjectDir`, `testGetHistoryDirResolvesKernelProjectDirViaGetcwdWhenContainerHasNoParameter`, `testFormatDurationFormatsCorrectly`.
- `testExecuteRunIdWithEntityStatisticsDisplaysEntitySection` – run with non-empty `statistics.entities` → "Entity Statistics" section from `displayRun()`.
- `testExecuteRunIdNotFoundReturnsFailure`: non-existent `--run-id` → exit 1 and "not found" message (lines 115-117).
- `testExecuteShowsInfoWhenNoRunsInHistory`: no runs in history → "No anonymization runs found" message, exit 0 (lines 157-159).
- `testDisplayRunShowsNASuccessRateWhenProcessedZero`: entity with `processed` 0 → Success Rate "N/A" (line 242).
- `testExecuteCompareWithEntitiesDisplaysEntityComparisonSection`: `--compare` with two runs with entities → "Entity Statistics Comparison" (lines 301-319).
- `testGetHistoryDirFallsBackToEnvWhenParameterBagGetThrows`: container has `parameter_bag` but `get()` throws → fallback to ENV (catch line 362).

**Remaining (6 lines):** branches that require `getProjectDirFromContainer()` to return `null` (getcwd() false) or `%kernel.project_dir%` still present after first replacement (lines 377, 391); difficult without mocking getcwd.

---

## 2. ExportDatabaseCommand (95,06 %)

**Implemented:**
- `testExecuteResolvesKernelProjectDirInOutputDir`, `testExecuteWithMongoConnectionRequestedWhenNoMongoUrl`, `testGetProjectDirFromContainerReturnsCwdWhenNoParameter`.
- `testExecuteShowsFailedToExportWhenExportReturnsNull`: unsupported driver (`pdo_unknown`) → null export → "Failed to export", "1 export(s) failed", exit 1.
- `testExecuteShowsExportedToAndSuccessWhenExportSucceeds`: EM with real SQLite → successful export → "Exported to", formatBytes, "Successfully exported 1 database(s)" (lines 261-266, 294-296).
- `testExecuteShowsGitignoreNoteWhenExportSucceedsAndAutoGitignoreEnabled`: successful export without `--no-gitignore` → note ".gitignore has been updated to exclude the export directory" (lines 288-290).
- `testExecuteShowsErrorExportingWhenGetManagerThrows`: `getManager('default')` throws → catch (271-273), "Error exporting" message, "Manager failed", exit 1.
- `testGetParameterBagFallsBackToAdapterWhenGetParameterBagThrows`: container has parameter_bag but get() throws → catch (314), fallback to KernelParameterBagAdapter.
- `testExecuteWithMongoConnectionAndMongoUrlParsesUrlAndAttemptsExport`: MONGODB_URL defined, `--connection mongodb`, Doctrine without managers → parse_url, exportMongoDB (lines 220-232); mongodump unavailable → expected failure, path covered.
- `testExecuteWithMongoConnectionNoUrlGetsParamsFromDoctrineAndAttemptsExport`: without MONGODB_URL, Doctrine with `mongodb` manager → else branch (235-243): getManager, getConnection, getParams, getDatabase, exportMongoDB; failure due to mongodump unavailable.
- `testExecuteUsesOutputDirFromParameterBagWhenOptionNotPassed`: without `--output-dir`, parameter bag with `nowo_anonymize.export.output_dir` → outputDir from parameter (lines 126-128).
- `testExecuteUsesFilenamePatternAndAutoGitignoreFromParameterBagWhenOptionsNotPassed`: without `--filename-pattern` or `--no-gitignore`, parameter bag with `filename_pattern` and `auto_gitignore` => false → configuration table shows pattern and "Auto .gitignore" No (lines 132-135, 146-147).

**Remaining:** 8 remaining lines. `compression` branch from parameter bag (138-141) is unreachable: the option has default `gzip`, so `getOption('compression')` is never null.

---

## 3. AnonymizeCommand (77,33 %)

**Implemented (in addition to existing ones):**
- `testExecuteWithConnectionOptionProcessesOnlyRequestedManager`, `testExecuteReturnsFailureWhenGetManagerThrows`, stats-json/stats-csv tests, verbose, parameter_bag throw.
- `testExecuteFailsWhenPreFlightChecksFail`: anonymizable entity with non-existent table → pre-flight returns error → exit 1 and "Pre-flight checks failed".
- `testExecuteInteractiveUserCancelsShowsWarningAndReturnsSuccess`: `--interactive` with stream "no" → first confirmation cancelled → "Anonymization cancelled by user", exit 0 (lines 282-286).
- `testExecuteInteractiveUserConfirmsProceedShowsSummaryAndContinues`: `--interactive` with stream "y" → summary and confirmation; user confirms → continues (lines 270-287).
- `testExecuteWhenBeforeAnonymizeEventListenerClearsEntitiesProcessesNone`: container with `event_dispatcher`, anonymizable entity, BeforeAnonymizeEvent listener that calls setEntityClasses([]) → dispatch and filtering (lines 449-455); output "Found 0 entity(ies) to process".
- `testExecuteWhenEntityHasNoAnonymizePropertyShowsNoteAndSkips`: entity with #[Anonymize] without #[AnonymizeProperty] or anonymizeService → empty(properties) && !usesAnonymizeService branch (589-597), note "No properties found with #[AnonymizeProperty] attribute" and continue.
- `testExecuteWithDebugWhenEntityHasNoPropertiesShowsDebugSkipMessage`: `--debug` with entity without anonymizable properties → "[DEBUG] Skipping entity (no anonymizable properties)" message (lines 594-596).
- `testExecuteWhenEntityUsesCustomAnonymizeServiceAndNoPropertiesShowsMessageAndProcesses`: entity with #[Anonymize(anonymizeService: "custom_anonymizer")] and no properties → usesAnonymizeService && empty(properties) branches (560-561, 600-601, 608-609), messages "Using custom anonymize service" and "Anonymization: custom service (no property attributes)", and full flow with count/bar/progress/anonymizeEntity.
- `testExecuteWithNoProgressProcessesWithoutProgressBar`: `--no-progress` with entity and custom anonymizer → noProgress branch (643-645), no progress bar (675-677 not executed).
- `testExecuteWithStatsJsonAbsolutePathExportsToGivenPath`: `--stats-json` with absolute path (e.g. /tmp/...) → stats_output_dir not prepended (branch 253-258 not executed for statsJson).
- `testExecuteWhenHistorySaveFailsAndDebugShowsDebugMessage`: history_dir pointing to a file → saveRun fails → catch with "[DEBUG] Failed to save history" message (lines 373-376).

**Remaining:** --batch-size in real use, truncate, more branches of processConnection/displayStatistics, interactive per entity/manager, "Skipping entity manager" (333-336) requires anonymizable entities in multiple managers.

---

## 4. AnonymizeInfoCommand (100 %)

**Implemented:**
- `testCommandHandlesEmptyConnections`, `testExecuteOutputsEntityAndPropertyInfoWhenEntitiesFound`, `testExecuteWithConnectionOptionProcessesOnlyRequestedManager`, `testExecuteOutputsAnonymizeServiceAndEntityPatternsWhenNoProperties`, `testExecuteOutputsPropertyWithServiceOptionsAndPatternsAndZeroRecords`, `testExecuteUsesPropertyNameAsColumnWhenHasFieldFalse`.
- `testCommandReturnsFailureWhenConnectionOptionMatchesNoManager`: `--connection other` when only "default" exists → empty managersToProcess → "No entity managers found", exit 1.
- `testExecuteSortsPropertiesWithSameWeightAlphabetically`: two properties with same weight → usort branch weightA === weightB (alphabetical order).
- `testExecuteSkipsEntityWithNoPropertiesAndNoAnonymizeService`: entity with #[Anonymize] without properties or anonymizeService → continue branch (lines 154-155).
- `testExecuteSortsPropertiesByWeightWhenWeightsDiffer`: two properties with different weight → usort branch return $weightA <=> $weightB (line 198).
- `testExecuteCountsRecordsToAnonymizeWhenRecordMatchesPropertyPattern`: record matching entity and property patterns → ++$recordsToAnonymize (lines 229-230).

---

## 5. GenerateAnonymizedColumnCommand (97,17 %)

**Implemented:**
- `testExecuteGeneratesSqlAndWritesToOutputFile`: entity with #[Anonymize] and AnonymizableTrait, table without `anonymized` column → generates ALTER TABLE and writes to file with `--output`.
- `testExecuteSkipsEntityWhenColumnExists`: entity with trait, table exists, `anonymized` column already present → "Column anonymized already exists", "No migrations needed".
- `testExecuteSkipsEntityWithoutTrait`: entity with #[Anonymize] but without AnonymizableTrait → continue (line 135), "No migrations needed".
- `testExecuteHandlesExceptionInProcessing`: exception in `createSchemaManager()` → catch (174-176), error message and continue.
- `testExecuteUsesDefaultManagerWhenConnectionNameDefaultNotInList`: `--connection default` with getManagerNames without 'default' key → return getManager() (233-234).
- `testExecuteGeneratesSqlWhenTraitInParentClass`: child entity with trait in parent → usesAnonymizableTrait via getParentClass() (262-272).
- `testExecuteGeneratesSqlWhenTraitInGrandparentClass`: entity with trait in grandparent → while ($parent) next iteration (line 269).
- `testExecutePrintsSqlToConsoleWhenNoOutputOption`: generated SQL without `--output` → console output (197-200).

**Remaining (3 lines):** getEntityManager: "return $manager" branch when connection name matches via getConnection()->getName() (233-234) and "return null" when no manager matches (238). Not covered because Doctrine\DBAL\Connection::getName() cannot be configured in PHPUnit mocks (final method or not mockable). Covering them would require a test with a real connection or a wrapper that is not the standard Connection mock.

---

## 6. GenerateMongoAnonymizedFieldCommand (94,12 %)

**Uncovered lines:** `elseif (preg_match('/Document(collection\s*=\s*.../'` branch in `scanDocumentClasses` (lines 220-221). That branch is unreachable when the first `preg_match` already matches (same content). Cannot be covered without changing the regex order or the design.

**Proposal:** Mark as known or add `@codeCoverageIgnore` comment on that branch if considered legacy.

---

## 7. DbalHelper (89,47 %)

**Uncovered lines:** 58-59 (`connection->quoteIdentifier`), 63 (`quoteIdentifierFallback` when the connection has neither `quoteSingleIdentifier` nor `quoteIdentifier`).

**Difficulty:** With PHPUnit mocks, `method_exists($connection, 'quoteIdentifier')` is true if the mock has that method. To cover 58-59 and 63 would require a real connection or a stub that does not implement those methods (e.g. anonymous class extending `Connection` and hiding methods), which conflicts with the `Connection` type in Doctrine 3.

**Proposals:**

| Test | What it covers |
|------|----------------|
| Use a real stub (not mock) | Create a test class that implements only `getDatabasePlatform()` (throwing) and `quoteIdentifier()` and pass it where a minimal interface is accepted; does not apply if the helper type-hints `Connection`. |
| Extract `QuotableConnection` interface | Optional refactor: the helper receives an interface with `quoteIdentifier()` and optionally `getDatabasePlatform()`; tests use a stub of that interface. |

---

## 8. KernelParameterBagAdapter (78,57 %)

**Uncovered lines:** 63-69 (reflection access to `parameterBag` when `getParameterBag()` does not return `ParameterBagInterface`), 72-73 (container `getParameter()`).

**Difficulty:** For 63-69, the kernel/container would have to return an object with `getParameterBag()` that is not `ParameterBagInterface`, which breaks the return type in Symfony. For 72-73, the container would have to have `getParameter()` without having `parameter_bag`; in practice the Symfony container has both.

**Proposal:** Consider `@codeCoverageIgnore` for those fallback branches or document as "legacy/edge case not executable in real runtime".

---

## 9. AnonymizationHistoryService (100 %)

**Implemented:** Minimal refactor: constructor accepts optional `?callable $kernelClassExistsProvider = null`. If provided, it is used instead of `class_exists(Kernel::class)` to decide whether to return `Kernel::VERSION` or `'unknown'`. Test `testSaveRunStoresUnknownSymfonyVersionWhenKernelClassDoesNotExist`: service with `null` as version provider and `() => false` as kernelClassExistsProvider → `saveRun` stores `symfony_version: 'unknown'`. Production behavior unchanged (third parameter null).

---

## 10. AnonymizeService (83,27 %)

**Implemented:** `testTruncateTablesPolymorphicCallsProgressCallbackWithDiscriminatorMessage`: truncate with polymorphic entity and progressCallback → message with "discriminator", "type", "customer" (covers line 232). `testAnonymizeEntityReturnsPropertyStatsWhenPropertyUpdated`: one record, anonymizable `email` property, metadata with `hasField('email')` and `getFieldMapping` returning `FieldMapping` (Doctrine ORM 3) for `id` and `email` via `willReturnMap` → branches that populate `propertyStats` (603-606, 660, 669). In unit tests with ClassMetadata mock, use real `\Doctrine\ORM\Mapping\FieldMapping` (e.g. `new FieldMapping('email', 'string', 'email')`) instead of array, because the declared return type is `FieldMapping`; `convertValue` also calls `getFieldMapping` for each field in `getFieldNames()`, so the mock must return FieldMapping for all (e.g. id and email with `willReturnMap`).

**Uncovered branches:** In `getDiscriminatorForTruncate`, when `discriminatorColumn` is array (lines 314-315). Doctrine 3 types `ClassMetadata::$discriminatorColumn` as `?DiscriminatorColumnMapping`, array cannot be assigned in test without refactor.

**Proposal:** Accept blocking for array branch; optionally extract injectable "discriminator resolver" to cover in tests.

---

## 11. DatabaseExportService (66,99 %)

**Implemented (additional):**
- `testUpdateGitignoreReturnsEarlyWhenProjectDirEmpty`: container with `getParameter('kernel.project_dir')` returning `''` → `updateGitignore` returns early (lines 377-378); verified that entry is not written to .gitignore.
- `testCommandExistsReturnsFalseForNonexistentCommand`: reflection on `commandExists('nonexistent_...')` → false.
- `testCommandExistsReturnsTrueForExistingCommand`: reflection on `commandExists('php')` → true.
- `testCreateTarArchiveWithGzipExtension`: reflection `createTarArchive` with `.tar.gz` path → compressionFlag `z` branch (lines 404-406).
- `testCreateTarArchiveWithBzip2Extension`: reflection with `.tar.bz2` path → compressionFlag `j` branch (406-408).
- `testCreateZipArchiveReturnsFalseWhenOpenFails`: reflection with non-writable path (e.g. `/dev/null`) → `ZipArchive::open` !== true, return false (373-374).
- `testRemoveDirectoryWithNestedSubdirectory`: reflection `removeDirectory` with nested subdirectory → recursive is_dir branch (334-335).
- `testExportSQLiteReturnsNullWhenPathDoesNotExist`: SQLite with `path` pointing to non-existent file → `exportSQLite` return null (!file_exists, line 261).
- `testExportConnectionReturnsNullForMySQLWhenMysqldumpNotAvailable`: `pdo_mysql` driver without mysqldump in PATH → enters `exportMySQL` and return null (line 182).
- `testExportConnectionReturnsNullForPostgreSQLWhenPgDumpNotAvailable`: `pdo_pgsql` driver without pg_dump in PATH → enters `exportPostgreSQL` and return null (line 219).

**Remaining proposals:**

| Test | What it covers |
|------|----------------|
| `exportMongoDB` when `mongodump` exists | Environment with `mongodump` in PATH and MongoDB running, or mock `exec()` to simulate success and cover zip/tar creation. |
| `exportMySQL` / `exportPostgreSQL` | Similar: mock `exec()` or real binaries in CI. |
| More private methods via reflection | Branches of `compressFile` (zip open fails), etc., when possible without tricks. |

---

## 12. PatternMatcher (95,40 %)

**Uncovered lines:** 208-210 (option with `%` inside the `|` pattern). Unreachable because if the pattern contains `%` the LIKE branch is used first (lines 198-201).

**Proposal:** Document or mark with `@codeCoverageIgnore`; or reorder the code so the `|` branch with `%` is reachable if coverage is desired.

---

## Summary of implemented tests (recent iterations)

- **AnonymizationHistoryCommandTest**
  - `testExecuteListsRunsWithRunsDisplaysTable`, `testExecuteWithLimitWhenRunsExist`, `testExecuteRunIdWithJsonOutput`, `testExecuteRunIdWithEntityStatisticsDisplaysEntitySection`.
  - `testGetHistoryDirFromEnvWhenNoParameterBag`, `testGetHistoryDirResolvesKernelProjectDir`, `testFormatDurationFormatsCorrectly`.
- **AnonymizeInfoCommandTest**
  - `testCommandHandlesEmptyConnections`, `testExecuteOutputsEntityAndPropertyInfoWhenEntitiesFound`, `testExecuteWithConnectionOptionProcessesOnlyRequestedManager`, `testExecuteOutputsAnonymizeServiceAndEntityPatternsWhenNoProperties`, `testExecuteOutputsPropertyWithServiceOptionsAndPatternsAndZeroRecords`, `testExecuteUsesPropertyNameAsColumnWhenHasFieldFalse`, `testCommandReturnsFailureWhenConnectionOptionMatchesNoManager`, `testExecuteSortsPropertiesWithSameWeightAlphabetically`.
- **ExportDatabaseCommandTest**
  - … plus `testExecuteUsesOutputDirFromParameterBagWhenOptionNotPassed`, `testExecuteUsesFilenamePatternAndAutoGitignoreFromParameterBagWhenOptionsNotPassed`. **ExportDatabaseCommand 95,06 %.**
- **AnonymizeCommandTest**
  - `testExecuteWithConnectionOptionProcessesOnlyRequestedManager`, `testExecuteReturnsFailureWhenGetManagerThrows`, `testExecuteWithStatsJsonRelativePathExportsFile`, `testExecuteWithStatsCsvRelativePathExportsFile`, `testExecuteWithVerboseShowsNote`, `testExecuteWhenParameterBagGetThrowsUsesKernelAdapter`, `testExecuteFailsWhenPreFlightChecksFail`.
- **GenerateAnonymizedColumnCommandTest**
  - `testExecuteGeneratesSqlAndWritesToOutputFile` (entity with AnonymizableTrait, SQL generation and --output); `testExecuteHandlesExceptionGracefully` adjusted ("not found" message).
- **PatternBasedFakerTest**
  - `testGenerateWithFallbackWhenSourceFieldMissingFromRecord` uses `constant` faker to avoid intl dependency.
- **UtmFakerTest**
  - `testGenerateTermTruncatesInsideMethodWhenExceedingMaxLength`: type `term` with `min_length` 30 and `max_length` 10 to cover internal truncation in `generateTerm()` (line 266).
  - `testGenerateCampaignPadsShortPattern`: 250 iterations with `min_length` 25 and `max_length` 55 to cover the while that pads short pattern in `generateCampaign()` (lines 210-211). **UtmFaker 100 %.**

**Current result:** Lines 91,94 % (3283/3571); AnonymizeCommand 77,33 %; ExportDatabaseCommand 95,06 %; DatabaseExportService 66,99 % (140/209); AnonymizationHistoryCommand 96,84 %; GenerateAnonymizedColumnCommand 97,17 %.

---

## Recommended next steps

1. Continue with **ExportDatabaseCommand** (8 remaining lines: getParameterBag catch, formatBytes, getProjectDirFromContainer getcwd false) or **AnonymizationHistoryCommand** (displayRun/displayComparison).
2. Add tests for **AnonymizeCommand** (dry-run, connection, batch-size, truncate) and complete **AnonymizeInfoCommand** to 100 % (service, options, patterns per property).
3. For unreachable or very costly branches (DbalHelper, KernelParameterBagAdapter, PatternMatcher OR+%, GenerateMongo `elseif`): document in this file; the prompt forbids `@codeCoverageIgnore`.
