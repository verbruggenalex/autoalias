<?php
namespace Autoalias\Component\Console\Installer;

use Symfony\Component\Console\Output\ConsoleOutput;

class Installer
{

  public static function postInstall() {

    // Get the home folder.
    $home = exec('echo ~');
    $bash = exec('echo which bash');

    $output = new ConsoleOutput();

    $output->writeln('<comment> ------------------------------------------------------------------------------');

    // Create our ~/.autoalias_aliases file.
    Installer::createComposerAliasesFile($home);

    // Add the replacement cd function to the ~/.bashrc file.
    Installer::addReplacementFunctionToBash($home);

    $output->writeln(' ------------------------------------------------------------------------------</comment>');

    // Refresh the bash.
    passthru('/bin/bash');

  }

  public static function preUninstall() {

    // Get the home folder.
    $home = exec('echo ~');
    $bash = exec('echo which bash');
    $bashrc = $home . '/.bashrc';
    $autoalias_aliases = $home . '/.autoalias_aliases';

    $output = new ConsoleOutput();

    $output->writeln('<comment> ------------------------------------------------------------------------------</comment>');

    // @todo: find a way to unalias the set autoaliases.
    if (file_exists($autoalias_aliases) && unlink($autoalias_aliases)) {
      $output->writeln('<comment> // ~/.autoalias_aliases: file deleted.</comment>');
    }
    else {
      $output->writeln('<comment> // ~/.autoalias_aliases: unable to remove, may not exist.</comment>');
    }

    if (file_exists($bashrc) && $contents = file_get_contents($bashrc)) {
      if ($filtered_contents = preg_replace('/# \=+\n# Autoalias function execution\. Do not alter\.\n(.*)# \=+/s', '', $contents)) {
        if ($filtered_contents != $contents) {
          if (file_put_contents($bashrc, $filtered_contents,LOCK_EX)) {
            $output->writeln('<comment> // ~/.bashrc: autoalias code successfully removed.</comment>');
          }
          else {
            $output->writeln('<comment> // ~/.bashrc: failed to remove autalias code. Please do this manually.</comment>');
          }
        }
        else {
          $output->writeln('<comment> // ~/.bashrc: no autoalias code detected that can be removed.</comment>');
        }
      }
      else {
        $output->writeln('<comment> // ~/.bashrc: failed to detect autoalias code. Please remove manually if necessary.</comment>');
      }
    }
    else {
      $output->writeln('<comment> // ~/.bashrc: file not found.</comment>');
    }

    $output->writeln('<comment> ------------------------------------------------------------------------------</comment>');

    // Refresh the bash.
    passthru('/bin/bash');
  }

  protected static function createComposerAliasesFile($home) {

    $input = null;
    $output = new ConsoleOutput();

    $autoalias_aliases = $home . '/.autoalias_aliases';
    $autoalias_root = getcwd();
    $autoalias_aliases_template = $autoalias_root . '/.autoalias_aliases';

    if (!is_file($autoalias_aliases)) {
      if (is_file($autoalias_aliases_template) && copy($autoalias_aliases_template, $autoalias_aliases)) {
        $output->writeln('<comment> // ~/.autoalias_aliases: file created.</comment>');
      }
      else {
        $output->writeln('<comment> // ~/.autoalias_aliases: file creation failed.</comment>');
      }
    }
    else {
      $output->writeln('<comment> // ~/.autoalias_aliases: file already exists.</comment>');
    }
  }

  protected static function addReplacementFunctionToBash($home) {

    $input = null;
    $output = new ConsoleOutput();

    $bashrc = $home . '/.bashrc';
    $autoalias_root = getcwd();
    $autoalias_bashrc = $autoalias_root . '/.autoalias_bashrc';

    if (file_exists($bashrc) && $contents = file_get_contents($bashrc)) {

      if (preg_match('/# \=+\n# Autoalias function execution\. Do not alter\.\n(.*)# \=+/s', $contents)) {
        // @todo: give user the option to switch installation to the new one.
        $output->writeln('<comment> // ~/.bashrc: autoalias already installed.</comment>');
      }
      else {
        $addition = file_exists($autoalias_bashrc) ? file_get_contents($autoalias_bashrc) : FALSE;
        if ($addition) {
          $prepared_addition = str_replace('%ROOT_INSTALL_PATH%', $autoalias_root, $addition);
          if (file_put_contents($bashrc, $prepared_addition, FILE_APPEND | LOCK_EX)) {
            $output->writeln('<comment> // ~/.bashrc: autoalias succesfully added.</comment>');
          }
          else {
            $output->writeln('<comment> // ~/.bashrc: failed to append required code.</comment>');
          }
        }
        else {
          $output->writeln('<comment> // ./.autoalias_bashrc: resource missing!</comment>');
        }
      }
    }
    else {
      $output->writeln('<comment> // ~/.bashrc: file found.</comment>');
    }
  }
}