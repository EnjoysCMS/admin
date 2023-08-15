<?php


namespace EnjoysCMS\Module\Admin\Settings;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use EnjoysCMS\Core\Setting as SettingComponent;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Setting
{

    private SettingComponent\Repository\Setting|EntityRepository $settingRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ServerRequestInterface $request,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        $this->settingRepository = $this->entityManager->getRepository(SettingComponent\Entity\Setting::class);
    }

    public function getForm(): Form
    {
        /** @var SettingComponent\Entity\Setting[] $settings */
        $settings = $this->settingRepository->findAll();

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


        foreach ($settings as $setting) {
            $name = $setting->getName();
            $name .= " <small>[{$setting->getVar()}]</small>";

            if ($setting->isRemovable()) {
                $name .= '<span class="float-right px-1"><a  class="small text-danger" href="' . $this->urlGenerator->generate(
                        '@admin_setting_delete',
                        ['id' => $setting->getVar()]
                    ) . '">удалить</a></span>';
            }

            $name .= '<span class="float-right px-1"><a class="small text-secondary" href="' . $this->urlGenerator->generate(
                    '@admin_setting_edit',
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
    public function doAction(): void
    {
        foreach ($this->request->getParsedBody() as $k => $v) {
            /** @var SettingComponent\Entity\Setting $item */
            $item = $this->settingRepository->find($k);
            if ($item === null) {
                continue;
            }
            $item->setValue($v);
            $this->entityManager->persist($item);
        }
        $this->entityManager->flush();
    }
}