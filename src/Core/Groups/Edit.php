<?php


namespace App\Module\Admin\Core\Groups;


use App\Components\Helpers\Error;
use App\Components\Helpers\Redirect;
use App\Entities\Groups;
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
        $form->setDefaults(
            [
                'name' => $this->group->getName(),
                'description' => $this->group->getDescription()
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


        $form->submit('sbmt1', 'Изменить');

        return $form;
    }

    private function doAction()
    {
        $this->group->setName($this->serverRequest->post('name'));
        $this->group->setDescription($this->serverRequest->post('description'));
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('admin/groups'));
    }
}
