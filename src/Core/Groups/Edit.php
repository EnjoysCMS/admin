<?php


namespace App\Module\Admin\Core\Groups;


use App\Module\Admin\Core\ACL\ACList;
use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Entities\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Edit implements ModelInterface
{
    private Group $group;
    private ObjectRepository|EntityRepository|\EnjoysCMS\Core\Repositories\Group $groupsRepository;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
        $this->groupsRepository = $this->entityManager->getRepository(Group::class);

        $this->group = $this->getGroup();
    }

    /**
     * @throws NoResultException
     */
    private function getGroup(): Group
    {
        $group = $this->groupsRepository->find(
            $this->requestWrapper->getRequest()->getAttribute('id')
        );

        if ($group === null) {
            throw new NoResultException();
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
            '_title' => 'Редактирование группы | Группы | Admin | ' . Setting::get('sitename')
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(
            [
                'method' => 'POST'
            ]
        );


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
                            ['name' => $this->requestWrapper->getPostData('name')]
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
            $aclsForCheckbox = (new ACList($this->entityManager->getRepository(ACL::class)))->getArrayForCheckboxForm();
            foreach ($aclsForCheckbox as $label => $item) {
                $form->checkbox(str_repeat(' ', $i++) . "acl", $label)->fill($item);
            }
        }

        $form->submit('sbmt1', 'Изменить');

        return $form;
    }

    private function doAction(): void
    {
        $acls = $this->entityManager->getRepository(ACL::class)->findBy(
            ['id' => $this->requestWrapper->getPostData('acl', [])]
        );


        $this->group->setName($this->requestWrapper->getPostData('name'));
        $this->group->setDescription($this->requestWrapper->getPostData('description'));

        $this->group->removeAcl();

        foreach ($acls as $acl) {
            $this->group->setAcl($acl);
        }

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('admin/groups'));
        //        Redirect::http();
    }


}
