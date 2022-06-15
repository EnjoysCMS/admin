<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\EntityManager;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Entities\Widget;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Widgets\ActivateWidget;
use EnjoysCMS\Module\Admin\Core\Widgets\Edit;
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
        ],
        methods: ['post']
    )]
    public function delete(ServerRequestWrapper $request, EntityManager $em): ResponseInterface
    {
        try {
            $repository = $em->getRepository(Widget::class);
            /** @var Widget|null $widget */
            $widget = $repository->find($request->getAttributesData('id'));
            if ($widget === null) {
                return $this->responseJson('The widget was not found, it may have been deleted')->withStatus(404);
            }
            $em->remove($widget);
            $em->flush();
        } catch (\Throwable $e) {
            return $this->responseJson(
                $e->getMessage()
            )->withStatus(500);
        }
        return $this->responseJson(sprintf('Widget with id = %d removed', $request->getAttributesData('id')));
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
        throw new \Exception('Still Disable Clone Widgets');
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
    public function edit(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/widgets/edit.twig',
                $this->getContext($this->getContainer()->get(Edit::class))
            )
        );
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
    public function save(ServerRequestWrapper $request, EntityManager $em): ResponseInterface
    {
        try {
            $widgetsRepository = $em->getRepository(Widget::class);
            $data = json_decode($request->getRequest()->getBody()->getContents(), true)['data'];
            foreach ($data as $options) {
                /** @var Widget $widget */
                $widget = $widgetsRepository->find($options['id']);
                unset($options['id']);

                foreach ($options as $key => $option) {
                    unset($options[$key]);
                    $newKey = function ($key) {
                        return implode(
                            '-',
                            array_map(function ($value) {
                                return strtolower($value);
                            }, preg_split('/(?=[A-Z])/', $key, flags: PREG_SPLIT_NO_EMPTY))
                        );
                    };
                    $options[$newKey($key)] = $option;
                }

                $widget->setOptions(array_merge($widget->getOptions(), ['gs' => $options]));
            }

            $em->flush();

            return $this->responseJson(
                'Расположение и размер виджетов успешно сохранены'
            );
        } catch (\Throwable $e) {
            return $this->responseJson(
                $e->getMessage()
            )->withStatus(500);
        }
    }

}
