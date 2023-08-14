<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Widgets;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use EnjoysCMS\Core\Block\Entity\Widget;
use Psr\Http\Message\ServerRequestInterface;

final class Edit
{

    private Widget $widget;

    /**
     * @throws NotSupported
     * @throws NoResultException
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
        $this->widget = $this->em->getRepository(Widget::class)->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();
    }


    public function getForm(): Form
    {
        $options = $this->widget->getOptions();
        unset($options['gs']);

        $form = new Form();
        $form->setDefaults(
            function () use ($options) {
                $data = [];
                foreach ($options as $key => $value) {
                    $data[$key] = $value['value'] ?? (!is_array($value) ? $value : null) ?? null;
                }
                return $data;
            }
        );

        foreach ($options as $key => $value) {
            switch ($value['type'] ?? 'text') {
                case 'radio':
                    $form->radio($key, $value['title'] ?? ucfirst($key))
                        ->setDescription($value['description'] ?? '')
                        ->fill($value['data'] ?? [], true);
                    break;
                case 'select':
                    $form->select($key, $value['title'] ?? ucfirst($key))
                        ->setDescription($value['description'] ?? '')
                        ->fill($value['data'] ?? [], true);
                    break;
                case 'text':
                default:
                    $form->text($key, $value['title'] ?? ucfirst($key))
                        ->setDescription($value['description'] ?? '');
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
        $result = [];
        foreach ($this->request->getParsedBody() as $key => $value) {
            if (!in_array($key, array_keys($this->widget->getOptions()))) {
                continue;
            }
            $result[$key]['value'] = empty($value) ? null : $value;
        }

        $this->widget->setOptions(array_merge_recursive_distinct($this->widget->getOptions(), $result));
        $this->em->flush();
    }

    public function getWidget(): Widget
    {
        return $this->widget;
    }
}
