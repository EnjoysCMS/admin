<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Widgets;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Config\Config;
use Enjoys\Config\Parse\YAML;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Block\Entity\Widget;
use EnjoysCMS\Core\Block\WidgetCollection;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Admin\AdminController;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/admin/widgets', '@admin_widgets_')]
class Controller extends AdminController
{

    #[Route('/delete/{id}',
        name: 'delete',
        requirements: [
            'id' => '\d+'
        ],
        methods: ['post'],
        comment: 'Удаление виджетов'
    )]
    public function delete(EntityManager $em, Identity $identity): ResponseInterface
    {
        try {
            $repository = $em->getRepository(Widget::class);
            /** @var Widget|null $widget */
            $widget = $repository->findOneBy([
                'id' => $this->request->getAttribute('id'),
                'user' => $identity->getUser()
            ]);
            if ($widget === null) {
                return $this->jsonResponse('The widget was not found, it may have been deleted')->withStatus(404);
            }
            $em->remove($widget);
            $em->flush();
        } catch (Throwable $e) {
            return $this->jsonResponse(
                $e->getMessage()
            )->withStatus(500);
        }
        return $this->jsonResponse(sprintf('Widget with id = %d removed', $this->request->getAttribute('id')));
    }

    /**
     * @throws OptimisticLockException
     * @throws NoResultException
     * @throws ORMException
     */
    #[Route('/clone/{id}',
        name: 'clone',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Клонирование виджетов'
    )]
    public function clone(
        EntityManager $em,
    ): ResponseInterface {
        $widget = $em->getRepository(Widget::class)->find($this->request->getAttribute('id'));
        if ($widget === null) {
            throw new NoResultException();
        }
        $newWidget = clone $widget;
        $em->persist($newWidget);
        $em->flush();

        return $this->redirect->toRoute('@admin_index');
    }

    /**
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Route('/edit/{id}',
        name: 'edit',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Редактирование виджетов'
    )]
    public function edit(Edit $edit, \EnjoysCMS\Module\Admin\Config $config): ResponseInterface
    {
        $this->breadcrumbs->add('admin/managewidgets', 'Менеджер виджетов')
            ->setLastBreadcrumb('Редактирование виджета');

        $form = $edit->getForm();
        if ($form->isSubmitted()) {
            $edit->doAction();
            return $this->redirect->toRoute('@admin_index');
        }

        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/widgets/edit.twig',
                [
                    'form' => $rendererForm->output(),
                    'widget' => $edit->getWidget(),
                ]
            )
        );
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws NotSupported
     * @throws Exception
     */
    #[Route(
        name: 'manage',
        comment: 'Просмотр не активированных виджетов'
    )]
    public function manage(EntityManager $em, Identity $identity, WidgetCollection $widgetCollection): ResponseInterface
    {
        $this->breadcrumbs
            ->setLastBreadcrumb('Менеджер виджета');

        $installedWidgets = array_map(
            function ($widget) {
                return $widget->getClass();
            },
            $em->getRepository(Widget::class)->findBy([
                'user' => $identity->getUser()
            ])
        );

        $allWidgets = new Config();

        $configs = array_merge(
            [getenv('ROOT_PATH') . '/app/widgets.yml'],
            glob(getenv('ROOT_PATH') . '/modules/*/widgets.yml'),
        );

        foreach ($configs as $config) {
            $allWidgets->addConfig($config, [], YAML::class);
        }

        return $this->response(
            $this->twig->render(
                '@a/widgets/manage.twig',
                [
                    'allowedWidgets' => $widgetCollection,
                    'installedWidgets' => $installedWidgets,
                ]
            )
        );
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    #[Route('/activate',
        name: 'activate',
        comment: 'Установка (активация) виджетов'
    )]
    public function activate(
        EntityManager $em,
        WidgetCollection $widgetCollection,
        Identity $identity,
    ): ResponseInterface {
        $class = $this->request->getQueryParams()['class'] ?? '';
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class not found: %s', $class));
        }
        $reflectionClass = new ReflectionClass($class);

        $widgetAnnotation = $widgetCollection->getAnnotation(
            $reflectionClass
        ) ?? throw new InvalidArgumentException(
            sprintf('Class "%s" not supported', $reflectionClass->getName())
        );

        $widget = new Widget();
        $widget->setName($widgetAnnotation->getName());
        $widget->setClass($widgetAnnotation->getClassName());
        $widget->setOptions($widgetAnnotation->getOptions());
        $widget->setUser($identity->getUser());

        $em->persist($widget);
        $em->flush();


//        $this->ACL->register(
//            $widget->getWidgetActionAcl(),
//            $widget->getWidgetCommentAcl()
//        );
        return $this->redirect->toRoute('@admin_index');
    }

    #[Route('/save',
        name: 'save',
        methods: ['post'],
        comment: '[ADMIN] Сохранение расположения виджетов'
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

                $widget->setOptions(array_merge($widget->getOptions()->toArray(), ['gs' => $options]));
            }

            $em->flush();

            return $this->jsonResponse(
                'Расположение и размер виджетов успешно сохранены'
            );
        } catch (Throwable $e) {
            return $this->jsonResponse(
                $e->getMessage()
            )->withStatus(500);
        }
    }

}
