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
      # Set command and params.
      command=${@:1:1}
      params=${@:2}
      # Request return variables.
      declare -A return=$(php %ROOT_INSTALL_PATH%/autoalias autoalias:execute --command="${command}" --params="${params// \ }")
      # Output message if needed.
      if [ "${return[message]}" != "" ]; then
          echo ${return[message]}
      fi
      # Execute command.
      ${return[command]}
      # Refresh autoaliases if needed.
      if [ "${return[refresh]}" != "false" ]; then
          php %ROOT_INSTALL_PATH%%/autoalias autoalias:refresh --composer-json="${return[refresh]}"
          . ~/.autoalias_aliases
      fi
  }
  # Include our autoalias_aliases.
  if [ -f ~/.autoalias_aliases ]; then
      . ~/.autoalias_aliases
  fi
# ================================================================================
 ```
Where **%ROOT_INSTALL_PATH%** will be replaced with the location of where
you installed the package. **So moving it will break the functionality!**

When first installed the included .autoalias_aliases file will be copied
to your home directory. This file contains one preset:
```bash
alias composer='autoalias-function composer'
```
**Note:** When executing `composer install` or `composer update` from
within a composer project it will add any aliases from its bin folder
that are not present yet in ~/.autoalias_aliases.
 
## Usage
If all went well you should now receive a message when using one of
these aliases in a composer project:
```
$ drush status
Executing local /var/www/your-project/vendor/drush/drush/drush
 PHP executable         :  /usr/bin/php
 PHP configuration      :  /etc/php/7.0/cli/php.ini
 PHP OS                 :  Linux
 Drush script           :  /var/www/your-project/vendor/drush/drush/drush.php
 Drush version          :  8.1.5
 Drush temp directory   :  /tmp
 Drush configuration    :
 Drush alias files      :
```

**Note:** If running your command has no output it might be you need to
source the .bashrc yourself, for that you can use:
```
$ . ~/.bashrc
```
