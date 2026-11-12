# Domain models

Domain entities live under `src/Projects/{Pascal}/Domain/Entities/` and must extend:

`ProcessMaker\Package\PackageSkeleton\Projects\Shared\Domain\Model`

After `rename-project.php`, `PackageSkeleton` becomes the package Pascal name (e.g. `PackageMyApp`).

## Rules

- Not Eloquent. Hydrate from process `$data` arrays; serialize back with `jsonSerialize()`.
- Implement `schemaPath()` returning an absolute path to `Domain/Schemas/{entity}.schema.json`, or `''` to skip validation until the schema exists.
- Validation uses `ValidatesWithJsonSchema` and `swaggest/json-schema` (declared in this package `composer.json`).
- Do not put schemas under `src/Models/{Pascal}/Schemas/`.
- Do not invent a project-local `*Model` base class.

## First entity

1. Add `src/Projects/{Pascal}/Domain/Schemas/{entity}.schema.json`.
2. Add `Domain/Entities/{Entity}.php` with `public static function schemaPath(): string`.
3. Keep HTTP/Eloquent models in `src/Models/{Pascal}` separate from domain entities.
