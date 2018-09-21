<?php
require __DIR__ . '/vendor/autoload.php';

use Slim\Http\Request;
use Slim\Http\Response;
use NYPL\Starter\Service;
use NYPL\Services\Controller;
use NYPL\Starter\Config;
use NYPL\Starter\ErrorHandler;

try {
    Config::initialize(__DIR__ . '/config');

    $service = new Service();

    $service->get("/docs/doc", function (Request $request, Response $response) {
        return \NYPL\Starter\SwaggerGenerator::generate(
            [__DIR__ . "/src", __DIR__ . "/vendor/nypl/microservice-starter/src"],
            $response
        );
    });

    $service->get("/api/v0.1/docs", function (Request $request, Response $response) {
        $controller = new Controller\SwaggerController($request, $response);
        return $controller->getDocs();
    });

    $service->run();
} catch (Exception $exception) {
    ErrorHandler::processShutdownError($exception->getMessage(), $exception);
}
