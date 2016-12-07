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
$ composer global require "verbruggenalex/autoalias:dev-master"
```

### Install the package
Execute the install script from the package. This assumes your home 
directory is the location of your global composer install. So adjust
your path accordingly.
```
$ composer run-script post-install-cmd  ~/.composer/vendor/verbruggenalex/autoalias
```

The result of the installation should look something like this:
```
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
 
This install script will source your .bashrc afterwards, such that it
may become activated.
 
Carefully check the message and/or verify that you have the needed
file and code. Your .bashrc file should have the following appended:
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
 Drush script           :  /var/www/your-project/vendor/drush/drush/drush.php
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
