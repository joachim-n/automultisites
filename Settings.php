<?php

namespace Automultisites;

/**
 * Contains helper code for use in settings.php.
 */
class Settings {

  /**
   * Populate the settings for a subsite.
   *
   * This should be called from sites/SUBSITE/settings.php thus:
   *
   * @code
   * Automultisites\Settings::configureSiteSettings(
   *   $app_root,
   *   $site_path,
   *   $databases,
   *   $settings,
   *   $config
   * );
   * @endcode
   *
   * To omit any part of the settings, pass an empty array instead of the
   * variable.
   *
   * @param string $app_root
   *   The full path to the Drupal app root, e.g. '/path/to/project/web'.
   * @param string $site_path
   *   The site path. This is the relative path from the Drupal root to the
   *   site folder, e.g. 'sites/local-alpha'.
   * @param array $databases
   *   The databases array. If this already contains a database definition,
   *   the database name has a the site key appended as a suffix.
   * @param array $settings
   *   The settings array.
   *    - The site's files path is set to sites/SUBSITE/files.
   *    - The site's config folder is set to sites/SUBSITE/config/sync.
   * @param array $config
   *   The config array. The site's temporary files path is set to
   *   sites/SUBSITE/files/tmp.
   * @param string $site_dir_prefix
   *   (optional) The prefix used by site folders. Defaults to 'local-'.
   */
  public static function configureSiteSettings(
    $app_root,
    $site_path,
    &$databases,
    &$settings,
    &$config,
    $site_dir_prefix = 'local-'
  ) {
    // Use the default site's settings as a common base.
    // This uses $site_path when including a settings.local.php file, so we
    // need to fake that and protect the real value.
    $real_site_path = $site_path;
    $site_path = 'sites/default';
    if (file_exists($app_root . '/sites/default/settings.php')) {
      include $app_root . '/sites/default/settings.php';
    }

    $site_path = $real_site_path;
    $site_dir = basename($site_path);
    $site_key = substr($site_dir, strlen($site_dir_prefix));

    // If the database details are set, assume the subsite's database name
    // uses a common prefix.
    if (isset($databases['default']['default']['database'])) {
      // Deduce a word separator used in the database name, if any.
      // Fall back to a '_' if the database name is a single word.
      $database_name = $databases['default']['default']['database'];
      $database_separators = preg_replace('@[[:alnum:]]*@', '', $database_name);
      if ($database_separators) {
        $database_separator = substr($database_separators, 0, 1);
      }
      else {
        $database_separator = '_';
      }

      $databases['default']['default']['database'] .= $database_separator . $site_key;
    }

    // Set the config folder to a folder inside the site folder.
    $settings['config_sync_directory'] = $site_path . '/config/sync';

    // Set public and tmp files.
    $settings['file_public_path'] = $site_path . '/files';
    $config['system.file']['path']['temporary'] = $site_path . '/files/tmp';

    // Set the site name for convenience.
    $config['system.site']['name'] = "Subsite $site_key";
  }

}
