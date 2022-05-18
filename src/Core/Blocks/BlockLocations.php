<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Doctrine\ORM\EntityManager;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Block;
use EnjoysCMS\Core\Entities\Location;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BlockLocations implements ModelInterface
{
    private Block $block;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
        if (null === $block = $entityManager->getRepository(Block::class)->find(
                $this->requestWrapper->getRequest()->getAttribute('id')
            )) {
            throw new InvalidArgumentException('Invalid block ID');
        }

        if (!($block instanceof Block)) {
            throw new InvalidArgumentException('Invalid block');
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
            'form' => $this->renderer,
            'block' => $this->block,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/blocks') => 'Менеджер блоков',
                $this->urlGenerator->generate('admin/editblock', ['id' => $this->block->getId()]) => 'Редактирование блока',
                sprintf('Настройка расположения блока `%s`', $this->block->getName())
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults(['locations' => $this->block->getLocationsIds()]);


        $form->select('locations')
            ->setMultiple()
            ->setAttribute(AttributeFactory::create('size', 20))
            ->fill($this->entityManager->getRepository(Location::class)->getListLocationsForSelectForm());
        $form->submit('send');
        return $form;
    }

    private function doAction(): void
    {
        $locations = $this->entityManager->getRepository(Location::class)->findBy(
            ['id' => $this->requestWrapper->getPostData('locations', [])]
        );

        $this->block->removeLocations();
        foreach ($locations as $location) {
            $this->block->setLocations($location);
        }

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('admin/blocks'));
    }
}
