<?php

namespace Automultisites\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drush\Drush;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides a Drush command.
 */
class SitesDrushCommands extends DrushCommands {

  /**
   * Sets up a new subsite.
   *
   * This does the following:
   *  - creates a symlink from the web root to itself
   *  - creates a site folder with a new settings.php file
   *  - adds a Drush site alias to drush/sites/local.site.yml
   *
   * @command multisite:new
   * @param $site_key
   *   The key for the new subsite, without the common prefix.
   * @option prefix The prefix for the folder names.
   */
  public function newSubsite(string $site_key, $options = ['prefix' => 'local-']) {
    $web_root  = Drush::bootstrapManager()->getRoot();
    $project_root = dirname($web_root);
    $site_folder = $options['prefix'] . $site_key;

    if (file_exists($web_root  . '/sites/' . $site_folder)) {
      $this->io()->error("Site folder $site_folder already exists.");
      $fail = TRUE;
    }
    if (file_exists($web_root  . '/' . $site_folder)) {
      $this->io()->error("File $site_folder already exists in the Drupal root.");
      $fail = TRUE;
    }
    if (!empty($fail)) {
      return;
    }

    // Ensure sites.php, and append code to it.
    if (!file_exists($web_root  . '/sites/sites.php')) {
      copy($web_root  . '/sites/example.sites.php', $web_root  . '/sites/sites.php');

      $sites_php_code = <<<'EOT'

      // This needs to be defined as a workaround for Drush.
      $sites = [];

      \Automultisites\Sites::addLocalSites($sites, $app_root);

      EOT;

      file_put_contents($web_root  . '/sites/sites.php', $sites_php_code, \FILE_APPEND);
    }

    symlink('.', $web_root  . '/' . $site_folder);

    mkdir($web_root  . '/sites/' . $site_folder);
    copy($web_root  . '/sites/default/default.settings.php', $web_root  . '/sites/' . $site_folder . '/settings.php');

    // Append code to the subsite's settings.php file.
    $settings_php_code = <<<'EOT'

    Automultisites\Settings::configureSiteSettings(
      $app_root,
      $site_path,
      $databases,
      $settings,
      $config
    );

    EOT;
    file_put_contents($web_root  . '/sites/' . $site_folder . '/settings.php', $settings_php_code, \FILE_APPEND);

    // Ensure a drush sites file exists, and stop here if we can't create one.
    if (!file_exists($project_root . '/drush/sites/local.site.yml')) {
      if (!file_exists($project_root . '/drush/sites')) {
        $result = mkdir($project_root . '/drush/sites', 0777, TRUE);
        if (!$result) {
          $this->io()->warning("Unable to create folder 'drush/sites'.");
          return;
        }
      }

      $result = file_put_contents($project_root . '/drush/sites/local.site.yml', '');
      if ($result === FALSE) {
        $this->io()->warning("Unable to create file 'drush/sites/local.site.yml'.");
        return;
      }
    }

    // Try to find an existing site alias definition, so we can get the uri
    // from it.
    $site_definitions = Yaml::parseFile($project_root . '/drush/sites/local.site.yml');
    foreach ($site_definitions as $site_definition) {
      if (strpos($site_definition['uri'], $options['prefix']) !== FALSE) {
        $base_uri = substr($site_definition['uri'], 0, strpos($site_definition['uri'], $options['prefix']) + strlen($options['prefix']));
        break;
      }
    }
    // Fall back to something generic: match the key in the $sites array
    // returned by \Automultisites\Sites::addLocalSites().
    if (!isset($base_uri)) {
      $base_uri = 'localhost.local-';
    }

    $site_definitions[$site_key] = [
      'root' => $web_root,
      'uri' => $base_uri . $site_key,
    ];

    $yaml = Yaml::dump($site_definitions);
    file_put_contents($project_root . '/drush/sites/local.site.yml', $yaml);

    $this->io()->text(dt("New subsite created with site folder '$site_folder'."));
  }

}
