<?php


namespace App\Module\Admin\Core\Groups;


use App\Module\Admin\Core\ACL\ACList;
use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Http;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Entities\Group;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Add implements ModelInterface
{

    private ObjectRepository|EntityRepository|\EnjoysCMS\Core\Repositories\Group $groupsRepository;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
        $this->groupsRepository = $this->entityManager->getRepository(Group::class);
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
            '_title' => 'Добавление группы | Группы | Admin | ' . Setting::get('sitename')
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();


        $aclGroupByIds = [];

        if (null !== $group = $this->groupsRepository->find($this->requestWrapper->getQueryData('by', 0))) {
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
                'by' => $this->requestWrapper->getQueryData('by')
            ]
        );

        $queryStrings = Http::getQueryParams($this->requestWrapper->getRequest()->getUri(), ['by']);

        $urlModify = $this->requestWrapper->getRequest()->getUri()->withQuery(
            (empty($queryStrings)) ? uniqid() : $queryStrings
        );
        //var_dump($urlModify); die();
        $form->select('by', 'Заполнить права доступа по...')
            ->setDescription('')
            ->fill(
                [''] + $this->groupsRepository->getListGroupsForSelectForm()
            )->setAttr(AttributeFactory::create('onchange', "location.href='{$urlModify}&by=' + this.value;"));

        $form->header('Информация о группе');

        $form->text('name', 'Название')
            ->addRule(
                Rules::CALLBACK,
                'Название группы должно быть уникальным',
                function () {
                    if (null === $group = $this->groupsRepository->findOneBy(
                            ['name' => $this->requestWrapper->getPostData('name')]
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
        $aclsForCheckbox = (new ACList($this->entityManager->getRepository(ACL::class)))->getArrayForCheckboxForm();
        foreach ($aclsForCheckbox as $label => $item) {
            $form->checkbox(str_repeat(' ', $i++) . "acl", $label)->fill($item);
        }


        $form->submit('sbmt1', 'Добавить');
        return $form;
    }

    private function doAction(): void
    {
        $acls = $this->entityManager->getRepository(ACL::class)->findBy(
            ['id' => $this->requestWrapper->getPostData('acl', [])]
        );

        $group = new Group();
        $group->setName($this->requestWrapper->getPostData('name'));
        $group->setDescription($this->requestWrapper->getPostData('description'));
        $group->setStatus(1);
        $group->setSystem(false);
        foreach ($acls as $acl) {
            $group->setAcl($acl);
        }


        try {
            $this->entityManager->persist($group);
            $this->entityManager->flush();
            Redirect::http($this->urlGenerator->generate('admin/groups'));
        } catch (OptimisticLockException | ORMException $e) {
            Error::code(500, $e->__toString());
        }
    }


}
