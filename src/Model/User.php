<?php

namespace EnjoysCMS\Module\Admin\Model;

use Doctrine\ORM\EntityManagerInterface;
use EnjoysCMS\Module\Admin\Users\Request\AddUserRequest;

class User
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function addUser(AddUserRequest $request): \EnjoysCMS\Core\Users\Entity\User {
        $user = new \EnjoysCMS\Core\Users\Entity\User();
        $user->setLogin($request->login);
        $user->setName($request->name);
        $user->genAndSetPasswordHash($request->password);
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }
}