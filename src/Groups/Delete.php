<?php


namespace EnjoysCMS\Module\Admin\Groups;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Psr\Http\Message\ServerRequestInterface;

class Delete
{
    private Group $group;
    private EntityRepository|\EnjoysCMS\Core\Users\Repository\Group $groupsRepository;

    /**
     * @throws CannotRemoveEntity
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly RedirectInterface $redirect,
        private readonly Setting $setting,
    ) {
        $this->groupsRepository = $this->entityManager->getRepository(Group::class);

        $this->group = $this->groupsRepository->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();

        if ($this->group->isSystem()) {
            throw new CannotRemoveEntity('You cannot delete a system group');
        }
    }


    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer,
            'group' => $this->group,
            '_title' => 'Удаление группы | Группы | Admin | ' . $this->setting->get('sitename')
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): void
    {
        $this->entityManager->remove($this->group);
        $this->entityManager->flush();
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->submit('confirm-delete', 'Удалить')->addClass('btn-danger');
        return $form;
    }

}
