<?php


namespace EnjoysCMS\Module\Admin\Core\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Setting\Setting;
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
        private readonly ServerRequestInterface $request,
        private readonly Setting $setting,
        private readonly RendererInterface $renderer,
        private readonly RedirectInterface $redirect
    ) {
        $this->usersRepository = $this->em->getRepository(User::class);
        $this->user = $this->getUser();
    }

    /**
     * @throws NotEditableUser
     * @throws NoResultException
     */
    public function getUser(): User
    {
        $user = $this->usersRepository->find(
            $this->request->getAttribute('id')
        );

        if ($user === null) {
            throw new NoResultException();
        }

        if (!$user->isEditable()) {
            throw new NotEditableUser('User is not editable');
        }

        return $user;
    }

    /**
     * @throws ExceptionRule
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->updatePassword();
            $this->redirect->toRoute('@admin_users_list', emit: true);
        }

        $this->renderer->setForm($form);

        return [
            '_title' => 'Смена пароля пользователя | Пользователи | Admin | ' . $this->setting->get('sitename'),
            'form' => $this->renderer,
            'username' => $this->user->getLogin(),
            'user' => $this->user
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
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
    private function updatePassword(): void
    {
        $this->user->genAndSetPasswordHash($this->request->getParsedBody()['password'] ?? null);
        $this->em->flush();
    }
}
