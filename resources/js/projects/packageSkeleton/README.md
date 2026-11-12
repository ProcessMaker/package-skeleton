# Domain Vue screens

Place screen SFCs here using camelCase folder names (e.g. `employeeDashboard/MyScreen.vue`).

After adding or changing components:

1. Register each root and child in `resources/js/designer/public-screen-components.json`.
2. Run `npm run prod` from the package root.
3. Publish assets: `php artisan vendor:publish --tag=<package-name> --force` on the ProcessMaker host.

`rename-project.php` renames this folder from `packageSkeleton` to the camelCase slug (e.g. `demoLeave`).
