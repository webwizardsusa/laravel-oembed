---
title: Installation
weight: 20
description: Install the package, publish what you need, set up the database store and include the stylesheets.
---

## Install the package

```bash
composer require webwizardsusa/laravel-oembed
```

The service provider and the `OEmbed` facade alias are registered by package discovery. Nothing else is required:
with the defaults, answers are kept in your default cache store and only the built-in providers are used.

## Publish the config (optional)

Publish `config/oembed.php` when you want to change the app's defaults, such as the store, how long answers are kept,
the timeout or discovery:

```bash
php artisan vendor:publish --tag=oembed-config
```

Every key is described in the [configuration reference](configuration.md).

## Publish the translations (optional)

The package's messages (the form field's validation errors) live in the `oembed` translation namespace. Publish them
to change the wording or add languages:

```bash
php artisan vendor:publish --tag=oembed-translations
```

## Use the database store (optional)

Answers are kept in a Laravel cache store by default. To keep them in a database table instead, which survives cache
clears and can be inspected, publish the migration and run it.

The migration reads `oembed.database.connection` and `oembed.database.table` **when it runs**, so set them first if
you don't want the default connection or the `oembed_responses` table. Publish the config, set the values, then
migrate:

```bash
php artisan vendor:publish --tag=oembed-config
```

```env
OEMBED_DB_CONNECTION=mysql
```

```bash
php artisan vendor:publish --tag=oembed-migrations
php artisan migrate
```

The table name has no environment variable; change `database.table` in `config/oembed.php` if you need another name.
If you change the connection or table after migrating, the store looks in the new place and the old table is left
as it was.

Then switch the store:

```env
OEMBED_STORE=database
```

Expired rows are ignored when reading, but they stay in the table until you prune them. Schedule the prune command,
for example in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('oembed:prune')->daily();
```

See [Stores](usage/stores.md) for how answers are kept and how to pick a store per lookup.

## Include the stylesheet

Rendered embeds need a little CSS: players that keep their aspect ratio as they scale, responsive photos and captions.
`OEmbed::stylesheet()` returns it as a string, so print it on every page that shows embeds:

```blade
<style>{!! OEmbed::stylesheet() !!}</style>
```

If you prefer a file, copy the rules from `vendor/webwizardsusa/laravel-oembed/resources/css/oembed.css` into your own
stylesheet. The classes it styles are listed in [Styling](extending/styling.md).

## Filament

With Filament installed, the package registers two stylesheets in your panels: the embed styles above and the styles
of the form field's preview card and the table column. Publish them with Filament's asset command, as for any
Filament plugin:

```bash
php artisan filament:assets
```

Run it again after updating the package. Pages in your panels then need nothing else; the components are described
in [Filament](filament/_index.md).

## Content Security Policy

Lookups happen on the server, so they need nothing from a CSP. If your site sends a `Content-Security-Policy` header,
pages that show embeds must allow the providers' players, scripts and images. See
[Content Security Policy](security/content-security-policy.md) for the hosts each built-in provider needs.
