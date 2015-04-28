<?php

include_once __DIR__ . '/vendor/autoload.php';

use Packagist\Api\Result\Package\Version;
use GitWrapper\GitWrapper;

// The list of managed modules. A key/value map of 
// module_name => max_version_checked
// If no key is set, it is presumed that there's no max to check against, and that the value is the module
// name. 
// If the key is set, and the max_version supplied, versions up to the provided value are compared against; greater 
// than that are ignored. This is to handle cases where some projects have branches labelled after the SS version, 
// eg Better Buttons. 
$managed = array();

// These are set to the ba-sis modules by default.
if (file_exists(__DIR__.'/managed_modules.php')) {
	$managed = include __DIR__.'/managed_modules.php';
}

if (file_exists(__DIR__ . '/custom_modules.php')) {
	$managed = include __DIR__ . '/custom_modules.php';
}

$remapped = array();
foreach ($managed as $key => $value) {
	if (is_int($key)) {
		$remapped[$value] = true;
	} else {
		$remapped[$key] = $value;
	}
}

$managed = $remapped;

$version_overrides = array();
if (file_exists(__DIR__.'/version_overrides.php')) {
	$version_overrides = include __DIR__.'/version_overrides.php';
}

echo "\n";

$options = getopt('f:', array(
	'timeout::',
	'check-git',			// do we check whether the master branch is different from latest tag?
	'version-fix::',		// do we output a list of 'fixed' versions for the modules based on packagist info?
	'modules::',			// which modules should we inspect? 
	'all-modules::',		
	'workspace::',			// what folder to use as the 'workspace' for checking out data into?
	'readme::',				// should we generate a collation of 'readme' files?
));

if (!isset($options['f'])) {
	usage();
	exit("Specify a composer file to check.\n");
}

$file = realpath($options['f']);
if (!file_exists($file)) {
	usage();
	o("Could not read $file\n");
	exit();
}

$workspace = isset($options['workspace']) ? $options['workspace'] : __DIR__ . '/workspace';

$composer = json_decode(file_get_contents($file));

if (!$composer) {
	usage();
	o("Could not find composer details\n");
	exit();
}

$client = new Packagist\Api\Client();

$wrapper = new GitWrapper();
$time = isset($options['timeout']) ? intval($options['timeout']) : 120;
$wrapper->setTimeout($time);

$notices = array();

$composerFiles = array($file);

if (isset($options['modules']) && $options['modules']) {
	$modules = explode(',', $options['modules']);
	foreach($modules as $module) {
		$project = dirname($file);
		$moduleComposer = "{$module}/composer.json";
		$moduleComposerPath = "{$project}/{$moduleComposer}";
		
		if(!file_exists($moduleComposerPath)) {
			o("Could not read/parse {$moduleComposerPath}");
			continue;
		}
		$composerFiles[] = $moduleComposerPath;
	}
}

 
$allRequirements = array();
$readmeContents = array();
	
foreach ($composerFiles as $composerFile) {
	$composer = json_decode(file_get_contents($composerFile));
	foreach ($composer->require as $packageName => $requiredVersion) {
		if (($packageName === 'php') || (!isset($options['all-modules']) && !isset($managed[$packageName]))) {
			continue;
		}
		$package_name = basename($packageName);

		// Retrieve the module from packagist.

		$package = retrieve_package($packageName, $requiredVersion, $client);
		if(!$package) {
			continue;
		}

		$latestVersion = 0;
		$repo = null;
		$latestRef = null;

		foreach ($package->getVersions() as $version) {
			/* @var $version Version */
			$source = $version->getSource();

			if (!isset($options['all-modules']) && ($maxVersion = $managed[$packageName]) && !is_bool($maxVersion)) {
				// if we're higher than the max version checked, we'll bail
				$greaterThan = version_compare($version->getVersion(), $maxVersion);
				if ($greaterThan > 0) {
					continue;
				}
			}

			$comp = version_compare($version->getVersion(), $latestVersion);
	//		o("Comparing " . $version->getVersion() . " to $latestVersion : $comp");
			if (version_compare($version->getVersion(), $latestVersion) > 0) {
				$latestVersion = $version->getVersion();
				$repo = $version->getSource()->getUrl();
				$latestRef = $version->getSource()->getReference();
			}
			if (!$latestRef) {
				$latestRef = $version->getSource()->getReference();
			}
		}

		$allRequirements[$packageName] = $latestVersion;

		if (version_compare($latestVersion, $requiredVersion) > 0 && $latestVersion) {
			$notices[] = "\033[31m✘ $packageName has an updated, fixed tag version available $latestVersion (currently $requiredVersion)\033[0m";
		} else if (!$latestVersion) {
			$notices[] = "\033[31m✘ $packageName has no proper version available\033[0m";
		} else {
			$notices[] = "\033[32m✔ $packageName looks to be using the latest available version\033[0m";
		}

		// now check out the project
		if ($repo && $latestRef && (isset($options['check-git']) || isset($options['readme']))) {
			$path = $workspace . DIRECTORY_SEPARATOR . basename($package_name);
			if (!file_exists($path)) {
				$git = $wrapper->cloneRepository($source->getUrl(), $path);
			} else {
				$git = $wrapper->workingCopy($path);
				$git->checkout('master', array('f' => true));
				$git->pull();
				$git->fetch(array('tags' => true));
			}

			$git->clearOutput();
			// checkout the 'latest' ref as far as packagist knows about, then check to see
			// whether the repository has changes between it and the current master HEAD
			// $git->diff($latestRef, array('name-status' => true));
			$git->log($latestRef . '..master', array('pretty' => 'oneline', 'abbrev-commit' => true));
			$out = trim($git->getOutput());

			if (isset($options['check-git'])) {
				if (strlen($out)) {
					$lines = explode("\n", $out);
					if (count($lines) > 0) {
						$notices[] = "\033[31m✘ $packageName: " . count($lines) . " commits found between $latestVersion ($latestRef) and master\033[0m";
						$notices[] = "$out\n";
					}
				} else {
					$notices[] = "\033[32m✔ $packageName @ $latestVersion appears to be up-to-date\033[0m\n";
				}
			} 
			if (isset($options['readme'])) {
				if (file_exists($path . '/README.md')) {
					$readmeContents[$package_name] = file_get_contents($path . '/README.md');
				}
			}
		}
	}
}

foreach ($notices as $notice) {
	o($notice);
}


if(isset($options['version-fix']) && $options['version-fix']) {
	o("Recommended project composer requirements: \n");
	foreach ($allRequirements as $package => $version) {
		o("\t\t" . '"' . $package . '": "' . $version . '",');
	}
	o('');
}

if (count($readmeContents)) {
	$dir = 'readme';
	if (isset($options['readme']) && is_string($options['readme']) && strlen($options['readme']) > 1) {
		$dir = $options['readme'];
	}
	
	if ($dir{0} != '/') {
		$dir = $workspace . '/' . $dir;
	}
	if (is_bool($dir)) {
		exit("Something's busted");
	}
	if (is_dir($dir)) {
		del_tree($dir);
	}
	mkdir($dir);
	
	$p = new ParsedownExtra();
	$links = array();
	foreach ($readmeContents as $name => $content) {
		$outfile = $dir . '/' . $name . '.html';
		$content = $p->text($content);
		file_put_contents($outfile, $content);
		$links[] = $name;
	}
	
	$indexContent = '';
	foreach ($links as $link) {
		$indexContent .= "* [$link]($link.html)\n";
	}
	$indexContent = $p->text($indexContent);
	file_put_contents($dir .'/index.html', $indexContent);
}


function o($txt) {
	echo "$txt\n";
}

function retrieve_package($packageName, $requiredVersion, $client) {
	try {
		$package = $client->get($packageName);
	}
	catch(Exception $e) {
		o("\033[31m✘ The package could not be found\n\033[0m");
		return false;
	}

	o("Retrieved package " . $package->getName() . "\n");
	return $package;
}

function del_tree($dir) {
	$files = array_diff(scandir($dir), array('.', '..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file") && !is_link($dir)) ? del_tree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}


function usage() {
	o('php index.php -f /path/to/project/composer.json');
	
	o("\t--modules=comma,separated");
	o("\t\tA list of modules in the project to include in the inspection");
	
	o("\t--version-fix=true");
	o("\t\tWhether to output a 'recommended' composer .json that will provide");
	o("\t\t_fixed_ versions to be bound to");
	
	o("\t--check-git=true");
	o("\t\tWhether to check git repositories for differences between master and ");
	o("\t\tthe latest tagged version ");
}