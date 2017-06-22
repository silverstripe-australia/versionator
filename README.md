# Versionator

A tool that helps with reviewing the state of your composer.json
dependencies for SilverStripe projects.

## Features

* List current module versions, along with the latest version available on
  packagist for that module
* Output a 'recommended' set of dependencies in composer format
* Check modules to determine whether there are changes in `master` that
  haven't been incorporated into a tag (ie whether the latest stable _may_
  not have the latest code)
* Generate README information about the modules used in the project

## When to use

Use this if:

* You want to generate a list of fixed version composer dependencies for a
  SilverStripe project
* You want to see whether the modules in your project have any updates
  that could be made use of
* You want to know whether a repository has any changes that haven't made it
  to a stable tagged release yet.
* You want to review the current state of tagging for a number of modules found
  under a specific packagist owner.

The motivation behind this tool is for projects to have a well
defined version set of modules defined in the composer.json for that project.

## Installing

If you wish to run this from anywhere, simply add your checkout of versionator to your PATH in your ~/.bash_profile (or ~/.bashrc) like this:

```
export PATH=$PATH:/path/to/versionator
```

## Usage

`versionator -f /path/to/project/composer.json`

**OR**

`versionator -o packagist,owners`

Will output the list of modules and their latest versions in packagist.

**Options**

* **--check-git** Whether to check git repositories for differences
  between master and the latest tagged version of modules
* **--modules=comma,separated,list** - A list of modules in the project
  to also include the composer.json settings for deeper inspection
* **--readme[=/output/path/for/files]** - Output a list of readmes for modules.
  If output path not specified, output is to versionator/workspace/readme
* **--version-fix** - Whether to output a 'recommended' composer .json
  that will provide _fixed_ versions to be bound to

## Example outputs

Default output

```
$ versionator.sh -f /home/marcus/www/projects/cycle-int/composer.json

Retrieved package symbiote/silverstripe-build

Retrieved package symbiote/silverstripe-test-assist

Retrieved package symbiote/silverstripe-memberprofiles

Retrieved package silverstripe/restrictedobjects

Retrieved package silverstripe/frontend-dashboards

Retrieved package silverstripe/microblog

✘ symbiote/silverstripe-build has an updated, fixed tag version available 1.2.3 (currently ~1.0)
✘ symbiote/silverstripe-test-assist has an updated, fixed tag version available 1.1.1 (currently ~1.0)
✘ symbiote/silverstripe-memberprofiles has an updated, fixed tag version available 1.1.1 (currently 1.1.*)
✘ silverstripe/restrictedobjects has an updated, fixed tag version available 2.1.5 (currently 2.1.*)
✘ silverstripe/frontend-dashboards has an updated, fixed tag version available 1.2.2 (currently ~1.2.0)
✘ silverstripe/microblog has an updated, fixed tag version available 1.9.3 (currently ~1.9.0)
```

Outputting the list of fixed versions

```
$ versionator.sh -f /home/marcus/www/projects/cycle-int/composer.json --version-fix=true

Retrieved package symbiote/silverstripe-build

Retrieved package symbiote/silverstripe-test-assist

Retrieved package symbiote/silverstripe-memberprofiles

Retrieved package silverstripe/restrictedobjects

Retrieved package silverstripe/frontend-dashboards

Retrieved package silverstripe/microblog

✘ symbiote/silverstripe-build has an updated, fixed tag version available 1.2.3 (currently ~1.0)
✘ symbiote/silverstripe-test-assist has an updated, fixed tag version available 1.1.1 (currently ~1.0)
✘ symbiote/silverstripe-memberprofiles has an updated, fixed tag version available 1.1.1 (currently 1.1.*)
✘ silverstripe/restrictedobjects has an updated, fixed tag version available 2.1.5 (currently 2.1.*)
✘ silverstripe/frontend-dashboards has an updated, fixed tag version available 1.2.2 (currently ~1.2.0)
✘ silverstripe/microblog has an updated, fixed tag version available 1.9.3 (currently ~1.9.0)
Recommended project composer requirements:

		"symbiote/silverstripe-build": "1.2.3",
		"symbiote/silverstripe-test-assist": "1.1.1",
		"symbiote/silverstripe-memberprofiles": "1.1.1",
		"silverstripe/restrictedobjects": "2.1.5",
		"silverstripe/frontend-dashboards": "1.2.2",
		"silverstripe/microblog": "1.9.3",


```

Adding additional modules to dig into from the project (note the extra modules included).

```
$ versionator.sh -f /home/marcus/www/projects/cycle-int/composer.json --modules=microblog

Retrieved package symbiote/silverstripe-build

Retrieved package symbiote/silverstripe-test-assist

Retrieved package symbiote/silverstripe-memberprofiles

Retrieved package silverstripe/restrictedobjects

Retrieved package silverstripe/frontend-dashboards

Retrieved package silverstripe/microblog

Retrieved package symbiote/silverstripe-multivaluefield

Retrieved package symbiote/silverstripe-queuedjobs

Retrieved package silverstripe/restrictedobjects

✘ symbiote/silverstripe-build has an updated, fixed tag version available 1.2.3 (currently ~1.0)
✘ symbiote/silverstripe-test-assist has an updated, fixed tag version available 1.1.1 (currently ~1.0)
✘ symbiote/silverstripe-memberprofiles has an updated, fixed tag version available 1.1.1 (currently 1.1.*)
✘ silverstripe/restrictedobjects has an updated, fixed tag version available 2.1.5 (currently 2.1.*)
✘ silverstripe/frontend-dashboards has an updated, fixed tag version available 1.2.2 (currently ~1.2.0)
✘ silverstripe/microblog has an updated, fixed tag version available 1.9.3 (currently ~1.9.0)
✘ symbiote/silverstripe-multivaluefield has an updated, fixed tag version available 2.0.5 (currently ~2.0)
✘ symbiote/silverstripe-queuedjobs has an updated, fixed tag version available 2.4.0 (currently ~2.2)
✘ silverstripe/restrictedobjects has an updated, fixed tag version available 2.1.5 (currently ~2.1)

```
