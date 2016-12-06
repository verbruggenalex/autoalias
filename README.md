# autoalias
A composer package that enables auto alias generation in composer
projects.

## Installation
It is recommended that you make the installation in your global composer
project. Currently there is no usage for multiple instances.

### Get the package
First off fetch the package. Currently there is no stable version, so 
you will just have the latest master. There are still todo's before I
can release the first version.
```
$ composer require "verbruggenalex/autoalias"
```

### Install the package
Enter the package directory. And perform a composer install.
```
$ cd autoalias
$ composer install
```

The result of the installation should look something like this:
```
Loading composer repositories with package information
Updating dependencies (including require-dev)
  - Installing psr/log (1.0.2)
    Loading from cache

  - Installing symfony/debug (v3.0.9)
    Loading from cache

  - Installing symfony/polyfill-mbstring (v1.3.0)
    Loading from cache

  - Installing symfony/console (v2.8.14)
    Loading from cache

  - Installing symfony/yaml (v2.8.14)
    Loading from cache

symfony/console suggests installing symfony/event-dispatcher ()
symfony/console suggests installing symfony/process ()
Writing lock file
Generating autoload files
> Autoalias\Component\Console\Installer\Installer::postInstall
 ------------------------------------------------------------------------------
 // ~/.autoalias_aliases: file created.
 // ~/.bashrc: autoalias succesfully added.
 ------------------------------------------------------------------------------
 ```
 The important thing is the post installation script. It needs to:
 - create an **.autoalias_aliases** file in your home directory.
 - append the autoalias-function and .autoalias_aliases file inclusion
 in the **.bashrc** file of your home directory.
 
 
 This install script will source your .bashrc afterwards.
 
 So carefully check the message and/or verify that you have the needed
 file and code.
 
 Your .bashrc file should have the following appended:
 ```bash
 # ================================================================================
 # Autoalias function execution. Do not alter.
   function autoalias-function() {
       params=${@:2}
       command=$(php %ROOT_INSTALL_PATH%/autoalias autoalias:execute --command=$1 --params="${params// \ }")
       php %ROOT_INSTALL_PATH%/autoalias autoalias:message --command=${command%% *}
       $command
   }
   if [ -f ~/.autoalias_aliases ]; then
       . ~/.autoalias_aliases
   fi
 # ================================================================================
 ```
Where **%ROOT_INSTALL_PATH%** will be replaced with the location of where
you installed the package. **So moving it will break the functionality!**

When first installed the included .autoalias_aliases file will be copied
to your home directory. This file contains a few presets at the moment:
 ```bash
alias behat='autoalias-function behat'
alias drush='autoalias-function drush'
alias phing='autoalias-function phing'
alias phpcbf='autoalias-function phpcbf'
alias phpcs='autoalias-function phpcs'
 ```
 **Note:** future functionality will be able to automatically add new
 executables to the aliases file.
 
 ## Usage
 If all went well you should now receive a message when using one of
 these aliases in a composer project:
 ```
 $ drush status
  ------------------------------------------------------------------------------
  // Autoalias in use: /var/www/your-project/vendor/drush/drush/drush
  // If you wish to change these settings use the command "autoalias configure".
  ------------------------------------------------------------------------------
  PHP executable         :  /usr/bin/php
  PHP configuration      :  /etc/php/7.0/cli/php.ini
  PHP OS                 :  Linux
  Drush script           :  /var/www/subsite-starterkit/vendor/drush/drush/drush.php
  Drush version          :  8.1.5
  Drush temp directory   :  /tmp
  Drush configuration    :
  Drush alias files      :
 ```
**Note:** The comment about the configuration is yet to be implemented.
**Note:** If running your command has no output it might be you need to
source the .bashrc yourself, for that you can use:
```
$ . ~/.bashrc
```