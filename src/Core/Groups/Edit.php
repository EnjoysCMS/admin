<?php


namespace EnjoysCMS\Module\Admin\Core\Groups;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Breadcrumbs\BreadcrumbCollection;
use EnjoysCMS\Core\Breadcrumbs\BreadcrumbCollectionInterface;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Core\Users\Entity\User;
use EnjoysCMS\Module\Admin\Core\ACL\ACList;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Edit implements ModelInterface
{
    private Group $group;
    private EntityRepository|\EnjoysCMS\Core\Users\Repository\Group $groupsRepository;

    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ServerRequestInterface $request,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RendererInterface $renderer,
        private readonly RedirectInterface $redirect,
        private readonly ACList $ACList,
        private readonly Setting $setting,
        private readonly BreadcrumbCollection $breadcrumbCollection,
    ) {
        $this->groupsRepository = $this->entityManager->getRepository(Group::class);

        $this->group = $this->groupsRepository->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();
    }


    /**
     * @throws OptimisticLockException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws ContainerExceptionInterface
     * @throws NotSupported
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('admin/groups', emit: true);
        }

        $this->renderer->setForm($form);

        $this->breadcrumbCollection
            ->remove('system/index')
            ->add('admin/index', 'Главная')
            ->add('admin/groups', 'Список групп пользователей')
            ->addBreadcrumbWithoutUrl(sprintf('Редактирование группы `%s`', $this->group->getName()))
        ;
//        dd($this->breadcrumbCollection->get());
        return [
            'form' => $this->renderer,
            '_title' => 'Редактирование группы | Группы | Admin | ' . $this->setting->get('sitename'),
            'breadcrumbs' => $this->breadcrumbCollection->getKeyValueArray(),
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     * @throws NotSupported
     */
    private function getForm(): Form
    {
        $form = new Form();


        $form->setDefaults(
            [
                'name' => $this->group->getName(),
                'description' => $this->group->getDescription(),
                'acl' => array_map(
                    function ($o) {
                        return $o->getId();
                    },
                    $this->group->getAcl()->toArray()
                )
            ]
        );

        $form->text('name', 'Название')
            ->addRule(
                Rules::CALLBACK,
                'Название группы должно быть уникальным',
                function () {
                    if (null === $group = $this->entityManager->getRepository(Group::class)->findOneBy(
                            ['name' => $this->request->getParsedBody()['name'] ?? null]
                        )
                    ) {
                        return true;
                    }

                    if ($group->getName() === $this->group->getName()) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);

        $form->textarea('description', 'Описание группы');


        if ($this->group->getId() === User::ADMIN_GROUP_ID) {
            $form->header('Группа имеет все привилегии (доступ ко всему)');
        } else {
            $form->header('Права доступа');

            $i = 0;
            $aclsForCheckbox = $this->ACList->getArrayForCheckboxForm();
            foreach ($aclsForCheckbox as $label => $item) {
                $fill = array_map(function ($i) {
                    if (str_contains($i[0], 'Admin')) {
                        $i[0] = sprintf('<span class="font-italic">%s</span>', $i[0]);
                    }
                    return $i;
                }, $item);
                $form->checkbox(str_repeat(' ', $i++) . "acl", $label)->fill($fill);
            }
        }

        $form->submit('sbmt1', 'Изменить');

        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    private function doAction(): void
    {
        $acls = $this->entityManager->getRepository(ACL::class)->findBy(
            ['id' => $this->request->getParsedBody()['acl'] ?? []]
        );


        $this->group->setName($this->request->getParsedBody()['name'] ?? null);
        $this->group->setDescription($this->request->getParsedBody()['description'] ?? null);

        $this->group->removeAcl();

        foreach ($acls as $acl) {
            $this->group->setAcl($acl);
        }

        $this->entityManager->flush();
    }


}
