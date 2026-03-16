# Propuesta de tests para alcanzar 100% de cobertura

Objetivo: cubrir el código actualmente sin cobertura en las clases por debajo del 100%.

**Cobertura actual (tras últimos tests):** Líneas 91,94 % (3283/3571), Clases 85,71 % (60/70). AnonymizeCommand 77,33 %; DatabaseExportService 66,99 % (140/209).

## Índice

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
- [Resumen de tests implementados (iteraciones recientes)](#resumen-de-tests-implementados-iteraciones-recientes)
- [Próximos pasos recomendados](#próximos-pasos-recomendados)

---

## 1. AnonymizationHistoryCommand (96,84 %)

**Implementados:**
- `testExecuteListsRunsWithRunsDisplaysTable`, `testExecuteWithLimitWhenRunsExist`, `testExecuteRunIdWithJsonOutput`, `testGetHistoryDirFromEnvWhenNoParameterBag`, `testGetHistoryDirResolvesKernelProjectDir`, `testGetHistoryDirResolvesKernelProjectDirViaGetcwdWhenContainerHasNoParameter`, `testFormatDurationFormatsCorrectly`.
- `testExecuteRunIdWithEntityStatisticsDisplaysEntitySection` – run con `statistics.entities` no vacío → sección "Entity Statistics" de `displayRun()`.
- `testExecuteRunIdNotFoundReturnsFailure`: `--run-id` inexistente → exit 1 y mensaje "not found" (líneas 115-117).
- `testExecuteShowsInfoWhenNoRunsInHistory`: sin runs en historia → mensaje "No anonymization runs found", exit 0 (líneas 157-159).
- `testDisplayRunShowsNASuccessRateWhenProcessedZero`: entidad con `processed` 0 → Success Rate "N/A" (línea 242).
- `testExecuteCompareWithEntitiesDisplaysEntityComparisonSection`: `--compare` con dos runs con entities → "Entity Statistics Comparison" (líneas 301-319).
- `testGetHistoryDirFallsBackToEnvWhenParameterBagGetThrows`: contenedor tiene `parameter_bag` pero `get()` lanza → fallback a ENV (catch línea 362).

**Pendiente (6 líneas):** ramas que requieren `getProjectDirFromContainer()` devolver `null` (getcwd() false) o `%kernel.project_dir%` aún presente tras primer reemplazo (líneas 377, 391); difícil sin mock de getcwd.

---

## 2. ExportDatabaseCommand (95,06 %)

**Implementados:**
- `testExecuteResolvesKernelProjectDirInOutputDir`, `testExecuteWithMongoConnectionRequestedWhenNoMongoUrl`, `testGetProjectDirFromContainerReturnsCwdWhenNoParameter`.
- `testExecuteShowsFailedToExportWhenExportReturnsNull`: driver no soportado (`pdo_unknown`) → exportación null → "Failed to export", "1 export(s) failed", exit 1.
- `testExecuteShowsExportedToAndSuccessWhenExportSucceeds`: EM con SQLite real → exportación exitosa → "Exported to", formatBytes, "Successfully exported 1 database(s)" (líneas 261-266, 294-296).
- `testExecuteShowsGitignoreNoteWhenExportSucceedsAndAutoGitignoreEnabled`: exportación exitosa sin `--no-gitignore` → nota ".gitignore has been updated to exclude the export directory" (líneas 288-290).
- `testExecuteShowsErrorExportingWhenGetManagerThrows`: `getManager('default')` lanza → catch (271-273), mensaje "Error exporting", "Manager failed", exit 1.
- `testGetParameterBagFallsBackToAdapterWhenGetParameterBagThrows`: container has parameter_bag pero get() lanza → catch (314), fallback a KernelParameterBagAdapter.
- `testExecuteWithMongoConnectionAndMongoUrlParsesUrlAndAttemptsExport`: MONGODB_URL definida, `--connection mongodb`, Doctrine sin managers → parse_url, exportMongoDB (líneas 220-232); mongodump no disponible → fallo esperado, path cubierto.
- `testExecuteWithMongoConnectionNoUrlGetsParamsFromDoctrineAndAttemptsExport`: sin MONGODB_URL, Doctrine con manager `mongodb` → rama else (235-243): getManager, getConnection, getParams, getDatabase, exportMongoDB; fallo por mongodump no disponible.
- `testExecuteUsesOutputDirFromParameterBagWhenOptionNotPassed`: sin `--output-dir`, parameter bag con `nowo_anonymize.export.output_dir` → outputDir desde parámetro (líneas 126-128).
- `testExecuteUsesFilenamePatternAndAutoGitignoreFromParameterBagWhenOptionsNotPassed`: sin `--filename-pattern` ni `--no-gitignore`, parameter bag con `filename_pattern` y `auto_gitignore` => false → tabla de configuración muestra patrón y "Auto .gitignore" No (líneas 132-135, 146-147).

**Pendiente:** 8 líneas restantes. Rama `compression` desde parameter bag (138-141) es inalcanzable: la opción tiene default `gzip`, por lo que `getOption('compression')` nunca es null.

---

## 3. AnonymizeCommand (77,33 %)

**Implementados (además de los ya existentes):**
- `testExecuteWithConnectionOptionProcessesOnlyRequestedManager`, `testExecuteReturnsFailureWhenGetManagerThrows`, tests de stats-json/stats-csv, verbose, parameter_bag throw.
- `testExecuteFailsWhenPreFlightChecksFail`: entidad anonimizable con tabla inexistente → pre-flight devuelve error → exit 1 y "Pre-flight checks failed".
- `testExecuteInteractiveUserCancelsShowsWarningAndReturnsSuccess`: `--interactive` con stream "no" → primera confirmación cancelada → "Anonymization cancelled by user", exit 0 (líneas 282-286).
- `testExecuteInteractiveUserConfirmsProceedShowsSummaryAndContinues`: `--interactive` con stream "y" → resumen y confirmación; usuario confirma → se continúa (líneas 270-287).
- `testExecuteWhenBeforeAnonymizeEventListenerClearsEntitiesProcessesNone`: container con `event_dispatcher`, entidad anonimizable, listener de BeforeAnonymizeEvent que hace setEntityClasses([]) → dispatch y filtrado (líneas 449-455); salida "Found 0 entity(ies) to process".
- `testExecuteWhenEntityHasNoAnonymizePropertyShowsNoteAndSkips`: entidad con #[Anonymize] sin #[AnonymizeProperty] ni anonymizeService → rama empty(properties) && !usesAnonymizeService (589-597), nota "No properties found with #[AnonymizeProperty] attribute" y continue.
- `testExecuteWithDebugWhenEntityHasNoPropertiesShowsDebugSkipMessage`: `--debug` con entidad sin propiedades anonimizables → mensaje "[DEBUG] Skipping entity (no anonymizable properties)" (líneas 594-596).
- `testExecuteWhenEntityUsesCustomAnonymizeServiceAndNoPropertiesShowsMessageAndProcesses`: entidad con #[Anonymize(anonymizeService: "custom_anonymizer")] y sin propiedades → ramas usesAnonymizeService && empty(properties) (560-561, 600-601, 608-609), mensajes "Using custom anonymize service" y "Anonymization: custom service (no property attributes)", y flujo completo con count/barra/progress/anonymizeEntity.
- `testExecuteWithNoProgressProcessesWithoutProgressBar`: `--no-progress` con entidad y custom anonymizer → rama noProgress (643-645), sin barra de progreso (675-677 no ejecutadas).
- `testExecuteWithStatsJsonAbsolutePathExportsToGivenPath`: `--stats-json` con ruta absoluta (p. ej. /tmp/...) → no se antepone stats_output_dir (rama 253-258 no ejecutada para statsJson).
- `testExecuteWhenHistorySaveFailsAndDebugShowsDebugMessage`: history_dir apuntando a un archivo → saveRun falla → catch con mensaje "[DEBUG] Failed to save history" (líneas 373-376).

**Pendiente:** --batch-size en uso real, truncate, más ramas de processConnection/displayStatistics, interactive por entidad/manager, "Skipping entity manager" (333-336) requiere entidades anonimizables en varios managers.

---

## 4. AnonymizeInfoCommand (100 %)

**Implementados:**
- `testCommandHandlesEmptyConnections`, `testExecuteOutputsEntityAndPropertyInfoWhenEntitiesFound`, `testExecuteWithConnectionOptionProcessesOnlyRequestedManager`, `testExecuteOutputsAnonymizeServiceAndEntityPatternsWhenNoProperties`, `testExecuteOutputsPropertyWithServiceOptionsAndPatternsAndZeroRecords`, `testExecuteUsesPropertyNameAsColumnWhenHasFieldFalse`.
- `testCommandReturnsFailureWhenConnectionOptionMatchesNoManager`: `--connection other` cuando solo existe "default" → managersToProcess vacío → "No entity managers found", exit 1.
- `testExecuteSortsPropertiesWithSameWeightAlphabetically`: dos propiedades con mismo weight → rama usort weightA === weightB (orden alfabético).
- `testExecuteSkipsEntityWithNoPropertiesAndNoAnonymizeService`: entidad con #[Anonymize] sin propiedades ni anonymizeService → rama continue (líneas 154-155).
- `testExecuteSortsPropertiesByWeightWhenWeightsDiffer`: dos propiedades con distinto weight → rama usort return $weightA <=> $weightB (línea 198).
- `testExecuteCountsRecordsToAnonymizeWhenRecordMatchesPropertyPattern`: registro que cumple patrones de entidad y propiedad → ++$recordsToAnonymize (líneas 229-230).

---

## 5. GenerateAnonymizedColumnCommand (97,17 %)

**Implementados:**
- `testExecuteGeneratesSqlAndWritesToOutputFile`: entidad con #[Anonymize] y AnonymizableTrait, tabla sin columna `anonymized` → genera ALTER TABLE y escribe en archivo con `--output`.
- `testExecuteSkipsEntityWhenColumnExists`: entidad con trait, tabla existe, columna `anonymized` ya presente → "Column anonymized already exists", "No migrations needed".
- `testExecuteSkipsEntityWithoutTrait`: entidad con #[Anonymize] pero sin AnonymizableTrait → continue (línea 135), "No migrations needed".
- `testExecuteHandlesExceptionInProcessing`: excepción en `createSchemaManager()` → catch (174-176), mensaje de error y continue.
- `testExecuteUsesDefaultManagerWhenConnectionNameDefaultNotInList`: `--connection default` con getManagerNames sin clave 'default' → return getManager() (233-234).
- `testExecuteGeneratesSqlWhenTraitInParentClass`: entidad hija con trait en padre → usesAnonymizableTrait vía getParentClass() (262-272).
- `testExecuteGeneratesSqlWhenTraitInGrandparentClass`: entidad con trait en abuelo → while ($parent) siguiente iteración (línea 269).
- `testExecutePrintsSqlToConsoleWhenNoOutputOption`: SQL generado sin `--output` → salida por consola (197-200).

**Pendiente (3 líneas):** getEntityManager: rama “return $manager” cuando el nombre de conexión coincide por getConnection()->getName() (233-234) y “return null” cuando ningún manager coincide (238). No cubiertas porque Doctrine\DBAL\Connection::getName() no puede configurarse en mocks de PHPUnit (método final o no mockeable). Cubrirlas requeriría un test con conexión real o un wrapper que no sea el mock estándar de Connection.

---

## 6. GenerateMongoAnonymizedFieldCommand (94,12 %)

**Líneas sin cubrir:** rama `elseif (preg_match('/Document(collection\s*=\s*.../'` en `scanDocumentClasses` (líneas 220-221). Esa rama es inalcanzable cuando el primer `preg_match` ya hace match (mismo contenido). No se puede cubrir sin cambiar el orden de los regex o el diseño.

**Propuesta:** Marcar como conocido o añadir comentario `@codeCoverageIgnore` en esa rama si se considera legacy.

---

## 7. DbalHelper (89,47 %)

**Líneas sin cubrir:** 58-59 (`connection->quoteIdentifier`), 63 (`quoteIdentifierFallback` cuando la conexión no tiene `quoteSingleIdentifier` ni `quoteIdentifier`).

**Dificultad:** Con mocks de PHPUnit, `method_exists($connection, 'quoteIdentifier')` es true si el mock tiene ese método. Para cubrir 58-59 y 63 haría falta una conexión real o un stub que no implemente esos métodos (p. ej. clase anónima que extienda `Connection` y oculte métodos), lo que choca con el tipo `Connection` en Doctrine 3.

**Propuestas:**

| Test | Qué cubre |
|------|-----------|
| Usar un stub real (no mock) | Crear una clase de test que implemente solo `getDatabasePlatform()` (lanzando) y `quoteIdentifier()` y pasarla donde se acepte una interfaz mínima; si el helper no type-hinta `Connection` no aplica. |
| Extraer interfaz `QuotableConnection` | Refactor opcional: el helper recibe una interfaz con `quoteIdentifier()` y opcionalmente `getDatabasePlatform()`; en tests se usa un stub de esa interfaz. |

---

## 8. KernelParameterBagAdapter (78,57 %)

**Líneas sin cubrir:** 63-69 (acceso por reflexión a `parameterBag` cuando `getParameterBag()` no devuelve `ParameterBagInterface`), 72-73 (`getParameter()` del container).

**Dificultad:** Para 63-69, el kernel/container tendría que devolver un objeto con `getParameterBag()` que no sea `ParameterBagInterface`, lo que rompe el tipo de retorno en Symfony. Para 72-73, el container tendría que tener `getParameter()` sin tener `parameter_bag`; en la práctica el container de Symfony tiene ambos.

**Propuesta:** Considerar `@codeCoverageIgnore` para esas ramas de fallback o documentar como “legacy/edge case no ejecutable en runtime real”.

---

## 9. AnonymizationHistoryService (100 %)

**Implementado:** Refactor mínimo: constructor acepta opcional `?callable $kernelClassExistsProvider = null`. Si se proporciona, se usa en lugar de `class_exists(Kernel::class)` para decidir si devolver `Kernel::VERSION` o `'unknown'`. Test `testSaveRunStoresUnknownSymfonyVersionWhenKernelClassDoesNotExist`: servicio con `null` como version provider y `() => false` como kernelClassExistsProvider → `saveRun` guarda `symfony_version: 'unknown'`. Comportamiento en producción sin cambios (tercer parámetro null).

---

## 10. AnonymizeService (83,27 %)

**Implementado:** `testTruncateTablesPolymorphicCallsProgressCallbackWithDiscriminatorMessage`: truncate con entidad polimórfica y progressCallback → mensaje con "discriminator", "type", "customer" (cubre línea 232). `testAnonymizeEntityReturnsPropertyStatsWhenPropertyUpdated`: un registro, propiedad `email` anonimizable, metadata con `hasField('email')` y `getFieldMapping` devolviendo `FieldMapping` (Doctrine ORM 3) para `id` y `email` vía `willReturnMap` → ramas que rellenan `propertyStats` (603-606, 660, 669). En tests unitarios con ClassMetadata mock hay que usar `\Doctrine\ORM\Mapping\FieldMapping` real (p. ej. `new FieldMapping('email', 'string', 'email')`) en lugar de array, porque el tipo de retorno declarado es `FieldMapping`; `convertValue` además llama a `getFieldMapping` para cada campo en `getFieldNames()`, por lo que el mock debe devolver FieldMapping para todos (p. ej. id y email con `willReturnMap`).

**Ramas sin cubrir:** En `getDiscriminatorForTruncate`, cuando `discriminatorColumn` es array (líneas 314-315). Doctrine 3 tipa `ClassMetadata::$discriminatorColumn` como `?DiscriminatorColumnMapping`, no se puede asignar array en test sin refactor.

**Propuesta:** Aceptar bloqueo para rama array; opcional extraer “discriminator resolver” inyectable para cubrir en tests.

---

## 11. DatabaseExportService (66,99 %)

**Implementados (adicionales):**
- `testUpdateGitignoreReturnsEarlyWhenProjectDirEmpty`: contenedor con `getParameter('kernel.project_dir')` devolviendo `''` → `updateGitignore` hace return early (líneas 377-378); se comprueba que no se escribe la entrada en .gitignore.
- `testCommandExistsReturnsFalseForNonexistentCommand`: reflexión sobre `commandExists('nonexistent_...')` → false.
- `testCommandExistsReturnsTrueForExistingCommand`: reflexión sobre `commandExists('php')` → true.
- `testCreateTarArchiveWithGzipExtension`: reflexión `createTarArchive` con path `.tar.gz` → rama compressionFlag `z` (líneas 404-406).
- `testCreateTarArchiveWithBzip2Extension`: reflexión con path `.tar.bz2` → rama compressionFlag `j` (406-408).
- `testCreateZipArchiveReturnsFalseWhenOpenFails`: reflexión con path no escribible (p. ej. `/dev/null`) → `ZipArchive::open` !== true, return false (373-374).
- `testRemoveDirectoryWithNestedSubdirectory`: reflexión `removeDirectory` con subdirectorio anidado → rama recursiva is_dir (334-335).
- `testExportSQLiteReturnsNullWhenPathDoesNotExist`: SQLite con `path` apuntando a archivo inexistente → `exportSQLite` return null (!file_exists, línea 261).
- `testExportConnectionReturnsNullForMySQLWhenMysqldumpNotAvailable`: driver `pdo_mysql` sin mysqldump en PATH → entra en `exportMySQL` y return null (línea 182).
- `testExportConnectionReturnsNullForPostgreSQLWhenPgDumpNotAvailable`: driver `pdo_pgsql` sin pg_dump en PATH → entra en `exportPostgreSQL` y return null (línea 219).

**Propuestas pendientes:**

| Test | Qué cubre |
|------|-----------|
| `exportMongoDB` cuando `mongodump` existe | Entorno con `mongodump` en PATH y MongoDB arrancado, o mock de `exec()` para simular éxito y cubrir creación de zip/tar. |
| `exportMySQL` / `exportPostgreSQL` | Similar: mock de `exec()` o binarios reales en CI. |
| Más métodos privados por reflexión | Ramas de `compressFile` (zip open falla), etc., cuando sea posible sin trucos. |

---

## 12. PatternMatcher (95,40 %)

**Líneas sin cubrir:** 208-210 (opción con `%` dentro del patrón `|`). Inalcanzables porque si el patrón contiene `%` se usa antes la rama LIKE (líneas 198-201).

**Propuesta:** Documentar o marcar con `@codeCoverageIgnore`; o reordenar el código para que la rama `|` con `%` sea alcanzable si se desea cubrirla.

---

## Resumen de tests implementados (iteraciones recientes)

- **AnonymizationHistoryCommandTest**
  - `testExecuteListsRunsWithRunsDisplaysTable`, `testExecuteWithLimitWhenRunsExist`, `testExecuteRunIdWithJsonOutput`, `testExecuteRunIdWithEntityStatisticsDisplaysEntitySection`.
  - `testGetHistoryDirFromEnvWhenNoParameterBag`, `testGetHistoryDirResolvesKernelProjectDir`, `testFormatDurationFormatsCorrectly`.
- **AnonymizeInfoCommandTest**
  - `testCommandHandlesEmptyConnections`, `testExecuteOutputsEntityAndPropertyInfoWhenEntitiesFound`, `testExecuteWithConnectionOptionProcessesOnlyRequestedManager`, `testExecuteOutputsAnonymizeServiceAndEntityPatternsWhenNoProperties`, `testExecuteOutputsPropertyWithServiceOptionsAndPatternsAndZeroRecords`, `testExecuteUsesPropertyNameAsColumnWhenHasFieldFalse`, `testCommandReturnsFailureWhenConnectionOptionMatchesNoManager`, `testExecuteSortsPropertiesWithSameWeightAlphabetically`.
- **ExportDatabaseCommandTest**
  - … más `testExecuteUsesOutputDirFromParameterBagWhenOptionNotPassed`, `testExecuteUsesFilenamePatternAndAutoGitignoreFromParameterBagWhenOptionsNotPassed`. **ExportDatabaseCommand 95,06 %.**
- **AnonymizeCommandTest**
  - `testExecuteWithConnectionOptionProcessesOnlyRequestedManager`, `testExecuteReturnsFailureWhenGetManagerThrows`, `testExecuteWithStatsJsonRelativePathExportsFile`, `testExecuteWithStatsCsvRelativePathExportsFile`, `testExecuteWithVerboseShowsNote`, `testExecuteWhenParameterBagGetThrowsUsesKernelAdapter`, `testExecuteFailsWhenPreFlightChecksFail`.
- **GenerateAnonymizedColumnCommandTest**
  - `testExecuteGeneratesSqlAndWritesToOutputFile` (entidad con AnonymizableTrait, generación SQL y --output); `testExecuteHandlesExceptionGracefully` ajustado (mensaje "not found").
- **PatternBasedFakerTest**
  - `testGenerateWithFallbackWhenSourceFieldMissingFromRecord` usa faker `constant` para evitar dependencia de intl.
- **UtmFakerTest**
  - `testGenerateTermTruncatesInsideMethodWhenExceedingMaxLength`: type `term` con `min_length` 30 y `max_length` 10 para cubrir truncamiento interno en `generateTerm()` (línea 266).
  - `testGenerateCampaignPadsShortPattern`: 250 iteraciones con `min_length` 25 y `max_length` 55 para cubrir el while que rellena patrón corto en `generateCampaign()` (líneas 210-211). **UtmFaker 100 %.**

**Resultado actual:** Líneas 91,94 % (3283/3571); AnonymizeCommand 77,33 %; ExportDatabaseCommand 95,06 %; DatabaseExportService 66,99 % (140/209); AnonymizationHistoryCommand 96,84 %; GenerateAnonymizedColumnCommand 97,17 %.

---

## Próximos pasos recomendados

1. Seguir con **ExportDatabaseCommand** (8 líneas restantes: getParameterBag catch, formatBytes, getProjectDirFromContainer getcwd false) o **AnonymizationHistoryCommand** (displayRun/displayComparison).
2. Añadir tests para **AnonymizeCommand** (dry-run, connection, batch-size, truncate) y completar **AnonymizeInfoCommand** al 100 % (service, options, patrones por propiedad).
3. Para ramas inalcanzables o muy costosas (DbalHelper, KernelParameterBagAdapter, PatternMatcher OR+%, GenerateMongo `elseif`): documentar en este archivo; el prompt prohíbe `@codeCoverageIgnore`.
