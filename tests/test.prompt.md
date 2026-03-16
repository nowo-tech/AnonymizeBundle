Act as a senior engineer specialized in PHP, testing, PHPUnit, integration, and incremental coverage improvement.

GOAL
Bring this PHP project’s test coverage to 100% in a real and safe way:
- Do not use coverage ignores.
- Do not remove code to “cheat” coverage.
- Do not change coverage configuration to hide lines.
- Do not use unnecessary mocks that undermine the value of the test.
- Do not break existing behaviour.
- Prioritize only:
  1. classes with no tests
  2. classes with coverage below 100%

IMPORTANT CONTEXT
- I do not have PHP installed locally.
- To run tests and check coverage you must ALWAYS use:
  `make test-coverage`

WORKING RULES
1. Before changing anything, inspect the project and identify:
   - classes with no tests
   - classes with coverage below 100%
   - uncovered lines or branches
   - any reusable data providers, factories, builders, fixtures or helpers
2. Work in small, safe iterations.
3. In each iteration focus on the smallest possible set of classes to keep progress under control.
4. Do not use `@codeCoverageIgnore`, `#[CodeCoverageIgnore]`, configuration exclusions or equivalent strategies.
5. Do not change business logic unless strictly needed to make code testable and improve design; if so:
   - the change must be minimal
   - it must be justified
   - it must not alter functional behaviour
6. If a class is hard to test due to coupling, static dependencies, side effects or non-deterministic code:
   - propose a minimal, safe refactor
   - apply it together with its tests
   - verify that everything still passes
7. Prioritize tests that cover:
   - conditional branches
   - early returns
   - exceptions
   - edge cases
   - nullables
   - empty collections
   - handled errors
   - callbacks, closures and rarely exercised paths
8. Avoid redundant tests. Each test should cover real behaviour or pending lines/branches.
9. After each iteration, run `make test-coverage` and analyse the result.
10. In each iteration explicitly verify:
   - that existing tests are not broken
   - that coverage has improved or at least advanced for the target class
11. If an iteration does not improve coverage, review the approach and fix it before continuing.
12. Do not stop until you have addressed all untested or incompletely covered classes, or until you have clearly identified a real technical blocker.

WORKFLOW
Follow this exact cycle:

PHASE 1. ANALYSIS
- Find classes with no tests or with coverage < 100%.
- Order them by impact and ease of improvement.
- Choose one or a few for the next iteration.

PHASE 2. TEST DESIGN
- Identify which lines, conditions, exceptions or branches are missing.
- Design the minimal tests needed to cover them for real.
- Reuse existing project infrastructure.

PHASE 3. IMPLEMENTATION
- Create or complete the tests.
- If needed, perform minimal refactors to allow testability.
- Keep project style and conventions.

PHASE 4. VALIDATION
- Run:
  `make test-coverage`
- Check that everything passes.
- Check that coverage improves.
- If something fails, fix it before continuing.

PHASE 5. BRIEF REPORT
After each iteration, provide a short summary with:
- classes worked on
- tests added or modified
- branches/lines covered
- refactors performed
- result of `make test-coverage`
- next target

TECHNICAL PRIORITIES
- First: classes with no tests.
- Then: classes with coverage below 100%.
- Then: complex branches within already partially covered classes.
- Pay special attention to:
  - services
  - handlers
  - managers
  - event listeners/subscribers
  - normalizers/transformers
  - value resolvers
  - commands
  - policy/authorization logic
  - utility classes with edge cases

QUALITY CRITERIA
- Readable, deterministic and maintainable tests.
- Clear, descriptive test names.
- No unnecessary duplication.
- No fragile assumptions.
- No sleeps or arbitrary timing unless truly needed.
- Do not touch production code more than strictly necessary.

CONSTRAINTS
- Do not use coverage ignores.
- Do not ask me to run alternative commands unless necessary.
- Use `make test-coverage` as the validation command.
- Do not assume progress; verify it in each iteration.
- Do not stop at recommendations; implement real changes.

DECISION STRATEGY
When several options exist, choose the one that maximizes:
1. real coverage increase
2. safety of the change
3. lower complexity
4. lower regression risk

EXPECTED BEHAVIOUR
Start immediately:
1. inspect the project
2. identify the first target set of classes
3. implement the first iteration
4. run `make test-coverage`
5. analyse the result
6. keep iterating until you approach real global 100%

Very important:
- Do not use artificial shortcuts.
- Do not use ignores.
- Every iteration must end with real validation.
- If there is a technical blocker, explain it precisely and propose the minimal safe solution.
