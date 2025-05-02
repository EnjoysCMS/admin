<?php

namespace EnjoysCMS\Module\Admin\Api\Users;

use Doctrine\ORM\EntityManagerInterface;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Core\Users\Entity\User;
use EnjoysCMS\Module\Admin\Api\AbstractApiController;
use EnjoysCMS\Module\Admin\Users\Request\AddUserRequest;
use JMS\Serializer\SerializerBuilder;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Hydrator\Hydrator;
use Yiisoft\Hydrator\Validator\Attribute\ValidateResolver;
use Yiisoft\Hydrator\Validator\ValidatingHydrator;
use Yiisoft\Validator\Validator;

#[Route('/api/user/add', 'api_users_add')]
class AddUser extends AbstractApiController
{


    /**
     * @throws \ReflectionException
     */
    public function __invoke(\EnjoysCMS\Module\Admin\Model\User $userModel, SerializerBuilder $serializerBuilder): ResponseInterface
    {
        $serializer = $serializerBuilder->build();
        $validator = new Validator();

        $addUserRequest = $serializer->fromArray($this->request->getParsedBody(), AddUserRequest::class);
//        $validator = new Validator();
        $validateResult = $validator->validate($addUserRequest);


        if(!$validateResult->isValid()) {
            return $this->json([
                'success' => false,
                'errors' => $validateResult->getErrorMessagesIndexedByProperty()
            ], 400);
        }

        $user = $userModel->addUser($addUserRequest);

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'login' => $user->getLogin(),
                'name' => $user->getName(),
            ]
        ]);
    }
}