<?php

namespace App;

use Aura\Router\RouterContainer;
use http\Exception\RuntimeException;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;

class Application
{
    public function run(string $dataDirectory): void
    {
        $request = ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
        );

        $routerContainer = new RouterContainer();
        $map = $routerContainer->getMap();

        $dataDirectory .= DIRECTORY_SEPARATOR;
        foreach ($this->files($dataDirectory) as $file) {
            if ($file === '.' || $file === '..') { continue; }
            $absFilePath = $dataDirectory . $file;

            $fileNameParsed = $this->fileNameParsed($absFilePath);
            $currentMethod = $this->currentMethod($fileNameParsed);
            $this->removeMethod($fileNameParsed);

            $currentRoute = $this->currentRoute($fileNameParsed);

            $map->route(
                $currentRoute,
                $currentRoute,
                function () use ($absFilePath) {
                    $response = new Response();
                    $response = $response->withHeader('Content-Type', 'application/json');
                    $response = $response->withHeader('Access-Control-Allow-Origin', '*');
                    $response->getBody()->write(file_get_contents($absFilePath));
                    return $response;
                }
            )->allows(mb_strtoupper($currentMethod));
        }

        $matcher = $routerContainer->getMatcher();

        $route = $matcher->match($request);
        if (! $route) {
            echo "No route found for the request." . PHP_EOL;
            exit;
        }

        foreach ($route->attributes as $key => $val) {
            $request = $request->withAttribute($key, $val);
        }

        $callable = $route->handler;
        $response = $callable($request);

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
        http_response_code($response->getStatusCode());
        echo $response->getBody();
    }

    /**
     * @param Array<int, string> $fileNameParsed
     * @return string
     */
    private function currentRoute(array $fileNameParsed): string
    {
        $currentRoute = DIRECTORY_SEPARATOR;
        foreach ($fileNameParsed as $key => $part) {
            if ($key == count($fileNameParsed)-1) {
                $currentRoute .= $part;
                break;
            }
            $currentRoute .= $part . DIRECTORY_SEPARATOR;
        }

        return $currentRoute;
    }

    /**
     * @param string $absFilePath
     * @return Array<int, string>
     */
    private function fileNameParsed(string $absFilePath): array
    {
        return explode('.', pathinfo($absFilePath)["filename"]);
    }

    /**
     * @param Array<int, string> $fileNameParsed
     * @return string
     */
    private function currentMethod(array $fileNameParsed): string
    {
        return $fileNameParsed[count($fileNameParsed)-1];
    }

    private function removeMethod(&$fileNameParsed): void
    {
        unset($fileNameParsed[count($fileNameParsed)-1]);
    }

    /**
     * @param string $dataDirectory
     * @return Array<int, string>
     */
    private function files(string $dataDirectory): array
    {
        $files = scandir($dataDirectory);
        if (! $files) {
            throw new RuntimeException('Data directory not found');
        }

        return $files;
    }
}