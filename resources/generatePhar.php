 <?php
$stub =
'<?php
Phar::mapPhar();
spl_autoload_register(function ($class) {
    $classFile = "phar://Ding.phar/" . str_replace("\\\", "/", $class) . ".php";
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
});
include "phar://Ding.phar/Ding/Autoloader/Autoloader.php";
\Ding\Autoloader\Autoloader::register();
__HALT_COMPILER();
?>';
$phar = new Phar($argv[1]);
$phar->setAlias('Ding.phar');
$phar->buildFromDirectory($argv[2]);
$phar->setStub($stub);
