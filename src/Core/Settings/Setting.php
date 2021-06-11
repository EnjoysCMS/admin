<?php


namespace App\Module\Admin\Core\Settings;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Setting implements ModelInterface
{


    /**
     * @var ObjectRepository
     */
    private ObjectRepository $settingRepository;
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

        $this->settingRepository = $this->entityManager->getRepository(\EnjoysCMS\Core\Entities\Setting::class);
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
            '_title' => 'Настройки | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename')
        ];
    }

    private function getForm()
    {
        $settings = (array)$this->settingRepository->findAll();

        $form = new Form(['method' => 'post']);
        $form->setDefaults(
            function () use ($settings) {
                $data = [];
                foreach ($settings as $setting) {
                    $data[$setting->getVar()] = $setting->getValue();
                }
                return $data;
            }
        );


        /**
         *
         *
         * @var \EnjoysCMS\Core\Entities\Setting $setting
         */
        foreach ($settings as $setting) {
            $name = $setting->getName();
            $name .= " <small>[{$setting->getVar()}]</small>";
            $name .= '<span class="float-right px-1"><a href="' . $this->urlGenerator->generate(
                    'admin/setting/delete',
                    ['id' => $setting->getVar()]
                ) . '">удалить</a></span>';

            $name .= '<span class="float-right px-1"><a href="' . $this->urlGenerator->generate(
                    'admin/setting/edit',
                    ['id' => $setting->getVar()]
                ) . '">редактировать</a></span>';



            switch ($setting->getType()) {
                case 'radio':
                    $params = json_decode($setting->getParams(), true);
                    $form->radio($setting->getVar(), $name)
                        ->addClass('w-100', Form::ATTRIBUTES_LABEL)
                        ->setDescription((string)$setting->getDescription())
                        ->fill((!is_array($params)) ? [] : $params)
                    ;

                    unset($data);
                    break;
                case 'select':
                    $params = json_decode($setting->getParams(), true);
                    $form->select($setting->getVar(), $name)
                        ->addClass('w-100', Form::ATTRIBUTES_LABEL)
                        ->setDescription((string)$setting->getDescription())
                        ->fill((!is_array($params)) ? [] : $params)
                    ;
                    break;
                case 'textarea':
                    $form->textarea($setting->getVar(), $name)
                        ->addClass('w-100', Form::ATTRIBUTES_LABEL)
                        ->setDescription((string)$setting->getDescription())
                    ;
                    break;
                case 'text':
                default:
                    $form->text($setting->getVar(), $name)
                        ->addClass('w-100', Form::ATTRIBUTES_LABEL)
                        ->setDescription((string)$setting->getDescription())
                    ;
                    break;
            }
        }
        $form->submit('save', 'Сохранить');
        return $form;
        // die();
    }

    private function doAction()
    {
        foreach ($this->serverRequest->post() as $k => $v) {
            /**
             *
             *
             * @var \EnjoysCMS\Core\Entities\Setting $item
             */
            if (null === $item = $this->settingRepository->find($k)) {
                continue;
            }
            $item->setValue($v);
            $this->entityManager->persist($item);
        }
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('admin/setting'));
    }
}
