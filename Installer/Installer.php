<?php
namespace Autoalias\Component\Console\Installer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Installer
{

  public static function postInstall() {

    // Get the home folder.
    $home = exec('echo ~');
    $bash = exec('echo which bash');

    $input = null;
    $output = new ConsoleOutput();

    $output->writeln('<comment> ------------------------------------------------------------------------------');

    // Create our ~/.composer_aliases file.
    Installer::createComposerAliasesFile($home);

    // Add the replacement cd function to the ~/.bashrc file.
    Installer::addReplacementFunctionToBash($home);

    $output->writeln(' ------------------------------------------------------------------------------</comment>');

    // Refresh the bash.
    passthru('/bin/bash');

    exit();

  }

  protected static function createComposerAliasesFile($home) {

    $input = null;
    $output = new ConsoleOutput();

    $composer_aliases = $home . '/.composer_aliases';

    if (!is_file($composer_aliases)) {
      if (touch($composer_aliases)) {
        $output->writeln('<comment> // ~/.composer_aliases: file created.</comment>');
      }
      else {
        $output->writeln('<comment> // ~/.composer_aliases: file creation failed.</comment>');
      }
    }
    else {
      $output->writeln('<comment> // ~/.composer_aliases: file already exists.</comment>');
    }
  }

  protected static function addReplacementFunctionToBash($home) {

    $input = null;
    $output = new ConsoleOutput();

    $bashrc = $home . '/.bashrc';
    $autoalias_root = getcwd();

    $cd_replacement_function = array(
      '',
      '# ================================================================================',
      '# Autoalias function execution. Do not alter.',
      '  function autoalias-function() {',
      '      params=${@:2}',
      '      command=$(php -f ' . $autoalias_root . '/autoalias autoalias:execute --command=$1 --params="${params// \ }")',
      '      php ' . $autoalias_root . '/autoalias autoalias:message --command=${command%% *}',
      '      $command',
      '  }',
      '  if [ -f ~/.composer_aliases ]; then',
      '      . ~/.composer_aliases',
      '  fi',
      '# ================================================================================',
      ''
    );

    if ($contents = file_get_contents($bashrc)) {
      if (preg_match('/# \=+\n# Autoalias function execution\. Do not alter\.\n(.*)# \=+/s', $contents)) {
        // @todo: give user the option to switch installation to the new one.
        $output->writeln('<comment> // ~/.bashrc: autoalias already installed.</comment>');
      }
      else {
        if (file_put_contents($bashrc, PHP_EOL . implode(PHP_EOL, $cd_replacement_function), FILE_APPEND | LOCK_EX)) {
          $output->writeln('<comment> // ~/.bashrc: autoalias succesfully added.</comment>');
        }
        else {
          $output->writeln('<comment> // ~/.bashrc: failed to append required code.</comment>');
        }
      }
    }
    else {
      $output->writeln('<comment> // ~/.bashrc: file found.</comment>');
    }
  }
}