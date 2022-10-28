<?php

namespace App;

use Psr\Http\Message\ServerRequestInterface;

class ContentFactory
{
    private int|null $statusCode;
    private array $validateErrors = [];

    public function __construct(private string $absFilePath, private ServerRequestInterface $request)
    {
    }

    private function getContent(): string
    {
        $content = file_get_contents($this->absFilePath);
        if (! $content) {
            throw new \RuntimeException();
        }

        return $content;
    }

    private function fromJson(string $content): \stdClass
    {
        /** @var \stdClass $decode */
        $decode = json_decode(json: $content, flags: JSON_THROW_ON_ERROR);

        return $decode;
    }

    private function validate(\stdClass $content, string $property, string $type = null): void
    {
        if (! property_exists($content, $property)) {
            throw new \RuntimeException('Property ' . $property . ' not found');
        }

        if (! isset($type)) {
            return;
        }

        if ($type === 'array') {
            if (! is_array($content->$property)) {
                throw new \RuntimeException('Property ' . $property . ' is not array');
            }
        }

        if ($type === 'string') {
            if (! is_string($content->$property)) {
                throw new \RuntimeException('Property ' . $property . ' is not string');
            }
        }
    }

    public function getBody(): \stdClass
    {
        $content = $this->fromJson($this->getContent());
        try {
            $this->validate($content, 'requests', 'array');
        } catch (\Throwable) {
            return $content;
        }

        $queryParams = [];
        if ($this->request->getMethod() === 'GET') {
            $queryParams = $this->request->getQueryParams();
        }
        if ($this->request->getMethod() === 'POST') {
            $queryParams = $this->request->getParsedBody();
            if (in_array('application/json', $this->request->getHeader('Content-Type'))) {
                $queryParams = json_decode($this->request->getBody()->getContents(), true);
            }
        }

        foreach ($content->requests as $item) {
            try {
                $this->validate($item, 'params', 'array');
                foreach ($item->params as $param) {
                    $this->validate($param, 'name', 'string');
                    $this->validate($param, 'value');
                    if (! array_key_exists($param->name, $queryParams)) {
                        throw new \RuntimeException('Params not found to query');
                    }
                    if ($queryParams[$param->name] != $param->value) {
                        throw new \RuntimeException('Param ' . $param->name . ' not equals schema');
                    }
                }
            } catch (\Throwable $e) {
                $this->validateErrors[] = $e->getMessage();
                continue; }
            try {
                $this->validate($item, 'response');
                $this->validate($item->response, 'statusCode');
                $this->validate($item->response, 'data');

                $this->statusCode = ! empty ($item->response->statusCode) ? (int) $item->response->statusCode : null;

                return $item->response->data;
            } catch (\Throwable $e) {
                $this->validateErrors[] = $e->getMessage();
            }
        }

        try {
            $this->validate($content, 'defaultResponse');

            return $content->defaultResponse;
        } catch (\Throwable){}

        if (! empty($this->validateErrors)) {
            $response = new \stdClass();
            $response->validateErrors = $this->validateErrors;

            return $response;
        }

        return new \stdClass();
    }

    public function getStatusCode(): int
    {
        if (! isset($this->statusCode)) {
            throw new \RuntimeException('Status code not set up');
        }

        return $this->statusCode;
    }
}