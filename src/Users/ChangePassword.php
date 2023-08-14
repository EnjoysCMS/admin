<?php


namespace EnjoysCMS\Module\Admin\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Users\Entity\User;
use EnjoysCMS\Module\Admin\Exception\NotEditableUser;
use Psr\Http\Message\ServerRequestInterface;

class ChangePassword
{
    private User $user;
    private ObjectRepository|EntityRepository $usersRepository;


    /**
     * @throws NotEditableUser
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request
    ) {
        $this->usersRepository = $this->em->getRepository(User::class);

        $this->user = $this->usersRepository->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();

        if (!$this->user->isEditable()) {
            throw new NotEditableUser('User is not editable');
        }
    }


    public function getUser(): User
    {
        return $this->user;
    }


    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
    {
        $form = new Form();

        $form->text('password', 'Новый пароль')->addRule(Rules::REQUIRED);


        $form->submit('submit1', 'Сменить пароль');

        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): void
    {
        $this->user->genAndSetPasswordHash($this->request->getParsedBody()['password'] ?? null);
        $this->em->flush();
    }
}
