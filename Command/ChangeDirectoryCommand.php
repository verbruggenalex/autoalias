<?php

namespace Autoalias\Component\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ChangeDirectoryCommand extends Command
{

  protected function configure()
  {
    // Setup command.
    $this->setName('cd');
    $this->setDescription('Change directory replacement for cd command.');
    $this->addArgument('origin', InputArgument::REQUIRED);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Set parameters.
    $origin = $input->getArgument('origin');
    $destination = getcwd();
    $yaml = Yaml::parse(file_get_contents(__DIR__.'/../index.yml'));
    $bash = exec('which bash');
    $home = exec('echo ~');

    // Add composer project root to index, or update it.
    if (file_exists($destination . '/composer.json')) {
      $json = file_get_contents($destination . '/composer.json');
      $composer = json_decode($json);

      if (!empty($composer->config->{'bin-dir'}) && file_exists($composer->config->{'bin-dir'})) {
        $bin_dir = rtrim($composer->config->{'bin-dir'}, '/');
        $yaml['include'][$destination]['bin-dir'] = $bin_dir;
        $updated_yaml = Yaml::dump($yaml, 5);
        file_put_contents(__DIR__.'/../index.yml', $updated_yaml);
      }
    }

    // Load the index.
    $yaml = Yaml::parse(file_get_contents(__DIR__.'/../index.yml'));


    foreach (array_keys($yaml['include']) as $root) {
      if (strpos($destination, $root) !== false  && file_exists($root . '/' . $yaml['include'][$root]['bin-dir'])) {
        $bin_dir = $yaml['include'][$root]['bin-dir'];
        $output->writeln('<info>From: ' . $origin . '</info>');
        $output->writeln('<info>To: ' . $destination . '</info>');
        $files = array_diff(scandir($root . '/' . $bin_dir), array('..', '.'));
        $aliases = array();
        foreach ($files as $filename) {
          $path = realpath($root . '/' . $bin_dir . '/' . $filename);
          switch ($filename) {
            case "phing":
              $options = " -find build.xml";
              break;
            default:
              $options = "";
          }

          $aliases[] = "alias $filename=\"$path$options\"";
        }

        // Set the aliases.
        file_put_contents($home . '/.composer_aliases', implode(PHP_EOL, $aliases));
        // Refresh the bash.
        passthru('/bin/bash');
      }
    }
  }
}