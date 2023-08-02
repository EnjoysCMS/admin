<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Entities\Location;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Repositories\Locations;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

class BlockLocations implements ModelInterface
{
    private Block $block;
    private Locations|EntityRepository $locationRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly RedirectInterface $redirect,
    ) {
        $this->block = $this->em->getRepository(Block::class)->find(
            $this->request->getAttribute('id')
        ) ?? throw new InvalidArgumentException('Invalid block ID');

        $this->locationRepository = $this->em->getRepository(Location::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('@admin_blocks_manage', emit: true);
        }
        $this->renderer->setForm($form);

        return [
            'form' => $this->renderer,
            'block' => $this->getBlock(),
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults(['locations' => $this->block->getLocationsIds()]);


        $form->select('locations')
            ->setMultiple()
            ->setAttribute(AttributeFactory::create('size', 20))
            ->fill($this->locationRepository->getListLocationsForSelectForm());
        $form->submit('send');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    private function doAction(): void
    {
        $locations = $this->em->getRepository(Location::class)->findBy(
            ['id' => $this->request->getParsedBody()['locations'] ?? []]
        );

        $this->block->removeLocations();
        foreach ($locations as $location) {
            $this->block->setLocations($location);
        }

        $this->em->flush();
    }

    public function getBlock(): Block
    {
        return $this->block;
    }
}
