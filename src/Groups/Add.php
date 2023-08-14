<?php

namespace EnjoysCMS\Module\Admin\Groups;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\AccessControl\AccessControl;
use EnjoysCMS\Core\Users\Entity\Group;
use Psr\Http\Message\ServerRequestInterface;

class Add
{

    private EntityRepository|\EnjoysCMS\Core\Users\Repository\Group $groupsRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly ACList $ACList,
        private readonly AccessControl $accessControl
    ) {
        $this->groupsRepository = $this->em->getRepository(Group::class);
    }



    /**
     * @throws ExceptionRule
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getForm(): Form
    {
        $form = new Form();

        $form->setDefaults(
            [
                'acl' => array_map(
                    function ($item) {
                        return $item->getId();
                    },
                    $this->accessControl->getManage()->getAccessActionsForGroup(
                        $this->request->getQueryParams()['by'] ?? 0
                    )
                ),
                'by' => $this->request->getQueryParams()['by'] ?? null
            ]
        );

        $queryString = $this->request->getQueryParams();
        unset($queryString['by']);

        $urlModify = $this->request->getUri()->withQuery(
            (empty($queryStrings)) ? sprintf('t=%d', time()) : $queryStrings
        );

        $form->select('by', 'Заполнить права доступа по...')
            ->setDescription('')
            ->fill(
                [''] + $this->groupsRepository->getListGroupsForSelectForm()
            )->setAttribute(AttributeFactory::create('onchange', "location.href='$urlModify&by=' + this.value;"));

        $form->header('Информация о группе');

        $form->text('name', 'Название')
            ->addRule(
                Rules::CALLBACK,
                'Название группы должно быть уникальным',
                function () {
                    return null === $this->groupsRepository->findOneBy(
                            ['name' => $this->request->getParsedBody()['name'] ?? null]
                        );
                }
            )->addRule(Rules::REQUIRED);

        $form->textarea('description', 'Описание группы');

        $form->header('Права доступа');
        $i = 0;
        $aclsForCheckbox = $this->ACList->getArrayForCheckboxForm();
        foreach ($aclsForCheckbox as $label => $item) {
            $form->checkbox(str_repeat(' ', $i++) . "acl", $label)->fill($item);
        }


        $form->submit('sbmt1', 'Добавить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): void
    {
        $group = new Group();
        $group->setName($this->request->getParsedBody()['name'] ?? '');
        $group->setDescription($this->request->getParsedBody()['description'] ?? '');
        $group->setStatus(1);
        $group->setSystem(false);

        foreach ($this->accessControl->getManage()->getList() as $acl) {
            if (in_array($acl->getId(), $this->request->getParsedBody()['acl'] ?? [])) {
                $acl->addGroup($group);
                continue;
            }
            $acl->removeGroup($group);
        }
        $this->em->persist($group);
        $this->em->flush();
    }


}
