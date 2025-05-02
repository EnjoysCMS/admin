<?php

namespace Api\Users;

use Doctrine\ORM\EntityManagerInterface;
use EnjoysCMS\Module\Admin\Api\Users\AddUser;
use EnjoysCMS\Module\Admin\Model\User;
use HttpSoft\Message\Response;
use HttpSoft\ServerRequest\ServerRequestCreator;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AddUserTest extends TestCase
{


    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public function testAddSuccessUser()
    {
        $serializerBuilder = SerializerBuilder::create();
        $request = ServerRequestCreator::createFromGlobals(
//            server: [],
//            files: [],
//            cookie: [],
//            get: [],
            post: [
                'name' => 'myName',
                'login' => 'test',
                'password' => 'test',
            ],
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $userModel = new User($em);
        $response = (new AddUser($request, new Response()))($userModel, $serializerBuilder);

        $responseContent = json_decode($response->getBody()->__toString());
var_dump($responseContent);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('myName', $responseContent->user->name);
    }
}
