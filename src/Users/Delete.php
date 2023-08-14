<?php


namespace EnjoysCMS\Module\Admin\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Users\Entity\User;
use EnjoysCMS\Module\Admin\Events\BeforeDeleteUserEvent;
use EnjoysCMS\Module\Admin\Exception\NotEditableUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

class Delete
{
    private User $user;
    private EntityRepository $usersRepository;

    /**
     * @throws NotEditableUser
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly EventDispatcherInterface $dispatcher,
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
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): void
    {
        $this->dispatcher->dispatch(new BeforeDeleteUserEvent($this->user));
        $this->em->remove($this->user);
        $this->em->flush();
    }

    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
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
