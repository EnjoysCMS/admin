<?php

namespace EnjoysCMS\Module\Admin\Api;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AbstractApiController
{
    public function __construct(
        protected readonly ServerRequestInterface $request,
        protected ResponseInterface $response
    ) {
    }

    final protected function json(mixed $payload, int $statusCode = 200): ResponseInterface
    {
        $this->response = $this->response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');

        $this->response->getBody()->write(json_encode($payload));
        return $this->response;
    }

}