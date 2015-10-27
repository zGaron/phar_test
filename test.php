<?php
#$phar = new Phar('phalcon.phar', 0, 'phalcon.phar');
#$phar->buildFromDirectory(dirname(__FILE__)."/phar_test");
#$phar->setStub($phar->createDefaultStub('.init.php', '.init.php'));
#$phar->compressFiles(Phar::GZ);

include "phalcon.phar";


use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;


$di = new FactoryDefault();
$application = new Application($di);
$application->useImplicitView(false);
echo $application->handle()->getContent();
