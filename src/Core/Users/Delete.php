<?php


namespace EnjoysCMS\Module\Admin\Core\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Entities\User;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Admin\Events\BeforeDeleteUserEvent;
use EnjoysCMS\Module\Admin\Exception\NotEditableUser;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Delete implements ModelInterface
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
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RendererInterface $renderer,
        private readonly EventDispatcher $dispatcher,
        private readonly RedirectInterface $redirect,
        private readonly Setting $setting,
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
     * @throws OptimisticLockException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws ContainerExceptionInterface
     */
    public function getContext(): array
    {
        $form = $this->getForm();


        if ($form->isSubmitted()) {
            $this->deleteUser();
            $this->redirect->toRoute('admin/users', emit: true);
        }

        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer,
            'username' => $this->user->getLogin(),
            'user' => $this->user,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/users') => 'Список пользователей',
                'Удаление пользователя',
            ],
            '_title' => 'Удаление пользователя | Пользователи | Admin | ' . $this->setting->get('sitename'),
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function deleteUser(): void
    {
        $this->dispatcher->dispatch(new BeforeDeleteUserEvent($this->user), BeforeDeleteUserEvent::NAME);
        $this->em->remove($this->user);
        $this->em->flush();
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->text('check-delete')->addClass('d-none')->setAttribute(AttributeFactory::create('disabled'))->addRule(
            Rules::CALLBACK,
            'Этого пользователя нельзя удалить',
            function () {
                $total_admins = $this->usersRepository->createQueryBuilder('u')
                    ->select('COUNT(u) as cnt')
                    ->join('u.groups', 'g')
                    ->where('g.id = :id')
                    ->setParameter('id', User::ADMIN_GROUP_ID)
                    ->getQuery()
                    ->getSingleResult()['cnt'];

                if ($this->user->isAdmin() && $total_admins <= 1) {
                    return false;
                }

                return true;
            }
        );
        $form->submit('confirm-delete', 'Удалить')->addClass('btn-danger');
        return $form;
    }
}
