<?php


namespace EnjoysCMS\Module\Admin\Core\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\User;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Admin\Exception\NotEditableUser;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChangePassword implements ModelInterface
{
    private User $user;
    private ObjectRepository|EntityRepository $usersRepository;


    /**
     * @throws NotEditableUser
     * @throws NoResultException
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RendererInterface $renderer,
        readonly private RedirectInterface $redirect
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

        if (!$user->isEditable()){
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
            $this->redirect->toRoute('admin/users', emit: true);
        }

        $this->renderer->setForm($form);

        return [
            '_title' => 'Смена пароля пользователя | Пользователи | Admin | ' . Setting::get('sitename'),
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
