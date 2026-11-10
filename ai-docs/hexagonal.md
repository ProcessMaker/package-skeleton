# Hexagonal layout for TCE domains

Military today is a transitional design: `src/Military/*Actions.php` plus RedBean (`Military\DB`) and a small `app()` container because **script tasks cannot use Laravel**. New domains keep that runtime constraint but live under `src/Projects/{Pascal}/` with namespace `ProcessMaker\Package\PackagePlg\Projects\{Pascal}`. Do not add new hexagons next to `src/Military/`.

## Layers

| Layer               | Allowed to depend on                          | Role                                                |
|---------------------|-----------------------------------------------|-----------------------------------------------------|
| Domain              | Shared `Model` only (plus nothing else outside Domain) | Entities extending `Projects\Shared\Domain\Model`, VOs, ports, JSON Schemas |
| Application         | Domain                                        | Use cases; orchestrate ports; shape process `$data` |
| Infrastructure      | Domain, Application, RedBean, PM helpers      | Persist, match SQL, PM container                    |

## Script-task rules

- No Laravel service container, no Eloquent, no facades.
- Script Tasks can return the data that could be updated.

## Use case shape

No port when the script only maps `$data` (no DB/HTTP):

```php
namespace ProcessMaker\Package\PackagePlg\Projects\{Pascal}\Application\UseCases;

final class MapTranscriptFormData
{
    /**
     * Shape process $data for the script task. No I/O.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function handle(array $data): array
    {
        return $data;
    }
}
```

Port only when there is DB/HTTP. Constructor-inject a Domain port; adapter lives in Infrastructure:

```php
namespace ProcessMaker\Package\PackagePlg\Projects\{Pascal}\Application\UseCases;

final class ExecuteTranscriptActions
{
    public function __construct(
        private TranscriptRepository $transcripts,
    ) {}

    public function handle(array $data): array
    {
        return $data;
    }
}
```
## Ports vs Eloquent

- `src/Models/{Pascal}`: Laravel HTTP, migrations, factories.
- Domain\Ports + RedBean adapters: script tasks and any code that must run inside the PM script executor.
- SQL from the executor: bound parameters and backtick identifiers (`groups`, `status`, `rank`, …). Unquoted reserved words → MySQL 1064.

Do not call Eloquent models from Application use cases if those use cases are invoked from scripts.

JSON Schema files live in `src/Projects/{Pascal}/Domain/Schemas/`. Details and rejected locations: [models.md](models.md).

## Reuse (script tasks)

Search `src/Projects/` (especially `Shared/`), `src/Military/`, and `src/Services/` **before** adding a class or method. Call what exists. If two use cases would copy the same I/O (collection PATCH, student SIS lookup, RedBean query helper), extract a **narrow Shared port** + Infrastructure adapter and inject it. Do not add a god `ScriptService` / `Utils`. Do not add Shared ports that are not in the tree yet.

`app(Foo::class)` is the project `ScriptTaskContainer` (not Laravel), and only if that domain already has it. Default: wrapper `isset` fake + `new UseCase($adapter)` or `new UseCase()`. It does not load classes missing from the Nayra image. After adding `src/` classes used from a script, rebuild the php-nayra executor.

### Naming and documentation

Names: verb + object (`SaveInCollectionV3`, `patchApplicationRecord`). Not `Helper`, `Processor`, `Manager`, `do`, `run`.

Variables: camelCase English words for the **value**. `$institutionId`, `$evaluateRow`, `$coursePair`, `$processMakerUserId`. Not APV3 letters (`$x`, `$c`, `$rev`) or cryptic abbrevs (`$instCode`, `$userIdPm`, `$sides`, `$art`, `$his`). Maps: `$courseIdByInternalSubject`, not `$map`.

Process `$data` keys and DB columns stay exact (`OKCourses`, `compount_id`). Locals can be clear, then copy into those keys.

Every public class and public/protected method: English docblock that states **what**, **when to call**, **what not to do**, then `@param` / `@return`. The next refactor should reuse from that text instead of duplicating.

## Example Mapping Military → hexagonal

| Military file                            | New place (`src/Projects/{Pascal}/...`)               |
|------------------------------------------|-------------------------------------------------------|
| `src/Military/JoinTranscriptsAction.php` | `Application/UseCases/JoinTranscripts.php`            |
| `src/Military/ExecuteDataActions.php`    | `Application/UseCases/ExecuteDataActions.php`         |
| `src/Military/DB.php`                    | `Infrastructure/Persistence/RedBeanConnection.php`    |
| `src/Models/Military/*Model.php` | `Domain/Entities/*.php` extending Shared `Model` |
| `src/Models/Military/Schemas/*.schema.json` | `Domain/Schemas/*.schema.json` |


## UseCases for User Screens

Before to create or migrate a screen use case ask the following to the developer:

Input: Screen Name defined in processmaker
Input: Use Cases of the screen
Input: Actor
Input: Description of the screen

Based on this information create or modify the php services, entities, schemas, also the resources like css, js and yaml in the right place. Screen YAML + Vue: [SKILL.md](SKILL.md) §4. Shared Vue catalog: `ai-docs/military/architecture/frontend.md`.

