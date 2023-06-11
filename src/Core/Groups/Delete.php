<?php


namespace EnjoysCMS\Module\Admin\Core\Groups;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Delete implements ModelInterface
{
    private Group $group;
    private ObjectRepository|EntityRepository|\EnjoysCMS\Core\Repositories\Group $groupsRepository;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private RedirectInterface $redirect,
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
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/users') => 'Группы пользователей',
                'Удаление группы',
            ],
            '_title' => 'Удаление группы | Группы | Admin | ' . Setting::get('sitename')
        ];
    }

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
