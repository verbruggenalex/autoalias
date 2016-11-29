<?php
namespace Autoalias\Component\Console\Installer;

class Installer
{

  public static function postInstall() {

    // Get the home folder.
    $home = exec('echo ~');
    $bash = exec('echo which bash');

    // Create our ~/.composer_aliases file.
    Installer::createComposerAliasesFile($home);

    // Create our index.yml file.
    Installer::createComposerIndexFile();

    // Add the replacement cd function to the ~/.bashrc file.
    Installer::addReplacementFunctionToBash($home);

    // Refresh the bash.
    passthru('/bin/bash');

  }

  protected static function createComposerAliasesFile($home) {

    $composer_aliases = $home . '/.composer_aliases';

    if (!is_file($composer_aliases)) {
      if (touch($composer_aliases)) {
        echo "~/.composer_aliases file created.\n";
      }
      else {
        echo "Failed to create ~/.composer_aliases file. Please create one manually\n";
      }
    }
    else {
      echo "The file ~/.composer_aliases does already exists.\n";
    }
  }

  protected static function createComposerIndexFile() {

    $index = getcwd(). '/index.yml';

    if (!is_file($index)) {
      if (touch($index)) {
        echo getcwd() . "/index.yml file created.\n";
      }
      else {
        echo "Failed to create index.yml file. Please create one manually\n";
      }
    }
    else {
      echo "The file index.yml does already exists.\n";
    }
  }

  protected static function addReplacementFunctionToBash($home) {

    $bashrc = $home . '/.bashrc';
    $autoalias_root = getcwd();

    $cd_replacement_function = array(
      'function cd() {',
      '# ================================================================================',
      '# Autoalias function parameter. Do not alter.',
      '  origin=$(pwd)',
      '# ================================================================================',
      '',
      '    # Change the directory.',
      '    builtin cd "$@"',
      '',
      '# ================================================================================',
      '# Autoalias function execution. Do not alter.',
      '  php ' . $autoalias_root . '/autoalias cd $origin',
      '  if [ -f ~/.composer_aliases ]; then',
      '      . ~/.composer_aliases',
      '  fi',
      '# ================================================================================',
      '}',
      ''
    );

    if ($contents = file_get_contents($bashrc)) {
      // Look if the cd function is present in the file.
      if (preg_match('/function cd\(\).*{(.*)}/s', $contents, $matches)) {
        echo "Found function.\n";
        if (preg_match('/# \=+\n# Autoalias function parameter\. Do not alter\.\n(.*)# \=+/s', $matches[1])) {
          echo "Found PARAMETER\n";
        }
        else {
          // @toto: Insert the parameter strings first within function.
        }

        if (preg_match('/# \=+\n# Autoalias function execution\. Do not alter\.\n(.*)# \=+/s', $matches[1])) {
          echo "Found EXECUTION\n";
        }
        else {
          // @toto: Insert the function strings last within function.
        }
      }
      else {
        if (file_put_contents($bashrc, PHP_EOL . implode(PHP_EOL, $cd_replacement_function), FILE_APPEND | LOCK_EX)) {
          echo "Cd function added to ~/.bashrc.\n";
        }
        else {
          echo "Could not add cd function to ~/.bashr. Please add manually.\n";
        }
      }
    }
    else {
      echo "No ~/.bashrc file found.\n";
    }
  }
}