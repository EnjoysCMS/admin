<?php


namespace App\Module\Admin\Core\Blocks;


use App\Blocks\Custom;
use App\Components\Helpers\Redirect;
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
    /**
     * @var mixed
     */
    private ?object $blockOptions = null;

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
                'name' => $this->block->getName(),
                'body' => $this->block->getBody(),
                'options' => $this->block->getOptionsKeyValue()
            ]
        );
        $form->text('name', 'Название');


        if ($this->block->getClass() === Custom::class) {
            $form->textarea('body', 'Контент');
        }


        if (null !== $this->block->getOptions()) {
            $form->header('Параметры (опции) блока');
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
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('admin/blocks'));
    }
}
