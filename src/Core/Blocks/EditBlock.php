<?php


namespace App\Module\Admin\Core\Blocks;


use App\Blocks\Custom;
use App\Components\Helpers\ACL;
use App\Components\Helpers\Redirect;
use App\Components\WYSIWYG\WYSIWYG;
use App\Entities\Blocks;
use App\Entities\Groups;
use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\WYSIWYG\TinyMCE\TinyMCE;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

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
    /**
     * @var mixed
     */
    private ?object $blockOptions = null;
    /**
     * @var Environment
     */
    private Environment $twig;
    private ?\App\Entities\ACL $acl;
    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $groupsRepository;

    public function __construct(
        Environment $twig,
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
        if (null === $block = $entityManager->getRepository(Blocks::class)->find($this->serverRequest->get('id'))) {
            throw new \InvalidArgumentException('Invalid block ID');
        }
        if(!($block instanceof Blocks)){
            throw new \InvalidArgumentException('Invalid block');
        }
        $this->block = $block;
        $this->acl = ACL::getAcl($this->block->getBlockActionAcl());
        $this->groupsRepository = $this->entityManager->getRepository(Groups::class);
    }

    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
        $wysiwyg = new WYSIWYG(new TinyMCE());
        $wysiwyg->setTwig($this->twig);

        return [
            'form' => $this->renderer,
            'block' => $this->block,
            'wysiwyg' => $wysiwyg->selector('#body')
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);




        $form->setDefaults(
            [
                'name' => $this->block->getName(),
                'body' => $this->block->getBody(),
                'options' => $this->block->getOptionsKeyValue(),
                'groups' => array_map(
                    function ($item) {
                        return $item->getId();
                    },
                    $this->acl->getGroups()->toArray()
                ),
            ]
        );
        $form->text('name', 'Название');


        if ($this->block->getClass() === Custom::class) {
            $form->textarea('body', 'Контент');
        }


        if (null !== $this->block->getOptions()) {
            foreach ($this->block->getOptions() as $key => $option) {
                if (isset($option['form']['type'])) {
                    switch ($option['form']['type']) {
                        case 'radio':
                            $form->radio(
                                "options[{$key}]",
                                (isset($option['name'])) ? $option['name'] : $key
                            )->setDescription($option['description'])->fill($option['form']['data']);
                            break;
                    }

                    continue;
                }
                $form->text("options[{$key}]", (isset($option['name'])) ? $option['name'] : $key)->setDescription(
                    $option['description']
                );
            }
        }

        $form->checkbox('groups', 'Права доступа')->fill(
           $this->groupsRepository->getGroupsArray()
        )->addRule(Rules::REQUIRED);

        $form->submit('send');

        return $form;
    }

    private function getBlockOptions(array $options): ?array
    {
        if (empty($options)) {
            return null;
        }

        $blockOptions = $this->block->getOptions();

        foreach ($options as $key => $value) {
            if (array_key_exists($key, $blockOptions)) {
                $blockOptions[$key]['value'] = $value;
            }
        }

        return $blockOptions;
    }

    private function doAction()
    {
        $this->block->setName($this->serverRequest->post('name'));
        $this->block->setBody($this->serverRequest->post('body'));
        $this->block->setOptions($this->getBlockOptions($this->serverRequest->post('options', [])));


        /** @var Groups $group */
        foreach ($this->groupsRepository->findAll() as $group) {
            if(in_array($group->getId(), $this->serverRequest->post('groups', []))){
                $this->acl->setGroups($group);
                continue;
            }
            $this->acl->removeGroups($group);

        }

        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('admin/blocks'));
//        Redirect::http();
    }
}
