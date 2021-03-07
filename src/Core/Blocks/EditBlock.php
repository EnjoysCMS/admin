<?php


namespace App\Module\Admin\Core\Blocks;


use App\Entities\Blocks;
use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EditBlock implements ModelInterface
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


    private Blocks $block;

    public function __construct(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
        if (null === $block = $entityManager->getRepository(Blocks::class)->find($this->serverRequest->get('id'))) {
            throw new \InvalidArgumentException('Invalid argument');
        }

        $this->block = $block;
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
        $form = new Form(['method' => 'post']);
        $form->setDefaults(
            [
            'name' => $this->block->getName()
            ]
        );
        $form->text('name', 'Название');
        return $form;
    }

    private function doAction()
    {
    }
}