<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Blocks;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\AccessControl\AccessControl;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Block\Options;
use EnjoysCMS\Core\Block\UserBlock;
use EnjoysCMS\Core\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Module\Admin\Config;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class AddBlocks implements ModelInterface
{

    private \EnjoysCMS\Core\Block\Repository\Block|EntityRepository $blockRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly ContentEditor $contentEditor,
        private readonly RedirectInterface $redirect,
        private readonly AccessControl $accessControl,
        private readonly Config $config
    ) {
        $this->blockRepository = $this->em->getRepository(Block::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
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
            'contentEditor' => $this->contentEditor->withConfig(
                $this->config->getContentEditorConfigParamForCustomBlocks()
            )->setSelector('#body')->getEmbedCode(),
            'form' => $this->renderer,
        ];
    }


    /**
     * @throws ExceptionRule
     * @throws NotSupported
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'id' => Uuid::uuid7()->toString()
        ]);
        $form->text('id', 'ID')
            ->addRule(
                Rules::CALLBACK,
                'UUID is wrong',
                function () {
                    return Uuid::isValid($this->request->getParsedBody()['id'] ?? '');
                }
            )
            ->addRule(
                Rules::CALLBACK,
                'Такой идентификатор уже существует',
                function () {
                    try {
                        /** @var string $id */
                        $id = $this->request->getParsedBody()['id'] ?? '';

                        $block = $this->blockRepository->find($id);

                        if ($block === null) {
                            return true;
                        }

                        return false;
                    } catch (ConversionException) {
                        return true;
                    }
                }
            );
        $form->text('name', 'Название')->addRule(Rules::REQUIRED);
        $form->textarea('body', 'Контент')->addRule(Rules::REQUIRED);

        $form->checkbox('groups', 'Группа')
            ->addRule(Rules::REQUIRED)
            ->fill(
                $this->em->getRepository(Group::class)->getGroupsArray()
            );

        $form->submit('addblock', 'Добавить блок');
        return $form;
    }


    /**
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function doAction(): void
    {
        $block = new Block();
        $block->setName($this->request->getParsedBody()['name'] ?? $block->getClassName());
        $block->setId($this->request->getParsedBody()['id'] ?? Uuid::uuid7()->toString());
        $block->setClassName(UserBlock::class);
        $block->setBody($this->request->getParsedBody()['body'] ?? null);
        $block->setRemovable(true);
        $block->setOptions(Options::createFromArray(UserBlock::META['options']));

        $this->em->beginTransaction();
        $this->em->persist($block);

        $this->em->flush();

        $accessAction = $this->accessControl->registerAction(
            $block->getId(),
            flush: false
        );

        $groups = $this->em->getRepository(Group::class)->findBy(
            ['id' => $this->request->getParsedBody()['groups'] ?? []]
        );
        foreach ($groups as $group) {
            $accessAction->addGroup($group);
        }

        $this->em->flush();
        $this->em->commit();
    }
}
