<?php


namespace App\Module\Admin\Core\Blocks;


use App\Components\Helpers\Redirect;
use App\Entities\Blocks;
use App\Entities\Groups;
use App\Entities\Locations;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BlockLocations implements \App\Module\Admin\Core\ModelInterface
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
            throw new \InvalidArgumentException('Invalid block ID');
        }

        if (!($block instanceof Blocks)) {
            throw new \InvalidArgumentException('Invalid block');
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

        return ['form' => $this->renderer, 'block' => $this->block,];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
        $form->setDefaults(['locations' => $this->block->getLocationsIds()]);


        $form->select('locations')->setMultiple()->fill(
            $this->entityManager->getRepository(Locations::class)->getListLocationsForSelectForm()
        );
        $form->submit('send');
        return $form;
    }

    private function doAction()
    {

        $locations = $this->entityManager->getRepository(Locations::class)->findBy(
            ['id' => $this->serverRequest->post('locations', [])]
        );

        $this->block->removeLocations();
        foreach ($locations as $location) {
            $this->block->setLocations($location);
        }

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('admin/blocks'));
    }
}