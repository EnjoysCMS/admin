<?php


namespace EnjoysCMS\Module\Admin\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Core\Users\Entity\User;
use Psr\Http\Message\ServerRequestInterface;

class Add
{
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function addUser(): void
    {
        $newUser = new User();
        $newUser->setName($this->request->getParsedBody()['name'] ?? null);
        $newUser->setLogin($this->request->getParsedBody()['login'] ?? null);
        $newUser->genAndSetPasswordHash($this->request->getParsedBody()['password'] ?? null);

        $groups = $this->em->getRepository(Group::class)->findBy(
            ['id' => $this->request->getParsedBody()['groups'] ?? []]
        );
        foreach ($groups as $group) {
            $newUser->setGroups($group);
        }

        $this->em->persist($newUser);
        $this->em->flush();
    }

    /**
     * @throws ExceptionRule
     * @throws NotSupported
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults(['groups' => [2]]);
        $form->text('name', 'Имя')
            ->setAttribute(AttributeFactory::create('autocomplete', 'off'))
            ->addRule(Rules::REQUIRED);
        $form->text('login', 'Логин')
            ->setAttribute(AttributeFactory::create('autocomplete', 'off'))
            ->addRule(
                Rules::CALLBACK,
                'Такой логин уже занят',
                function () {
                    if (null === $this->em->getRepository(User::class)->findOneBy(
                            ['login' => $this->request->getParsedBody()['login'] ?? null]
                        )
                    ) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);
        $form->text('password', 'Пароль')
            ->setAttribute(AttributeFactory::create('autocomplete', 'off'))
            ->addRule(Rules::REQUIRED);


        $form->checkbox('groups', 'Группа')
            ->addRule(Rules::REQUIRED)
            ->fill(
                $this->em->getRepository(Group::class)->getGroupsArray()
            );

        $form->submit('sbmt1', 'Добавить');

        return $form;
    }

}
