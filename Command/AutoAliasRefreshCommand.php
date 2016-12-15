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
   * Helper function to put the aliases in the ~/.autoalias_aliases file.
   *
   * @param array $aliases
   *   Array of filenames to make an alias for.
   */
  private function buildAliases($aliases) {
    $home = exec('echo ~');
    $autoalias_aliases = $home . '/.autoalias_aliases';
    $autoalias_strings = array();
    if ($contents = file_get_contents($autoalias_aliases)) {
      foreach ($aliases as $alias) {
        if (!preg_match("~alias " . $alias . "='autoalias-execute " . $alias . "'~", $contents)) {
          $autoalias_strings[] = "alias " . $alias . "='autoalias-execute " . $alias . "'";

        }
      }
    }
    if (!empty($autoalias_strings)) {
      file_put_contents($home . '/.autoalias_aliases', implode(PHP_EOL, $autoalias_strings), FILE_APPEND);
    }
  }
}
