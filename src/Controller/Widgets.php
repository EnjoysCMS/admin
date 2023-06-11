<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Components\Auth\Identity;
use EnjoysCMS\Core\Entities\Widget;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Widgets\ActivateWidget;
use EnjoysCMS\Module\Admin\Core\Widgets\Edit;
use EnjoysCMS\Module\Admin\Core\Widgets\Manage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
    public function delete(ServerRequestInterface $request, EntityManager $em, Identity $identity): ResponseInterface
    {
        try {
            $repository = $em->getRepository(Widget::class);
            /** @var Widget|null $widget */
            $widget = $repository->findOneBy([
                'id' => $request->getAttribute('id'),
                'user' => $identity->getUser()
            ]);
            if ($widget === null) {
                return $this->jsonResponse('The widget was not found, it may have been deleted')->withStatus(404);
            }
            $em->remove($widget);
            $em->flush();
        } catch (\Throwable $e) {
            return $this->jsonResponse(
                $e->getMessage()
            )->withStatus(500);
        }
        return $this->jsonResponse(sprintf('Widget with id = %d removed', $request->getAttribute('id')));
    }

    /**
     * @throws OptimisticLockException
     * @throws NoResultException
     * @throws ORMException
     */
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
    public function clone(
        EntityManager $em,
        ServerRequestInterface $request,
        RedirectInterface $redirect,
    ): ResponseInterface {
        $widget = $em->getRepository(Widget::class)->find($request->getAttribute('id'));
        if ($widget === null) {
            throw new NoResultException();
        }
        $newWidget = clone $widget;
        $em->persist($newWidget);
        $em->flush();

        return $redirect->toRoute('admin/index');
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
    public function edit(Edit $edit): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/widgets/edit.twig',
                $edit->getContext()
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
    public function manage(Manage $manage): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/widgets/manage.twig',
                $manage->getContext()
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
    public function activate(ActivateWidget $activateWidget): ResponseInterface
    {
        return $activateWidget();
    }

    #[Route(
        path: '/admin/widgets/save',
        name: 'admin/save-widgets',
        options: [
            'comment' => '[ADMIN] Сохранение расположения виджетов'
        ],
        methods: ['post']
    )]
    public function save(ServerRequestInterface $request, EntityManager $em): ResponseInterface
    {
        try {
            $widgetsRepository = $em->getRepository(Widget::class);
            $data = json_decode($request->getBody()->__toString(), true)['data'];
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

            return $this->jsonResponse(
                'Расположение и размер виджетов успешно сохранены'
            );
        } catch (\Throwable $e) {
            return $this->jsonResponse(
                $e->getMessage()
            )->withStatus(500);
        }
    }

}
