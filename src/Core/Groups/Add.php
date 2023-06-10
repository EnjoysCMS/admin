<?php

namespace EnjoysCMS\Module\Admin\Core\Groups;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Http;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ACL\ACList;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Add implements ModelInterface
{

    private EntityRepository|\EnjoysCMS\Core\Repositories\Group $groupsRepository;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private RedirectInterface $redirect,
        private ACList $ACList,
    ) {
        $this->groupsRepository = $this->entityManager->getRepository(Group::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('admin/groups', emit: true);
        }

        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer,
            '_title' => 'Добавление группы | Группы | Admin | ' . Setting::get('sitename'),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/groups') => 'Список групп пользователей',
                'Добавить новую группу'
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();


        $aclGroupByIds = [];

        if (null !== $group = $this->groupsRepository->find($this->request->getQueryParams()['by'] ?? 0)) {
            $aclGroupByIds = array_map(
                function ($o) {
                    return $o->getId();
                },
                $group->getAcl()->toArray()
            );
        }


        $form->setDefaults(
            [
                'acl' => $aclGroupByIds,
                'by' => $this->request->getQueryParams()['by'] ?? null
            ]
        );

        $queryStrings = Http::getQueryParams($this->request->getUri(), ['by']);

        $urlModify = $this->request->getUri()->withQuery(
            (empty($queryStrings)) ? uniqid() : $queryStrings
        );
        //var_dump($urlModify); die();
        $form->select('by', 'Заполнить права доступа по...')
            ->setDescription('')
            ->fill(
                [''] + $this->groupsRepository->getListGroupsForSelectForm()
            )->setAttribute(AttributeFactory::create('onchange', "location.href='{$urlModify}&by=' + this.value;"));

        $form->header('Информация о группе');

        $form->text('name', 'Название')
            ->addRule(
                Rules::CALLBACK,
                'Название группы должно быть уникальным',
                function () {
                    if (null === $group = $this->groupsRepository->findOneBy(
                            ['name' => $this->request->getParsedBody()['name'] ?? null]
                        )
                    ) {
                        return true;
                    }

                    return false;
                }
            )->addRule(Rules::REQUIRED);

        $form->textarea('description', 'Описание группы');

        $form->header('Права доступа');
        $i = 0;
        $aclsForCheckbox = $this->ACList->getArrayForCheckboxForm();
        foreach ($aclsForCheckbox as $label => $item) {
            $form->checkbox(str_repeat(' ', $i++) . "acl", $label)->fill($item);
        }


        $form->submit('sbmt1', 'Добавить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $acls = $this->entityManager->getRepository(ACL::class)->findBy(
            ['id' => $this->request->getParsedBody()['acl'] ?? []]
        );

        $group = new Group();
        $group->setName($this->request->getParsedBody()['name'] ?? null);
        $group->setDescription($this->request->getParsedBody()['description'] ?? null);
        $group->setStatus(1);
        $group->setSystem(false);
        foreach ($acls as $acl) {
            $group->setAcl($acl);
        }

        $this->entityManager->persist($group);
        $this->entityManager->flush();

    }


}
