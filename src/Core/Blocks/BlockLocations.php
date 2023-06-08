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
use EnjoysCMS\Core\Entities\Location;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BlockLocations implements ModelInterface
{
    private Block $block;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private  RedirectInterface $redirect,
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
            $this->redirect->toRoute('admin/blocks', emit: true);
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

    /**
     * @throws NotSupported
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults(['locations' => $this->block->getLocationsIds()]);


        $form->select('locations')
            ->setMultiple()
            ->setAttribute(AttributeFactory::create('size', 20))
            ->fill($this->em->getRepository(Location::class)->getListLocationsForSelectForm());
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
}
