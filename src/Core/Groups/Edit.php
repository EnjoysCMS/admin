<?php


namespace App\Module\Admin\Core\Groups;


use App\Components\Helpers\Error;
use App\Components\Helpers\Redirect;
use App\Entities\ACL;
use App\Entities\Groups;
use App\Entities\Users;
use App\Module\Admin\Core\ACL\ACList;
use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Edit implements ModelInterface
{

    private ?Groups $group;
    /**
     * @var ObjectRepository
     */
    private ObjectRepository $groupsRepository;
    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;

    public function __construct(
        EntityManager $entityManager,
        ObjectRepository $groupsRepository,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        $this->serverRequest = $serverRequest;
        $this->groupsRepository = $groupsRepository;
        $this->entityManager = $entityManager;

        $this->group = $this->groupsRepository->find(
            $this->serverRequest->get('id')
        );

        if ($this->group === null) {
            Error::code(404);
        }


        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
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
            'title' => 'Редактирование группы | Группы | Admin | ' . \App\Components\Helpers\Setting::get('sitename')
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
                'acl' => array_map(function($o){
                    return $o->getId();
                }, $this->group->getAcl()->toArray())
            ]
        );

        $form->text('name', 'Название')
            ->addRule(
                Rules::CALLBACK,
                'Название группы должно быть уникальным',
                function () {
                    if (null === $group = $this->entityManager->getRepository(Groups::class)->findOneBy(
                            ['name' => $this->serverRequest->post('name')]
                        )) {
                        return true;
                    }

                    if ($group->getName() === $this->group->getName()) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);

        $form->textarea('description', 'Описание группы');


        if($this->group->getId() === Users::ADMIN_GROUP_ID){
            $form->header('Группа имеет все привилегии (доступ ко всему)');
        }else{
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

    private function doAction()
    {
        $acls = $this->entityManager->getRepository(ACL::class)->findBy(
            ['id' => $this->serverRequest->post('acl', [])]
        );


        $this->group->setName($this->serverRequest->post('name'));
        $this->group->setDescription($this->serverRequest->post('description'));

        $this->group->removeAcl();

        foreach ($acls as $acl) {
            $this->group->setAcl($acl);
        }

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('admin/groups'));
    }
}
