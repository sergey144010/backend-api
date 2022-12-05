<?php

namespace App;

use Aura\Router\RouterContainer;
use http\Exception\RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;

class Application
{
    public function run(string $dataDirectory): void
    {
        $request = ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
        );

        if ($request->getMethod() === 'OPTIONS') {
            echo $this->render($this->responseWithCors());

            return;
        }

        $routerContainer = new RouterContainer();
        $map = $routerContainer->getMap();

        $dataDirectory .= DIRECTORY_SEPARATOR;
        foreach ($this->files($dataDirectory) as $file) {
            if ($file === '.' || $file === '..') { continue; }
            $absFilePath = $dataDirectory . $file;
            if (is_dir($absFilePath)) { continue; }
            $pathInfo = $this->pathInfo($absFilePath);
            $fileNameParsed = $this->fileNameParsed($pathInfo);
            if ($this->getExtension($pathInfo) !== 'json') { continue; }

            $status = $this->getStatus($fileNameParsed);
            $method = $this->getMethod($fileNameParsed);
            $this->removeStatusAndMethod($fileNameParsed);
            $route = $this->getRoute($fileNameParsed);

            $map->route(
                $method . '-' . $route,
                $route,
                function (ServerRequestInterface $request) use ($absFilePath, $status) {
                    $contentService = (new ContentFactory($absFilePath, $request));
                    $content = json_encode($contentService->getBody(), JSON_THROW_ON_ERROR);
                    try {
                        $statusCode = $contentService->getStatusCode();
                    } catch (Throwable) {
                        $statusCode = $status;
                    }
                    $response = $this->responseWithCors();
                    $response = $response->withHeader('Content-Type', 'application/json');
                    $response = $response->withStatus($statusCode);
                    $response->getBody()->write($content);
                    return $response;
                }
            )->allows(mb_strtoupper($method));
        }

        $matcher = $routerContainer->getMatcher();

        $route = $matcher->match($request);
        if (! $route) {
            $response = $this->responseWithCors();
            $response->getBody()->write('No route found for the request');

            echo $this->render($response);

            return;
        }

        foreach ($route->attributes as $key => $val) {
            $request = $request->withAttribute($key, $val);
        }

        $callable = $route->handler;
        $response = $callable($request);

        echo $this->render($response);
    }

    /**
     * @param Array<int, string> $fileNameParsed
     * @return string
     */
    private function getRoute(array $fileNameParsed): string
    {
        $currentRoute = DIRECTORY_SEPARATOR;
        foreach ($fileNameParsed as $key => $part) {
            if ($key === count($fileNameParsed)-1) {
                $currentRoute .= $part;
                break;
            }
            $currentRoute .= $part . DIRECTORY_SEPARATOR;
        }

        return $currentRoute;
    }

    private function pathInfo(string $absFilePath): array
    {
        /** @var Array<string, string> $pathInfo */
        $pathInfo = pathinfo($absFilePath);

        return $pathInfo;
    }

    /**
     * @param Array<string, string> $pathInfo
     * @return Array<int, string>
     */
    private function fileNameParsed(array $pathInfo): array
    {
        return explode('.', $pathInfo["filename"]);
    }

    /**
     * @param Array<string, string> $pathInfo
     * @return string
     */
    private function getExtension(array $pathInfo): string
    {
        return $pathInfo["extension"] ?? '';
    }

    /**
     * @param Array<int, string> $fileNameParsed
     * @return string
     */
    private function getStatus(array $fileNameParsed): string
    {
        return $fileNameParsed[count($fileNameParsed)-1];
    }

    /**
     * @param Array<int, string> $fileNameParsed
     * @return string
     */
    private function getMethod(array $fileNameParsed): string
    {
        return $fileNameParsed[count($fileNameParsed)-2];
    }

    private function removeLast(&$fileNameParsed): void
    {
        unset($fileNameParsed[count($fileNameParsed)-1]);
    }

    private function removeStatusAndMethod(&$fileNameParsed): void
    {
        $this->removeLast($fileNameParsed);
        $this->removeLast($fileNameParsed);
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

    private function render(ResponseInterface $response): StreamInterface
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
        http_response_code($response->getStatusCode());

        return $response->getBody();
    }

    private function responseWithCors(): Response
    {
        return new Response(
            status: 200,
            headers: [
                        'Access-Control-Allow-Origin' => '*',
                        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                        'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE, PATCH',
                    ]
        );
    }
}