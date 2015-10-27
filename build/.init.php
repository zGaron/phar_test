<?php
class FakePhalconAutoload {
	public static function autoload($class) {
		$try_file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
		if(stripos($try_file, 'Phalcon') === false) {
			$try_file = APP_PATH . $try_file;
		}
		if(file_exists($try_file)) {
			require_once $try_file;
			return class_exists($class);
		} else {
			throw new Exception("file $try_file not exists!");
		}
		return false;
	}
}
if(!defined('APP_PATH')) {
	echo "APP_PATH not defined!? Are you sure you are running with a phalcon project?";
	exit;
}
spl_autoload_register(array('FakePhalconAutoload', 'autoload'));
