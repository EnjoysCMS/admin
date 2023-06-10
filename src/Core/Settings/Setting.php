<?php


namespace EnjoysCMS\Module\Admin\Core\Settings;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Setting implements ModelInterface
{

    /**
     * @var ObjectRepository
     */
    private ObjectRepository $settingRepository;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private RedirectInterface $redirect
    ) {
        $this->settingRepository = $this->entityManager->getRepository(\EnjoysCMS\Core\Entities\Setting::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('admin/setting', emit: true);
        }
        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer,
            '_title' => 'Настройки | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename'),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                'Глобальные настройки сайта',
            ],
        ];
    }

    private function getForm(): Form
    {
        $settings = (array)$this->settingRepository->findAll();

        $form = new Form();
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
         * @var \EnjoysCMS\Core\Entities\Setting $setting
         */
        foreach ($settings as $setting) {
            $name = $setting->getName();
            $name .= " <small>[{$setting->getVar()}]</small>";

            if ($setting->isRemovable()) {
                $name .= '<span class="float-right px-1"><a  class="small text-danger" href="' . $this->urlGenerator->generate(
                        'admin/setting/delete',
                        ['id' => $setting->getVar()]
                    ) . '">удалить</a></span>';
            }

            $name .= '<span class="float-right px-1"><a class="small text-secondary" href="' . $this->urlGenerator->generate(
                    'admin/setting/edit',
                    ['id' => $setting->getVar()]
                ) . '">редактировать</a></span>';


            switch ($setting->getType()) {
                case 'radio':
                    $params = json_decode($setting->getParams(), true);
                    $form->radio($setting->getVar(), $name)
                        ->addClass('w-100', Form::ATTRIBUTES_LABEL)
                        ->setDescription((string)$setting->getDescription())
                        ->fill((!is_array($params)) ? [] : $params);

                    unset($data);
                    break;
                case 'select':
                    $params = json_decode($setting->getParams(), true);
                    $form->select($setting->getVar(), $name)
                        ->addClass('w-100', Form::ATTRIBUTES_LABEL)
                        ->setDescription((string)$setting->getDescription())
                        ->fill((!is_array($params)) ? [] : $params);
                    break;
                case 'textarea':
                    $form->textarea($setting->getVar(), $name)
                        ->addClass('w-100', Form::ATTRIBUTES_LABEL)
                        ->setDescription((string)$setting->getDescription());
                    break;
                case 'text':
                default:
                    $form->text($setting->getVar(), $name)
                        ->addClass('w-100', Form::ATTRIBUTES_LABEL)
                        ->setDescription((string)$setting->getDescription());
                    break;
            }
        }
        $form->submit('save', 'Сохранить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        foreach ($this->request->getParsedBody() as $k => $v) {
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
    }
}
