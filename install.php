<?php

echo "\n\n\n\n\n\nInstalling RULES_MODULE into Budabot\n";
echo "------------------------------------\n\n";
define("BUDA", getcwd());
define("CWD", dirname($_SERVER["PHP_SELF"]));
define("DIR", "proprietary");
echo 'Budabot directory:\t' . BUDA . "\n";

function getVersion() {
	$content = file_get_contents(BUDA . '/core/BotRunner.php');
	if(preg_match('~\$version[^"]*"([^"]*)"~', $content, $arr)) {
		return $arr[1];
	}
	return false;
}

function install($version) {
	echo "Budabot version:\t$version\n";
	
	echo "\nScanning configuration files...\n";
	$dir = opendir(BUDA . '/conf');
	$configs = Array();
	if($dir) {
		while(($file = readdir($dir))) {
			if(!in_array($file, Array(".", "..", "config.template.php", "log4php.xml")) && preg_match('~\.php$~i', $file)) {
				$file = Array("file" => $file, "done" => false, "name" => "");
				
				include BUDA . '/conf/' . $file["file"];
				$file["name"] = $vars['name'];
				$file["done"] = in_array('./' . DIR, $vars['module_load_paths']);
				unset($vars);
				$configs[] = $file;
			}
		}
	}
	
	if(!count($configs)) {
		echo "\nNo configuration files found!\n";
	} else {
		foreach($configs as $cfg) {
			if(!$cfg["done"]) {
				do {
					echo "\nUse RULES_MODULE in bot \"{$cfg["name"]}\" file: {$cfg["file"]} ? (yes/no)\n";
					//$line = trim(fgets(fopen("php://stdin", "r")));
					$line = "yes";
				} while(!in_array($line, Array("yes", "no")));
				if($line == "yes") {
					$backup = $cfg["file"] . '.backup';
					echo "\n\tMaking backup: $backup\n";
	//				copy(BUDA . '/conf/' . $cfg["file"], BUDA . '/conf/' . $backup);

					echo "\tModifying file...\n";
					$content = file_get_contents(BUDA . '/conf/' . $cfg["file"]);
					$start = strpos($content, '$vars[\'module_load_paths\']');
					$end = strpos($content, ');', $start);
	//				file_put_contents(BUDA . '/conf/' . $cfg["file"], trim(substr($content, 0, $end)) . ', \'./' . DIR . "'\n\t" . substr($content, $end));
					echo "\t\tdone.\n";
				} else {
					echo "skipped\n";
				}
			}
		}
	}
	
	echo "\nInstalling module if not exists...\n";
	$DEST = BUDA . '\\' . DIR;
	if(!file_exists($DEST)) {
			echo "\tcreating $DEST ...\n";
			mkdir($DEST);
	}
	$DEST .= '\RULES_MODULE';
	if(!file_exists($DEST)) {
			echo "\tcreating $DEST ...\n";
			mkdir($DEST);
	}
	$dir = opendir(CWD);
	while(($file = readdir($dir))) {
		if(!in_array($file, Array(".", "..", "install.bat", "install.php", "RulesController.class.php"))) {
			if(!file_exists("$DEST\\$file")) {
				echo "\tcopying $DEST\\$file ...\n";
				copy(CWD . "\\$file", "$DEST\\$file");
			}
		}
	}
	$file = "RulesController.class.php";
	if(!file_exists("$DEST\\$file")) {
		echo "\tcopying $DEST\\$file ...\n";
		if($version == "3.0_GA") {
			$delimiter = "/*DELETE_FOR_V3.0*/";
			$content = file_get_contents(CWD . "\\$file");
			$tmp = explode($delimiter, $content);
			$content = array_shift($tmp); array_shift($tmp);
			while(count($tmp)) {
				$content .= array_shift($tmp);
			}
			file_put_contents("$DEST\\$file", $content);
		
		} else {
			copy(CWD . "\\$file", "$DEST\\$file");
		}
	}
	echo "\nInstallation finished!\n";
}
$version = getVersion();
if(preg_match('~^3.(0_GA|1_RC1)$~', $version)) {
	install($version);
}
else {
	echo "\nERROR! Budabot version can not be determined!\n";
}

echo "\n";