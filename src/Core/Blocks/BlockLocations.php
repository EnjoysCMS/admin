<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Block\Entity\BlockLocation;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\RouteCollection;

class BlockLocations implements ModelInterface
{
    private Block $block;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly RedirectInterface $redirect,
        private readonly RouteCollection $routeCollection,
    ) {
        $this->block = $this->em->getRepository(Block::class)->find(
            $this->request->getAttribute('id')
        ) ?? throw new InvalidArgumentException('Invalid block ID');
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

        $form->setDefaults(['locations' => $this->block->getLocationsValues()]);


        $form->select('locations')
            ->setMultiple()
            ->setAttribute(AttributeFactory::create('size', 20))
            ->fill($this->getFillLocations());
        $form->submit('send');
        return $form;
    }

    private function getFillLocations(): \Closure
    {
        return function () {
            $result = [];
            foreach ($this->routeCollection as $routeName => $route) {
                if (str_starts_with($routeName, '@') && !($route->getOption('allowBlockLocation') ?? false)) {
                    continue;
                }
                $controller = implode('::', (array)$route->getDefault('_controller'));
                $result[$controller] = $route->getOption('title') ?? $controller;
            }
            return $result;
        };
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    private function doAction(): void
    {
        $blockLocationRepository = $this->em->getRepository(BlockLocation::class);

        $this->block->removeLocations();
        foreach ($this->request->getParsedBody()['locations'] ?? [] as $location) {
            /** @var string $location */
            $blockLocation = $blockLocationRepository->findOneBy(['location' => $location]) ?? new BlockLocation();
            $blockLocation->setLocation($location);
            $this->em->persist($blockLocation);
            $this->block->setLocations($blockLocation);
        }
        $this->em->flush();
    }

    public function getBlock(): Block
    {
        return $this->block;
    }
}
