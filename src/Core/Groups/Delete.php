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
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Delete implements ModelInterface
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
     * @var RendererInterface
     */
    private RendererInterface $renderer;
    private ObjectRepository $groupRepository;
    private ?Groups $group;

    public function __construct(     ObjectRepository $groupRepository,
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,

        RendererInterface $renderer
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
        $this->groupRepository = $groupRepository;

        $this->group = $this->groupRepository->find(
            $this->serverRequest->get('id')
        );

        if ($this->group === null || $this->group->isSystem()) {
            Error::code(404);
        }
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
            'title' => 'Удаление группы | Группы | Admin | ' . \App\Components\Helpers\Setting::get('sitename')
        ];
    }

    private function doAction()
    {
        $this->entityManager->remove($this->group);
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('admin/groups'));
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setMethod('post');

        $form->submit('confirm-delete', 'Удалить')->addClass('btn-danger');
        return $form;
    }
}
