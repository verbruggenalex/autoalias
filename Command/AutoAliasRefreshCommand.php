<?php

namespace Autoalias\Component\Console\Command;

use Autoalias\Component\Console\Helper\AutoAliasComposerHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutoAliasRefreshCommand extends Command
{
  protected function configure()
  {
    // Setup command.
    $this->setName('autoalias:refresh');
    $this->setDescription('Refresh autoalias aliases.');
    $this->addOption('composer-json', null, InputOption::VALUE_OPTIONAL, 'The composer project to look for new aliases.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Set parameters.
    $composer_json = !empty($input->getOption('composer-json')) ? $input->getOption('composer-json') : AutoAliasComposerHelper::findComposerFile();
    $output->setFormatter(new OutputFormatter(true));

    // Check if we have an executable of the requested alias in our bin folder.
    if (is_file($composer_json)) {
      $project_path = dirname($composer_json);
      $json = file_get_contents($composer_json);
      $composer = json_decode($json);

      if (!empty($composer->config->{'bin-dir'}) && file_exists($project_path . '/' . $composer->config->{'bin-dir'})) {
        $bin_dir = $project_path . '/' . rtrim($composer->config->{'bin-dir'}, '/');
        $files = array_slice(scandir($bin_dir), 2);
        AutoAliasRefreshCommand::buildAliases($files);
        $files_string = implode(', ', $files);
        $output->writeln('<comment>Active autoaliases: <info>' . $files_string . '</info></comment>');
      }
    }
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
      return $filepath;
    }
  }

  private function buildAliases($aliases) {
    $home = exec('echo ~');
    $autoalias_aliases = $home . '/.autoalias_aliases';
    if ($contents = file_get_contents($autoalias_aliases)) {
      foreach ($aliases as $alias) {
        if (!preg_match("~alias " . $alias . "='autoalias-function " . $alias . "'~", $contents)) {
          file_put_contents($home . '/.autoalias_aliases', "alias " . $alias . "='autoalias-function " . $alias . "'\n", FILE_APPEND);
        }
      }
    }
  }
}
