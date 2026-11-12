# Hexagonal layout for project domains

Domains live under `src/Projects/{Pascal}/` with namespace `ProcessMaker\Package\PackageSkeleton\Projects\{Pascal}`. After `rename-project.php`, `PackageSkeleton` becomes the package Pascal name. This layout matches full domain packages built on ProcessMaker; the skeleton ships empty folders and shared `Model` only — no dependency on another composer package.

Script tasks cannot use Laravel; keep that runtime constraint.

## Layers

| Layer          | Allowed to depend on                                      | Role                                                                |
|----------------|-----------------------------------------------------------|---------------------------------------------------------------------|
| Domain         | Shared `Model` only (plus nothing else outside Domain)    | Entities extending `Projects\Shared\Domain\Model`, VOs, ports, schemas |
| Application    | Domain                                                    | Use cases; orchestrate ports; shape process `$data`                 |
| Infrastructure | Domain, Application, RedBean/HTTP helpers                 | Persist and external I/O adapters                                   |

## Script-task rules

- No Laravel service container, no Eloquent, no facades.
- Script tasks return the process data that should be updated.

## Use case shape

No port when the script only maps `$data` (no DB/HTTP):

```php
namespace ProcessMaker\Package\PackageSkeleton\Projects\{Pascal}\Application\UseCases;

final class MapFormData
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
namespace ProcessMaker\Package\PackageSkeleton\Projects\{Pascal}\Application\UseCases;

final class SaveRecord
{
    public function __construct(
        private RecordRepository $records,
    ) {}

    public function handle(array $data): array
    {
        return $data;
    }
}
```

## Ports vs Eloquent

- `src/Models/{Pascal}`: Laravel HTTP, migrations, factories.
- Domain\Ports + adapters: script tasks and any code that must run inside the PM script executor.
- Do not call Eloquent models from Application use cases invoked from scripts.

JSON Schema files live in `src/Projects/{Pascal}/Domain/Schemas/`. Details: [models.md](models.md).

## Reuse (script tasks)

Search `src/Projects/` (especially `Shared/`) **before** adding a class or method. If two use cases would copy the same I/O, extract a **narrow Shared port** + Infrastructure adapter and inject it. Do not add a god `ScriptService` / `Utils`. Do not add Shared ports that are not in the tree yet.

Default wiring: wrapper `isset` fake + `new UseCase($adapter)` or `new UseCase()`. After adding `src/` classes used from a script, rebuild the php-nayra executor.

### Naming and documentation

Names: verb + object (`SaveRecord`, `PatchApplication`). Not `Helper`, `Processor`, `Manager`, `do`, `run`.

Variables: camelCase English words for the **value**. `$institutionId`, `$evaluateRow`. Not cryptic abbrevs (`$instCode`, `$x`).

Process `$data` keys and DB columns stay exact. Locals can be clear, then copy into those keys.

Every public class and public/protected method: English docblock with **what**, **when to call**, **what not to do**, then `@param` / `@return`.

## UseCases for User Screens

Before creating or migrating a screen use case, ask:

- Screen name defined in ProcessMaker
- Use cases of the screen
- Actor
- Description of the screen

Then create or modify PHP services, entities, schemas, and resources (CSS, JS, YAML) in the right place per root [AGENTS.md](../AGENTS.md).
