<?php

declare(strict_types=1);

namespace App\Module\Admin\Core\Blocks;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Blocks\Custom;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Entities\Block;
use EnjoysCMS\Core\Entities\Group;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddBlocks implements ModelInterface
{
    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    public function __construct(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        $this->renderer = $renderer;
        $this->urlGenerator = $urlGenerator;
        $this->serverRequest = $serverRequest;
        $this->entityManager = $entityManager;
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

    private function getForm()
    {
        $form = new Form(['method' => 'post']);
        $form->text('name', 'Название')->addRule(Rules::REQUIRED);
        $form->textarea('body', 'Контент')->addRule(Rules::REQUIRED);

        $form->checkbox('groups', 'Группа')->fill(
            $this->entityManager->getRepository(Group::class)->getGroupsArray()
        )->addRule(Rules::REQUIRED)
        ;

        $form->submit('addblock', 'Добавить блок');
        return $form;
    }

    private function doAction()
    {
        try {
            $block = new Block();
            $block->setName($this->serverRequest->post('name'));
            $block->setAlias((string)Uuid::uuid4());
            $block->setBody($this->serverRequest->post('body'));
            $block->setRemovable(true);
            $block->setOptions(Custom::getMeta()['options']);

            $this->entityManager->beginTransaction();
            $this->entityManager->persist($block);
            $this->entityManager->flush();

            /**
             *
             *
             * @var ACL $acl
             */
            $acl = \EnjoysCMS\Core\Components\Helpers\ACL::registerAcl(
                $block->getBlockActionAcl(),
                $block->getBlockCommentAcl()
            );
            //$acl->setGroups();

            $groups = $this->entityManager->getRepository(Group::class)->findBy(
                ['id' => $this->serverRequest->post('groups', [])]
            )
            ;
            foreach ($groups as $group) {
                $acl->setGroups($group);
            }
            $this->entityManager->persist($acl);
            $this->entityManager->flush();
            $this->entityManager->commit();

            Redirect::http($this->urlGenerator->generate('admin/blocks'));
        } catch (OptimisticLockException | ORMException $e) {
            Error::code(500, $e->__toString());
        }
    }
}
