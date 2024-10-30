<?php

namespace Automultisites;

use Drush\Drush;

/**
 * Contains helper code for use in sites.php.
 */
class Sites {

  /**
   * Add site alises based on local directories.
   *
   * This should be called from sites/sites.php thus:
   *
   * @code
   *  // This needs to be defined as a workaround for Drush.
   *  $sites = [];
   *  \Automultisites\Sites::addLocalSites($sites, $app_root);
   * @endcode
   *
   * @param array &$sites
   *   The sites directory to populate.
   * @param string $app_root
   *   The absolute path to the app root. In Drupal's sites.php this is a
   *   locally-defined variable, as the file is included by defined in
   *   DrupalKernel::findSitePath() which defines it.
   *   (This is only optional as a workaround for the Drush site-install
   *   command.)
   * @param string $site_dir_prefix
   *   (optional) The prefix used for names of site directories in sites/.
   *   Defaults to 'local-', so your sites directories would be sites/local-foo/,
   *   sites/local-bar/, etc
   * @param string $alias_prefix
   *   (optional) The prefix for the aliases in the root directory. Defaults to
   *   an empty string, so the site directory name is expected to match the
   *   alias name.
   * @param string $server_prefix
   *   (optional) The server part of the URL to use in the site alias. Defaults
   *   to $_SERVER['HTTP_HOST'].
   */
  public static function addLocalSites(array &$sites, string $app_root = NULL, string $site_dir_prefix = 'local-', string $alias_prefix = '', string $server_prefix = NULL) {
    $server_prefix ??= $_SERVER['HTTP_HOST'];

    // Workaround for Drush site-install command, which includes sites.php
    // but doesn't provide it with the same environment as DrupalKernel.
    if (empty($app_root)) {
      $app_root = Drush::bootstrapManager()->getRoot();
    }

    $sites_dir = $app_root . '/sites';

    if (php_sapi_name() == "cli") {
      // Running on the command line, could be Drush or PHPUnit.
      if (isset($_SERVER['SIMPLETEST_BASE_URL'])) {
        // Running PHPUnit tests on the command line.
        $site_url = $_SERVER['SIMPLETEST_BASE_URL'];
      }
      else {
        // Running on Drush.
        $drush_args = $_SERVER['argv'];

        // By the time we get here, Drush has populated the command arguments with
        // the site URI from its site aliases, so we can use that.
        foreach ($drush_args as $arg) {
          if (preg_match('@^--uri=@', $arg)) {
            $argv_site = $arg;
            break;
          }
        }
        $site_url = preg_replace('@^--uri=@', '', $argv_site);
      }

      $base_dir = parse_url($site_url, PHP_URL_PATH);
    }
    else {
      // Running in the browser.
      // Get the base URL of the site, to form the sites.php alias.
      $base_dir = dirname($_SERVER['SCRIPT_NAME']);

      // Trim off so this works during install, when the script name ends in
      // 'core/install.php'.
      $base_dir = preg_replace('@/core(/.+)?$@', '', $base_dir);
    }

    // Trim off the local directory alias, in both environments.
    $base_dir = preg_replace('@/' . $site_dir_prefix . '.+$@', '', $base_dir);

    $base_dir_pieces = array_filter(explode('/', $base_dir));

    $files = scandir($sites_dir);
    foreach ($files as $file) {
      // Skip non-directories. is_dir() can apparently fail if not given the
      // full path.
      if (!is_dir($sites_dir . '/' . $file)) {
        continue;
      }

      // Skip directories that don't have the specified prefix.
      if (substr($file, 0, strlen($site_dir_prefix)) !== $site_dir_prefix) {
        continue;
      }

      // Skip dot directories. These will still be here if $site_dir_prefix is
      // an empty string.
      if (substr($file, 0, 1) == '.') {
        continue;
      }

      $site_directory_pieces = $base_dir_pieces;
      $site_directory_pieces[] = $alias_prefix . $file;

      $site_directory = $server_prefix . '.' . implode('.', $site_directory_pieces);

      $sites[$site_directory] = $file;
    }
  }

}
