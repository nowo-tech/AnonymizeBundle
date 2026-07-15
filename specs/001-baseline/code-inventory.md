# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/anonymize-bundle`  
**Last audited**: 2026-07-15

Tests under `tests/` are out of Packagist scope.

## Symfony config (`src/Resources/config/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/config/services.yaml` | Service wiring | FR-DI-001 |

## Bundle & DI (`src/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `AnonymizeBundle.php` | Bundle entry | FR-BUNDLE-001 |
| `DependencyInjection/Configuration.php` | Config tree `nowo_anonymize` | FR-CFG-001 |
| `DependencyInjection/AnonymizeExtension.php` | DI extension | FR-CFG-002 |

## Attributes & trait

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Attribute/Anonymize.php` | Entity-level attribute | FR-ATTR-001 |
| `Attribute/AnonymizeProperty.php` | Property-level attribute | FR-ATTR-001 |
| `Trait/AnonymizableTrait.php` | Optional entity trait | FR-ATTR-002 |

## CLI commands

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Command/AbstractCommand.php` | Shared CLI base | FR-CLI-001 |
| `Command/AnonymizeCommand.php` | Main anonymize runner | FR-CLI-001 |
| `Command/AnonymizeInfoCommand.php` | Discovery / dry info | FR-CLI-001 |
| `Command/AnonymizationHistoryCommand.php` | History viewer | FR-CLI-001 |
| `Command/ExportDatabaseCommand.php` | DB export | FR-CLI-001 |
| `Command/GenerateAnonymizedColumnCommand.php` | Column SQL generator | FR-CLI-001 |
| `Command/GenerateMongoAnonymizedFieldCommand.php` | Mongo field generator | FR-CLI-001 |

## Events

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Event/BeforeAnonymizeEvent.php` | Pre-run hook | FR-EVT-001 |
| `Event/AfterAnonymizeEvent.php` | Post-run hook | FR-EVT-001 |
| `Event/BeforeEntityAnonymizeEvent.php` | Pre-entity hook | FR-EVT-001 |
| `Event/AfterEntityAnonymizeEvent.php` | Post-entity hook | FR-EVT-001 |
| `Event/AnonymizePropertyEvent.php` | Per-property hook | FR-EVT-001 |

## Enums

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Enum/FakerType.php` | Built-in faker type names | FR-ENUM-001 |
| `Enum/SymfonyService.php` | Service-backed faker refs | FR-ENUM-001 |

## Faker factory & contract

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Faker/FakerInterface.php` | Faker contract | FR-FAKER-001 |
| `Faker/FakerFactoryInterface.php` | Factory contract | FR-FAKER-001 |
| `Faker/FakerFactory.php` | Type → implementation resolver | FR-FAKER-001 |
| `Faker/Example/ExampleCustomFaker.php` | Integrator example | FR-FAKER-002 |

## Faker implementations

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Faker/AddressFaker.php` | Address values | FR-FAKER-002 |
| `Faker/AgeFaker.php` | Age values | FR-FAKER-002 |
| `Faker/BooleanFaker.php` | Boolean values | FR-FAKER-002 |
| `Faker/ColorFaker.php` | Color values | FR-FAKER-002 |
| `Faker/CompanyFaker.php` | Company names | FR-FAKER-002 |
| `Faker/ConstantFaker.php` | Fixed constant | FR-FAKER-002 |
| `Faker/CoordinateFaker.php` | Geo coordinates | FR-FAKER-002 |
| `Faker/CopyFaker.php` | Copy from other field | FR-FAKER-002 |
| `Faker/CountryFaker.php` | Country codes/names | FR-FAKER-002 |
| `Faker/CreditCardFaker.php` | Card numbers | FR-FAKER-002 |
| `Faker/DateFaker.php` | Dates | FR-FAKER-002 |
| `Faker/DniCifFaker.php` | Spanish ID numbers | FR-FAKER-002 |
| `Faker/EmailFaker.php` | Email addresses (optional unique suffix) | FR-FAKER-002, FR-FAKER-003 |
| `Faker/EnumFaker.php` | Enum pick | FR-FAKER-002 |
| `Faker/FileFaker.php` | File paths | FR-FAKER-002 |
| `Faker/HashFaker.php` | Hash replacement | FR-FAKER-002 |
| `Faker/HashPreserveFaker.php` | Preserve hash format | FR-FAKER-002 |
| `Faker/HtmlFaker.php` | HTML snippets | FR-FAKER-002 |
| `Faker/IbanFaker.php` | IBAN values | FR-FAKER-002 |
| `Faker/IpAddressFaker.php` | IP addresses | FR-FAKER-002 |
| `Faker/JsonFaker.php` | JSON payloads | FR-FAKER-002 |
| `Faker/LanguageFaker.php` | Language codes | FR-FAKER-002 |
| `Faker/MacAddressFaker.php` | MAC addresses | FR-FAKER-002 |
| `Faker/MapFaker.php` | Map/coords JSON | FR-FAKER-002 |
| `Faker/MaskingFaker.php` | Partial mask | FR-FAKER-002 |
| `Faker/NameFaker.php` | Person names | FR-FAKER-002 |
| `Faker/NameFallbackFaker.php` | Name with fallback | FR-FAKER-002 |
| `Faker/NullFaker.php` | Null out field | FR-FAKER-002 |
| `Faker/NumericFaker.php` | Numeric values | FR-FAKER-002 |
| `Faker/PasswordFaker.php` | Passwords | FR-FAKER-002 |
| `Faker/PatternBasedFaker.php` | Regex/pattern driven | FR-FAKER-002 |
| `Faker/PhoneFaker.php` | Phone numbers | FR-FAKER-002 |
| `Faker/ServiceFaker.php` | Delegates to service | FR-FAKER-002 |
| `Faker/ShuffleFaker.php` | Shuffle existing | FR-FAKER-002 |
| `Faker/SurnameFaker.php` | Surnames | FR-FAKER-002 |
| `Faker/TextFaker.php` | Lorem/text | FR-FAKER-002 |
| `Faker/UrlFaker.php` | URLs | FR-FAKER-002 |
| `Faker/UsernameFaker.php` | Usernames | FR-FAKER-002 |
| `Faker/UtmFaker.php` | UTM params | FR-FAKER-002 |
| `Faker/UuidFaker.php` | UUIDs | FR-FAKER-002 |

## Domain services

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Service/AnonymizeService.php` | Core anonymization loop (merged row to fakers) | FR-SVC-001 |
| `Service/EntityAnonymizerServiceInterface.php` | Entity anonymizer contract | FR-SVC-001 |
| `Service/PatternMatcher.php` | Attribute pattern matching | FR-SVC-002 |
| `Service/PreFlightCheckService.php` | Preflight validation | FR-SVC-003 |
| `Service/EnvironmentProtectionService.php` | Prod guard | FR-SVC-004 |
| `Service/DatabaseExportService.php` | Export orchestration | FR-SVC-005 |
| `Service/SchemaService.php` | Schema introspection | FR-SVC-005 |
| `Service/AnonymizationHistoryService.php` | Run history | FR-SVC-006 |
| `Service/AnonymizeStatistics.php` | Stats aggregation | FR-SVC-007 |
| `Service/AnonymizeStatisticsDisplay.php` | Stats output formatting | FR-SVC-007 |
| `Service/CommandRunnerInterface.php` | External command runner | FR-SVC-005 |
| `Service/SystemCommandRunner.php` | Symfony Process runner | FR-SVC-005 |

## Helpers & internal

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Helper/DbalHelper.php` | DBAL utilities | FR-HELPER-001 |
| `Helper/OrmHelper.php` | ORM metadata helpers | FR-HELPER-001 |
| `Internal/KernelParameterBagAdapter.php` | Kernel param adapter | FR-INT-001 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Symfony config | 1 | 1 |
| Bundle & DI | 3 | 3 |
| Attributes & trait | 3 | 3 |
| CLI | 7 | 7 |
| Events | 5 | 5 |
| Enums | 2 | 2 |
| Faker (factory + impls) | 40 | 40 |
| Services | 12 | 12 |
| Helpers & internal | 3 | 3 |
| **Total production sources** | **80** | **80** |

## Maintainer tooling (out of `src/`, REQ-GIT-001)

Not shipped in the Packagist archive; mapped for repository traceability.

| Artifact | Spec section | Requirement IDs |
| --- | --- | --- |
| `.scripts/check-no-cursor-coauthor.sh` | Git history audit | REQ-GIT-001 |
| `.scripts/strip-cursor-coauthor-from-history.sh` | History rewrite | REQ-GIT-001 |
| `.githooks/commit-msg` | Strip co-author trailers | REQ-GIT-001 |
| `.cursor/rules/01-git-commits.mdc` | Cursor commit rule | REQ-GIT-001 |
| `.github/workflows/ci.yml` (`git-hygiene` job) | CI gate | REQ-GIT-001 |
| `docs/GITLAB_CI.md` | GitLab CI requirements | REQ-GIT-001, FR-DOCS-001 |
| `docs/TEST_COVERAGE_PROPOSAL.md` | Coverage roadmap | FR-DOCS-003 |

## PHPUnit coverage exclusions

Per [`phpunit.xml.dist`](../../phpunit.xml.dist); integration tests cover orchestration paths.

| Source file | Reason |
| --- | --- |
| `Command/AnonymizeCommand.php` | High-volume CLI branches; integration coverage |
| `Service/AnonymizeService.php` | Batch orchestration; integration coverage |
| `Helper/OrmHelper.php` | Metadata helpers; unit + integration coverage |
