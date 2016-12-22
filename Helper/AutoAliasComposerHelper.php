<?php

namespace Autoalias\Component\Console\Helper;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoAliasComposerHelper
{
  /**
   * PhingPropertiesHelper constructor.
   *
   * Setup our input output interfaces.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  function __construct(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;
  }

  /**
   * Returns absolute path to build.xml if found.
   *
   * This function takes the current working directory and searches upwards until the
   * file gets found. When the path remains the same it means we have reached the
   * root of the filesystem and we return false.
   *
   * @param string $path
   *   This is always set with getcwd(). But because of the recursiveness of the
   *   function we can not enter it in the function itself (I think).
   * @return bool|string
   *   False if we reached root without finding it. Absolute path if found.
   */
  public function findComposerFile($path = '')
  {
    $path = !empty($path) ? $path : getcwd();
    $filename = 'composer.json';
    $filepath = $path . '/' . $filename;
    // If the current folder does not contain the build file, proceed.
    if (!is_file($filepath)) {
      // If we haven't reached root yet, retry in parent folder.
      if (dirname($path) != $path) {
        return self::findComposerFile(dirname($path));
      }
      else {
        return FALSE;
      }
    }
    // If found return absolute path.
    else {
      return $filepath;
    }
  }

  /**
   * A recursive helper function to search upwards the directories for executables in
   * a composer project. If none are found it returns the global or original command.
   *
   * @param string $command
   *   The command to search for.
   * @param string $path
   *   The starting path.
   * @return mixed
   *   Returns a full command path or the original string.
   */
  public function retrieveCommand($command, $path = '') {
    $path = !empty($path) ? $path : getcwd();
    $composer_json = self::findComposerFile($path);
    // Get first found composer.json file.
    if ($composer_json && is_file($composer_json)) {
      $project_path = dirname($composer_json);
      $json = file_get_contents($composer_json);
      $composer = json_decode($json);
      $bin_dir = isset($composer->config->{'bin-dir'}) ? rtrim($composer->config->{'bin-dir'}) : "";
      $bin_dir_path = $project_path . '/' . $bin_dir;
      $command_path = $bin_dir_path . '/' . $command;
      // If we have a matching command.
      if (!empty($bin_dir) && file_exists($command_path)) {
        // Return absolute command path.
        return realpath($command_path);
      }
      else {
        // Retry.
        return self::retrieveCommand($command, dirname($project_path));
      }
    }
    else {
      // Return the global command path or original command.
      $global_command = exec('which ' . $command);
      return empty($global_command) ? $command : $global_command;
    }
  }
}
