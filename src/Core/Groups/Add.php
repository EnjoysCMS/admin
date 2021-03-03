<?php


namespace App\Module\Admin\Core\Groups;


use App\Components\Helpers\Error;
use App\Components\Helpers\Http;
use App\Components\Helpers\Redirect;
use App\Entities\ACL;
use App\Entities\Groups;
use App\Entities\Users;
use App\Module\Admin\Core\ACL\ACList;
use App\Module\Admin\Core\ModelInterface;
use DI\Annotation\Inject;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Add implements ModelInterface
{

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;
    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var ObjectRepository
     */
    private ObjectRepository $groupsRepository;
    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;

    /**
     * @Inject({"modules" = "Modules"})
     * @param ObjectRepository $groupsRepository
     * @param EntityManager $entityManager
     * @param ServerRequestInterface $serverRequest
     * @param UrlGeneratorInterface $urlGenerator
     * @param RendererInterface $renderer
     */
    public function __construct(
        ObjectRepository $groupsRepository,
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->groupsRepository = $groupsRepository;
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
            'form' => $this->renderer
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(
            [
                'method' => 'POST'
            ]
        );


        $aclGroupByIds = [];

        if (null !== $group = $this->groupsRepository->find($this->serverRequest->get('by', 0))) {
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
                'by' => $this->serverRequest->get('by')
            ]
        );

        $queryStrings = Http::getQueryParams($this->serverRequest->getRequest()->getUri(), ['by']);

        $urlModify = $this->serverRequest->getRequest()->getUri()->withQuery(
            (empty($queryStrings)) ? uniqid() : $queryStrings
        );
        //var_dump($urlModify); die();
        $form->select('by', 'Заполнить права доступа по...')
            ->setDescription('')
            ->fill(
                [''] + $this->groupsRepository->getListGroupsForSelectForm()
            )->setAttribute('onchange', "location.href='{$urlModify}&by=' + this.value;");

        $form->header('Информация о группе');

        $form->text('name', 'Название')
            ->addRule(
                Rules::CALLBACK,
                'Название группы должно быть уникальным',
                function () {
                    if (null === $group = $this->groupsRepository->findOneBy(
                            ['name' => $this->serverRequest->post('name')]
                        )) {
                        return true;
                    }

                    return false;
                }
            )->addRule(Rules::REQUIRED);

        $form->textarea('description', 'Описание группы');


        $i = 0;
        $aclsForCheckbox = (new ACList($this->entityManager->getRepository(ACL::class)))->getArrayForCheckboxForm();
        foreach ($aclsForCheckbox as $label => $item) {
            $form->checkbox(str_repeat(' ', $i++) . "acl", $label)->fill($item);
        }


        $form->submit('sbmt1', 'Добавить');
        return $form;
    }

    private function doAction()
    {
        $acls = $this->entityManager->getRepository(ACL::class)->findBy(
            ['id' => $this->serverRequest->post('acl', [])]
        );

        $group = new Groups();
        $group->setName($this->serverRequest->post('name'));
        $group->setDescription($this->serverRequest->post('description'));
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
