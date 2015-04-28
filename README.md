# Versionator

A tool that helps with reviewing the state of your composer.json 
dependencies for SilverStripe projects. 

The motivation behind this tool is for projects to have a well
defined version set of modules defined in the composer.json for that project. 


## Features

* List current module versions, along with the latest version available on 
  packagist for that module
* Output a 'recommended' set of dependencies in composer format
* Check modules to determine whether there are changes in `master` that 
  haven't been incorporated into a tag (ie whether the latest stable _may_ 
  not have the latest code) 

## Usage

`php index.php -f /path/to/project/composer.json`

Will output the list of modules and their latest versions in packagist

**Options**

* **--modules=comma,separated,list** - A list of modules in the project 
  to also include the composer.json settings for deeper inspection
* **--version-fix** - Whether to output a 'recommended' composer .json 
  that will provide _fixed_ versions to be bound to
* **--check-git=true** Whether to check git repositories for differences 
  between master and the latest tagged version of modules


## Installing

If you wish to run this from anywhere, create a shell script similar to

```
#!/bin/bash
php /home/user/path/to/versionator/index.php "$@"
```

and place it in your */bin folder


## Example outputs

Default output

```
$ versionator.sh -f /home/marcus/www/projects/cycle-int/composer.json 

Retrieved package silverstripe-australia/build

Retrieved package silverstripe-australia/ssautesting

Retrieved package silverstripe-australia/memberprofiles

Retrieved package silverstripe/restrictedobjects

Retrieved package silverstripe/frontend-dashboards

Retrieved package silverstripe/microblog

✘ silverstripe-australia/build has an updated, fixed tag version available 1.2.3 (currently ~1.0)
✘ silverstripe-australia/ssautesting has an updated, fixed tag version available 1.1.1 (currently ~1.0)
✘ silverstripe-australia/memberprofiles has an updated, fixed tag version available 1.1.1 (currently 1.1.*)
✘ silverstripe/restrictedobjects has an updated, fixed tag version available 2.1.5 (currently 2.1.*)
✘ silverstripe/frontend-dashboards has an updated, fixed tag version available 1.2.2 (currently ~1.2.0)
✘ silverstripe/microblog has an updated, fixed tag version available 1.9.3 (currently ~1.9.0)
```

Outputting the list of fixed versions

```
$ versionator.sh -f /home/marcus/www/projects/cycle-int/composer.json --version-fix=true

Retrieved package silverstripe-australia/build

Retrieved package silverstripe-australia/ssautesting

Retrieved package silverstripe-australia/memberprofiles

Retrieved package silverstripe/restrictedobjects

Retrieved package silverstripe/frontend-dashboards

Retrieved package silverstripe/microblog

✘ silverstripe-australia/build has an updated, fixed tag version available 1.2.3 (currently ~1.0)
✘ silverstripe-australia/ssautesting has an updated, fixed tag version available 1.1.1 (currently ~1.0)
✘ silverstripe-australia/memberprofiles has an updated, fixed tag version available 1.1.1 (currently 1.1.*)
✘ silverstripe/restrictedobjects has an updated, fixed tag version available 2.1.5 (currently 2.1.*)
✘ silverstripe/frontend-dashboards has an updated, fixed tag version available 1.2.2 (currently ~1.2.0)
✘ silverstripe/microblog has an updated, fixed tag version available 1.9.3 (currently ~1.9.0)
Recommended project composer requirements: 

		"silverstripe-australia/build": "1.2.3",
		"silverstripe-australia/ssautesting": "1.1.1",
		"silverstripe-australia/memberprofiles": "1.1.1",
		"silverstripe/restrictedobjects": "2.1.5",
		"silverstripe/frontend-dashboards": "1.2.2",
		"silverstripe/microblog": "1.9.3",


```


Adding additional modules to dig into from the project (note the extra modules included). 

```
$ versionator.sh -f /home/marcus/www/projects/cycle-int/composer.json --modules=microblog

Retrieved package silverstripe-australia/build

Retrieved package silverstripe-australia/ssautesting

Retrieved package silverstripe-australia/memberprofiles

Retrieved package silverstripe/restrictedobjects

Retrieved package silverstripe/frontend-dashboards

Retrieved package silverstripe/microblog

Retrieved package silverstripe/multivaluefield

Retrieved package silverstripe/queuedjobs

Retrieved package silverstripe/restrictedobjects

✘ silverstripe-australia/build has an updated, fixed tag version available 1.2.3 (currently ~1.0)
✘ silverstripe-australia/ssautesting has an updated, fixed tag version available 1.1.1 (currently ~1.0)
✘ silverstripe-australia/memberprofiles has an updated, fixed tag version available 1.1.1 (currently 1.1.*)
✘ silverstripe/restrictedobjects has an updated, fixed tag version available 2.1.5 (currently 2.1.*)
✘ silverstripe/frontend-dashboards has an updated, fixed tag version available 1.2.2 (currently ~1.2.0)
✘ silverstripe/microblog has an updated, fixed tag version available 1.9.3 (currently ~1.9.0)
✘ silverstripe/multivaluefield has an updated, fixed tag version available 2.0.5 (currently ~2.0)
✘ silverstripe/queuedjobs has an updated, fixed tag version available 2.4.0 (currently ~2.2)
✘ silverstripe/restrictedobjects has an updated, fixed tag version available 2.1.5 (currently ~2.1)

```