<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Setting extends BaseController
{
    /**
     * @var ObjectRepository
     */
    private ObjectRepository $settingRepository;

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        parent::__construct($twig, $serverRequest, $entityManager, $urlGenerator, $renderer);
        $this->settingRepository = $this->entityManager->getRepository(\EnjoysCMS\Core\Entities\Setting::class);
    }

    public function setting()
    {
        return $this->view(
            '@a/setting/setting.twig',
            $this->getContext(
                new \App\Module\Admin\Core\Setting(
                    $this->settingRepository,
                    $this->entityManager,
                    $this->serverRequest,
                    $this->urlGenerator,
                    $this->renderer
                )
            )
        );
    }

    /**
     * @Route(
     *     path="/admin/setting/add",
     *     name="admin/setting/add",
     *     options={
     *          "aclComment": "Добаление глобальной настройки"
     *     }
     * )
     */
    public function addSetting()
    {
        $form = new Form(['method' => 'post']);
        $form->text('var', 'var')->addRule(Rules::REQUIRED)->addRule(
            Rules::CALLBACK,
            'Настройка с таким id уже существует',
            function () {
                $check = $this->settingRepository->findOneBy(['var' => $this->serverRequest->post('var')]);
                if ($check === null) {
                    return true;
                }
                return false;
            }
        )
        ;
        $form->text('value', 'value');
        $form->select('type', 'type')->fill(
            [
                'text',
                'select',
                'radio',
                'textarea'
            ],
            true
        )->addRule(Rules::REQUIRED)
        ;;
        $form->text('params', 'params')->setDescription('json');
        $form->text('name', 'name')->addRule(Rules::REQUIRED);;
        $form->text('description', 'description');
        $form->submit('add');

        if ($form->isSubmitted()) {
            $setting = new \EnjoysCMS\Core\Entities\Setting();
            $setting->setVar($this->serverRequest->post('var'));
            $setting->setValue($this->serverRequest->post('value'));
            $setting->setType($this->serverRequest->post('type'));
            $setting->setParams($this->serverRequest->post('params'));
            $setting->setName($this->serverRequest->post('name'));
            $setting->setDescription($this->serverRequest->post('description'));

            $this->entityManager->persist($setting);
            $this->entityManager->flush();

            Redirect::http($this->urlGenerator->generate('admin/setting'));
        }


        $this->renderer->setForm($form);
        return $this->view(
            '@a/setting/add.twig',
            [
                'form' => $this->renderer,
                '_title' => 'Добавление настройки | Настройки | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get(
                        'sitename'
                    )
            ]
        );
    }
}
