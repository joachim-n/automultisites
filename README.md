Automultisites
==============

This is a collection of helpers for quickly creating new subsites in a local
Drupal project. It can often be useful to create a new site using a project's
codebase, either to debug or prototype on a fresh install, or to quickly switch
between different copies of the database.

The subsites use subfolders, so there is no need for creating new virtual hosts.

A symlink in the Drupal root back to itself allows Drupal to function as if it
were in a subfolder, which allows the multisite system to consider it a
different site.

Compatibility
-------------

- The Drush command requires Drush ^10.5.
- This is not entirely compatible with the
  joachim-n/drupal-core-development-project Composer project template.

Terminology
-----------

Each subsite has a *site directory*, which is its subdirectory in `sites/`.
These have a common prefix, which defaults to `local-`. The part after the
prefix is referred to as the *site key*, so for example with this directory
listing:

- default
- local-alpha
- local-beta

the site keys are 'alpha' and 'beta'.

Installation
------------

Install with Composer: `composer require joachim-n/automultisites`.

Drush command
-------------

To create a new subsite with Drush, do:

```
$ drush multisite:new SITE-KEY
```

Manual instructions
-------------------

Without Drush, do the following:

1. Copy sites/example.sites.php to sites/sites.php if it does not already exist
2. In sites.php, add the following code:

```
$sites = []; // Necessary workaround for Drush.
\Automultisites\Sites::addLocalSites($sites, $app_root);
```

3. Create a symlink of the Drupal root back to itself:

```
$ cd web
$ ln -s . local-alpha
```

4. Create a new site folder:

```
$ cd web/sites
$ mkdir local-alpha
```

5. Copy settings.php to it:

```
$ cp web/sites/default/default.settings.php web/sites/local-alpha/settings.php
```

6. In the new settings.php file, add the following code:

```
// Use the default site's settings as a common base.
if (file_exists($app_root . '/sites/default/settings.php')) {
include $app_root . '/sites/default/settings.php';
}

Automultisites\Settings::configureSiteSettings(
$site_path,
$databases,
$config_directories,
$settings,
$config
);
```

7. To create a Drush site alias, create a file drush/sites/local.site.yml and
   add the following to it:

```
alpha:
    root: /path/to/project/web
    uri: localhost.local-alpha
```
