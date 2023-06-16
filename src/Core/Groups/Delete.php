<?php


namespace EnjoysCMS\Module\Admin\Core\Groups;


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
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Psr\Http\Message\ServerRequestInterface;

class Delete implements ModelInterface
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

        $this->group = $this->getGroup();
    }

    /**
     * @throws NoResultException
     * @throws CannotRemoveEntity
     */
    private function getGroup(): Group
    {
        $group = $this->groupsRepository->find(
            $this->request->getAttribute('id')
        );

        if ($group === null) {
            throw new NoResultException();
        }

        if ($group->isSystem()) {
            throw new CannotRemoveEntity('You cannot delete a system group');
        }
        return $group;
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
    private function doAction(): void
    {
        $this->entityManager->remove($this->group);
        $this->entityManager->flush();
        $this->redirect->toRoute('admin/groups', emit: true);
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->submit('confirm-delete', 'Удалить')->addClass('btn-danger');
        return $form;
    }

}
