# Processmaker Package Skeleton
This package provides the necessary base code to start the developing a package in ProcessMaker 4.

## Create project (recommended)

```bash
composer create-project --stability=dev processmaker/package-skeleton:dev-feature/FOUR-34328 my-package
```

The post-install wizard asks for **Migration** or **PM4 only**, then **ProcessMaker URL**, **API token** (optional), writes `.mcp.json` (processes + screens), `.env.example`, and installs skills.

Non-interactive:

```bash
PM_SKILLS_PROFILE=migration NAYRA_SERVER=https://pm.example.com PM_SYNC_TOKEN=… \
  composer create-project --stability=dev processmaker/package-skeleton:dev-feature/FOUR-34328 my-package
```

## Development
If you need to create a new ProcessMaker package run the following commands:

```
git clone https://github.com/ProcessMaker/package-skeleton.git
cd package-skeleton
php rename-project.php package-skeleton
composer install
npm install
npm run dev
```

## Installation
* Use `composer require processmaker/package-skeleton` to install the package.
* Use `php artisan package-skeleton:install` to install generate the dependencies.

## Navigation and testing
* Navigate to administration tab in your ProcessMaker 4
* Select `Skeleton Package` from the administrative sidebar

## Uninstall
* Use `php artisan package-skeleton:uninstall` to uninstall the package
* Use `composer remove processmaker/package-skeleton` to remove the package completely
