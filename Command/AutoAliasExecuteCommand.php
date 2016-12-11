<?php

namespace Autoalias\Component\Console\Command;

use Autoalias\Component\Console\Helper\AutoAliasComposerHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
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
    $this->addOption('refresh', null, InputOption::VALUE_OPTIONAL, 'Whether or not to refresh the aliases.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Set parameters.
    $composer_json = AutoAliasComposerHelper::findComposerFile();
    $command = $input->getOption('command');
    $params = !empty($input->getOption('params')) ? str_replace('"', '', $input->getOption('params')) : '';
    $refresh = !empty($input->getOption('refresh')) ? $input->getOption('refresh') : 'false';
    $commandline = '';
    $messages = array();
    $output->setFormatter(new OutputFormatter(true));

    // Check if we have an executable of the requested alias in our bin folder.
    if (is_file($composer_json)) {
      $project_path = dirname($composer_json);
      $json = file_get_contents($composer_json);
      $composer = json_decode($json);

      if (!empty($composer->config->{'bin-dir'}) && file_exists($project_path . '/' . $composer->config->{'bin-dir'})) {
        $bin_dir = $project_path . '/' . rtrim($composer->config->{'bin-dir'}, '/');
        $executable = realpath($bin_dir . '/' . $command);
        // Request autoalias refresh after command is executed.
        echo strtok($params);
        if ($command == 'composer' && in_array(strtok($params, ' '), array('install', 'update'))) {
          $refresh = $composer_json;
        }
        if (file_exists($executable)) {
          // @todo: allow for custom params on global and/or project level.
          $commandline = $executable . ' ' . $params;
          $messages[] = '<comment>Executing local <info>' . $executable . '</info></comment>';
        }
        else {
          $commandline = $command . ' ' . $params;
          $messages[] = '<comment>Executing global <info>' . $command . '</info></comment>';
        }
      }
    }
    else {
      $commandline = $command . ' ' . $params;
      $messages[] = '<comment>Executing global <info>' . $command . '</info></comment>';
    }
    $variables = array(
      OutputFormatter::escape('[message]="') . implode('\n', $messages) . '"',
      OutputFormatter::escape('[refresh]="') . $refresh . '"',
      OutputFormatter::escape('[command]="') . $commandline . '"',
    );

    $output->write('( ' . implode(' ', $variables) . ' )');
  }
}
