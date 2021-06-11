<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Settings\AddSetting;
use App\Module\Admin\Core\Settings\EditSetting;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Setting extends BaseController
{

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        parent::__construct($twig, $serverRequest, $entityManager, $urlGenerator, $renderer);
    }

    public function setting()
    {
        return $this->view(
            '@a/setting/setting.twig',
            $this->getContext(
                new \App\Module\Admin\Core\Settings\Setting(
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
        return $this->view(
            '@a/setting/add.twig',
            $this->getContext(
                new AddSetting(
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
     *     path="/admin/setting/edit",
     *     name="admin/setting/edit",
     *     options={
     *          "aclComment": "Изменение глобальной настройки"
     *     }
     * )
     */
    public function editSetting()
    {
        return $this->view(
            '@a/setting/add.twig',
            $this->getContext(
                new EditSetting(
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
     *     path="/admin/setting/delete",
     *     name="admin/setting/delete",
     *     options={
     *          "aclComment": "Удаление глобальной настройки"
     *     }
     * )
     */
    public function deleteSetting()
    {
        if (null === $setting = $this->entityManager->getRepository(\EnjoysCMS\Core\Entities\Setting::class)->find(
                $this->serverRequest->get('id')
            )) {
            Error::code(404);
        }

//        if (!$setting->isRemovable()) {
//            throw new Exception('Block not removable');
//        }


        $this->entityManager->remove($setting);
        $this->entityManager->flush();


        Redirect::http($this->urlGenerator->generate('admin/setting'));
    }
}
