<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\EntityManager;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Entities\Widget;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Widgets\ActivateWidget;
use EnjoysCMS\Module\Admin\Core\Widgets\Manage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

class Widgets extends AdminBaseController
{

    #[Route(
        path: '/admin/widgets/delete/{id}',
        name: 'admin/deletewidget',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Удаление виджетов'
        ]
    )]
    public function delete(): void
    {
    }

    #[Route(
        path: '/admin/widgets/clone/{id}',
        name: 'admin/clonewidget',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Клонирование виджетов'
        ]
    )]
    public function clone(): void
    {
    }

    #[Route(
        path: '/admin/widgets/edit/{id}',
        name: 'admin/editwidget',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Редактирование виджетов'
        ]
    )]
    public function edit(): void
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: '/admin/widgets/manage',
        name: 'admin/managewidgets',
        options: [
            'aclComment' => 'Просмотр не активированных виджетов'
        ]
    )]
    public function manage(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/widgets/manage.twig',
                $this->getContext($this->getContainer()->get(Manage::class))
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: '/admin/widgets/activate',
        name: 'admin/acivatewidget',
        options: [
            'aclComment' => 'Установка (активация) виджетов'
        ]
    )]
    public function activate(): void
    {
        $this->getContainer()->get(ActivateWidget::class)();
    }

    #[Route(
        path: '/admin/widgets/save',
        name: 'admin/save-widgets',
        options: [
            'comment' => '[ADMIN] Сохранение расположения виджетов'
        ],
        methods: ['post']
    )]
    public function save(ServerRequestWrapper $request, EntityManager $em)
    {
        $widgetsRepository = $em->getRepository(Widget::class);
        $data = json_decode($request->getRequest()->getBody()->getContents(), true)['data'];
        foreach ($data as $options) {


            /** @var Widget $widget */
            $widget = $widgetsRepository->find($options['id']);
            unset($options['id']);

            foreach ($options as $key => $option) {
                unset($options[$key]);
                $newKey = function($key){
                    return implode('-', array_map(function ($value){
                        return strtolower($value);
                    }, preg_split('/(?=[A-Z])/', $key, flags: PREG_SPLIT_NO_EMPTY)));
                };
                $options[$newKey($key)] = $option;
            }

            $widget->setOptions(array_merge($widget->getOptions(), ['gs' => $options]));
        }

        $em->flush();

        return $this->responseJson(
            'saved widgets'
        );
    }

}
