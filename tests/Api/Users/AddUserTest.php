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

class AddUserTest extends TestCase
{


    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public function testAddSuccessUser()
    {
        $request = ServerRequestCreator::createFromGlobals(
            post: [
                'name' => 'testName',
                'login' => 'testLogin',
                'password' => 'testPassword',
            ],
        );

        $response = (new AddUser($request, new Response()))(
            new User($this->createMock(EntityManagerInterface::class)),
            SerializerBuilder::create()
        );

        $this->assertSame(200, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"success":true,"user":{"id":null,"login":"testLogin","name":"testName"}}',
            $response->getBody()->__toString()
        );
    }
}
