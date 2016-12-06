<?php

namespace Autoalias\Component\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutoAliasExecuteCommand extends Command
{
  protected function configure()
  {
    // Setup command.
    $this->setName('autoalias:execute');
    $this->setDescription('Execute autoalias.');
    $this->addOption('command', null, InputOption::VALUE_REQUIRED, 'The command to execute.');
    $this->addOption('params', null, InputOption::VALUE_OPTIONAL, 'The parameters for the command.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    //$output = new ConsoleOutput();
    // Set parameters.
    $command = $input->getOption('command');
    $params = $input->getOption('params');
    $composer_json = AutoAliasExecuteCommand::findComposerFile();

    // Check if we have an executable of the requested alias in our bin folder.
    if (is_file($composer_json)) {
      $project_path = dirname($composer_json);
      $json = file_get_contents($composer_json);
      $composer = json_decode($json);

      if (!empty($composer->config->{'bin-dir'}) && file_exists($project_path . '/' . $composer->config->{'bin-dir'})) {
        $bin_dir = rtrim($composer->config->{'bin-dir'}, '/');
        $executable = realpath($project_path . '/' . $bin_dir . '/' . $command);
        if (file_exists($executable)) {
          // Write the path to the executable with it's params.
          $output->writeln($executable . ' ' . $params);
        }
        else {
          $output->writeln($command . ' ' . $params);
        }
      }
    }
    else {
      $output->writeln($command . ' ' . $params);
    }

    exit();
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
  private function findComposerFile($path = '')
  {
    $path = !empty($path) ? $path : getcwd();
    $filename = 'composer.json';
    $filepath = $path . '/' . $filename;
    // If the current folder does not contain the build file, proceed.
    if (!is_file($filepath)) {
      // If we haven't reached root yet, retry in parent folder.
      if (dirname($path) != $path) {
        return $this->findComposerFile(dirname($path));
      }
      else {
        return FALSE;
      }
    }
    // If found return absolute path.
    else {
//      $output->writeln('<info>Succesfully loaded: ' . $filepath . '</info>');
      return $filepath;
    }
  }
}
