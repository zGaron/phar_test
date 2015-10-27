#!/usr/bin/php
<?php
$phar = new Phar('phalcon.phar', 0, 'phalcon.phar');
$phar->buildFromDirectory(dirname(__FILE__)."/build");
$phar->setStub($phar->createDefaultStub('.init.php', '.init.php'));
$phar->compressFiles(Phar::GZ);
