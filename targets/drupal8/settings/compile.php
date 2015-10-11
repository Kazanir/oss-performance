<?php

function oss_performance_twig_compile() {
  // Make our own function to be portable across Drush copies.
  require_once DRUSH_DRUPAL_CORE . "/themes/engines/twig/twig.engine";
  // Scan all enabled modules and themes.
  // @todo Refactor to not reuse commandfile paths directly.
  $boot = drush_get_bootstrap_object();
  $searchpaths = $boot->commandfile_searchpaths(DRUSH_BOOTSTRAP_DRUPAL_FULL);
  foreach (array_keys(system_list('theme')) as $theme) {
    $searchpaths[] = drupal_get_path('theme', $theme);
  }
  drush_print_r($searchpaths);
  // Some functions like attach_library call render without a context so errors
  // are thrown. Setup a dummy render context to avoid this.
  $context = new \Drupal\Core\Render\RenderContext();
  \Drupal::getContainer()->get('renderer')->executeInRenderContext($context, function() use ($searchpaths) {
    foreach ($searchpaths as $searchpath) {
      foreach ($file = drush_scan_directory($searchpath, '/\.html.twig/', array('tests')) as $file) {
        $relative = str_replace(drush_get_context('DRUSH_DRUPAL_ROOT'). '/', '', $file->filename);
        // @todo Dynamically disable twig debugging since there is no good info there anyway.
        twig_render_template($relative, array('theme_hook_original' => ''));
        drush_log(dt('Compiled twig template !path', array('!path' => $relative)), 'notice');
        // Now do the file without any path at all.
        $onlyfile = $file->basename;
        twig_render_template($onlyfile, array('theme_hook_original' => ''));
        drush_log(dt('Compiled twig template !path', array('!path' => $onlyfile)), 'notice');
      }
    }
  });
}

oss_performance_twig_compile();

