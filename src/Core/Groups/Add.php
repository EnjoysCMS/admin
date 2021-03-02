<?php


namespace App\Module\Admin\Core\Groups;


use App\Components\Composer\Utils;
use App\Components\Helpers\Modules;
use App\Entities\ACL;
use App\Module\Admin\Core\ModelInterface;
use DI\Annotation\Inject;
use Doctrine\ORM\EntityManager;
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
        foreach ($this->getAclArrayForForm() as $name => $item) {
            $i++;
            $form->checkbox("acl[{$i}][]", $name)->fill($item);
        }


        $form->submit('sbmt1', 'Добавить');
        return $form;
    }

    private function doAction()
    {
        var_dump($this->serverRequest->post());
        exit;
//        $group = new Groups();
//        $group->setName($this->serverRequest->post('name'));
//        $group->setDescription($this->serverRequest->post('description'));
//        $group->setStatus(1);
//        $group->setSystem(false);
//
//
//        try {
//            $this->entityManager->persist($group);
//            $this->entityManager->flush();
//            Redirect::http($this->urlGenerator->generate('admin/groups'));
//        } catch (OptimisticLockException | ORMException $e) {
//            Error::code(500, $e->__toString());
//        }
    }

    private function getAclArrayForForm()
    {
        $aclFromDb = $this->entityManager->getRepository(ACL::class)->getAllActiveACL();


        $ACList = [];
        $tACList = [];
        foreach (Modules::installed() as $module) {
            foreach ($module->namespaces as $ns) {
                $tACList[$module->moduleName] = array_filter(
                    $aclFromDb,
                    function ($v) use ($ns) {
                        return str_starts_with($v->getAction(), $ns);
                    }
                );
            }
            /** @var ACL $v */
            foreach ($tACList[$module->moduleName] as $v) {
                $ACList[$module->moduleName][' ' . $v->getId()] = [
                    $v->getComment() . '<br><small>' . $v->getAction() . '</small>',
                    ['id' => $v->getId()]
                ];
            }

            $aclFromDb = array_diff_key($aclFromDb, $tACList[$module->moduleName]);
        }

        $systemNamespaces = Utils::parseComposerJson($_ENV['PROJECT_DIR'] . '/composer.json')->namespaces;
        foreach ($systemNamespaces as $ns) {
            $tACList['System Core'] = array_filter(
                $aclFromDb,
                function ($v) use ($ns) {
                    return str_starts_with($v->getAction(), $ns);
                }
            );
        }

        /** @var ACL $v */
        foreach ($tACList['System Core'] as $v) {
            $ACList['System Core'][' ' . $v->getId()] = [
                $v->getComment() . '<br><small>' . $v->getAction() . '</small>',
                ['id' => $v->getId()]
            ];
        }


        return $ACList;
    }
}
