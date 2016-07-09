<?hh
/*
 *  Copyright (c) 2014, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

abstract class Drupal8Target extends PerfTarget {
  public function __construct(protected PerfOptions $options) {}

  protected function getSanityCheckString(): string {
    return 'Read more';
  }

  public function install(): void {
    $src_dir = $this->options->srcDir;
    if ($src_dir) {
      Utils::CopyDirContents($src_dir, $this->getSourceRoot());
    } else {
      # Extract Drupal core.
      Utils::ExtractTar(
        __DIR__.'/drupal-8.0.0-rc1.tar.gz',
        $this->options->tempDir,
      );
      # Extract Drush and its vendor dir.
      Utils::ExtractTar(
        __DIR__.'/drush-4017fe6.tar.bz2',
        $this->options->tempDir,
      );
      Utils::ExtractTar(
        __DIR__.'/drush-4017fe6-vendor.tar.bz2',
        $this->options->tempDir,
      );
      # Extract static files.
      Utils::ExtractTar(
        __DIR__.'/demo-static.tar.bz2',
        $this->getSourceRoot().'/sites/default',
      );
    }
    # Settings files and our Twig template setup script.
    copy(
      __DIR__.'/settings/settings.php',
      $this->getSourceRoot().'/sites/default/settings.php',
    );
    copy(
      __DIR__.'/settings/compile.php',
      $this->getSourceRoot().'/sites/default/compile.php',
    );
    copy(
      __DIR__.'/settings/services.yml',
      $this->getSourceRoot().'/sites/default/services.yml',
    );

    # Installing the database is left to the child targets.
  }

  public function getSourceRoot(): string {
    return $this->options->tempDir.'/drupal-8.0.0-rc1';
  }

  public function drushPrep(): void {
    // For repo.auth mode to work, we need Drush to run a setup script that
    // populates Twig template files.
    $hhvm = shell_exec('which hhvm');
    if ($hhvm) {
      putenv("DRUSH_PHP=$hhvm");
    }
    $drush = $this->options->tempDir.'/drush/drush';
    $current = getcwd();
    chdir($this->getSourceRoot());

    // Rebuild Drupal's cache to clear out stale filesystem entries.
    shell_exec($drush.' cr');
    if (is_dir($this->getSourceRoot().'/core/modules/dynamic_page_cache')) {
      shell_exec($drush.' en dynamic_page_cache -y');
    }
    // Try to pre-generate all Twig templates.
    shell_exec($drush.' scr sites/default/compile.php 2>&1');
    chdir($current);
  }

}
