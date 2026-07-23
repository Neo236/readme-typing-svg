<?php declare(strict_types=1);

// resolver el autoload de composer sin depender del cwd
foreach ([__DIR__ . "/../vendor/autoload.php", "/var/task/vendor/autoload.php", __DIR__ . "/vendor/autoload.php"] as $autoload) {
    if (file_exists($autoload)) {
        require $autoload;
        break;
    }
}

// load environment variables if .env exists
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$controller = new RendererController($_REQUEST);
$controller->setHeaders();
echo $controller->render();
