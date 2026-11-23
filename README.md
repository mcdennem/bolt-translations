# Bolt Translations

A Craft-like translations manager for Bolt CMS. Editors manage translations in the Bolt admin, and the site uses them right away.

Translations are stored in the database instead of `.xlf` files, so deployments and FTP uploads never overwrite changes made by editors.

## Features

- **Translations** menu item in the Bolt admin
- Overview with one column per language, and search on key and translation
- Add, edit and delete translations
- Import existing translations from `translations/*.xlf`, with a button in the admin or a console command
- Database translations take priority; keys that are not in the database fall back to the regular `.xlf` translations

## Requirements

- Bolt CMS 6
- PHP 8.4 or higher

## Installation

```bash
composer require mcdennem/bolt-translations
php bin/console cache:clear
```

The database table is created automatically the first time you open the Translations page or run the import.

## Configuration

On first use, Bolt creates `config/extensions/mcdennem-bolttranslations.yaml`:

```yaml
# Languages that get a column in the Translations overview.
locales:
    - en
    - nl

# Only show keys that start with one of these prefixes, e.g. "label.".
# Leave empty to show all keys.
prefixes: []
```

- **locales**: each language gets a column in the overview, even when it has no translations yet. Languages that only exist in the database are shown as well.
- **prefixes**: limits the overview to your own keys, for example `label.`, and hides form labels and other texts that are not meant to be translated by editors.

Bolt only creates this file once. When you update the extension, new settings are not added automatically: copy them from this README.

## Importing existing translations

Copy the translations from `translations/*.xlf` into the database in one of two ways:

- **In the admin:** open **Translations** and click **Import from .xlf files** at the bottom of the page. The button is only visible to users with the admin role. Use this on shared hosting without SSH access.
- **On the command line:**

```bash
php bin/console translations:import
```

Existing database translations with the same key and language are overwritten by the import. Run it once when installing, not after editors have made changes.

## Usage

Open **Translations** in the Bolt admin menu. The page follows the admin URL of your site (`/bolt`, `/admin`, `/beheer`, …).

In templates, nothing changes:

```twig
{{ 'label.read_more'|trans }}
```

## License

MIT
