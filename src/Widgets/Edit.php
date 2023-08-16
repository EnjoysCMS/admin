<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Widgets;


use DI\Container;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use EnjoysCMS\Core\Block\Entity\Widget;
use Invoker\Exception\NotCallableException;
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
        private readonly Container $container,
    ) {
        $this->widget = $this->em->getRepository(Widget::class)->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();
    }


    public function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'options' => $this->widget->getOptionsKeyValue(),
        ]);

        foreach ($this->widget->getOptions() as $key => $option) {
            if ($key === 'gs') {
                continue;
            }
            $type = $option['type'] ?? null;

            if ($type) {
                $data = $option['data'] ?? [null];
                try {
                    if (is_array($data) && !array_key_exists(0, $data)) {
                        throw new NotCallableException();
                    }
                    $data = $this->container->call($data);
                } catch (NotCallableException) {
                    //skip
                }
                switch ($type) {
                    case 'radio':
                        $form->radio(
                            "options[$key]",
                            (isset($option['name'])) ? $option['name'] : $key
                        )->setDescription(
                            $option['description'] ?? ''
                        )->fill($data, true);
                        break;
                    case 'checkbox':
                        $form->checkbox(
                            "options[$key]",
                            (isset($option['name'])) ? $option['name'] : $key
                        )->setDescription(
                            $option['description'] ?? ''
                        )->fill($data, true);
                        break;
                    case 'select':
                        $form->select(
                            "options[$key]",
                            (isset($option['name'])) ? $option['name'] : $key
                        )->setDescription(
                            $option['description'] ?? ''
                        )->fill($data, true);
                        break;
                    case 'textarea':
                        $form->textarea(
                            "options[$key]",
                            (isset($option['name'])) ? $option['name'] : $key
                        )->setDescription($option['description'] ?? '');
                        break;
                    case 'file':
                        $form->file("options[$key]", $option['name'] ?? $key)
                            ->setDescription($option['description'] ?? '')
                            ->setMaxFileSize(
                                $data['max_file_size'] ?? iniSize2bytes(ini_get('upload_max_filesize'))
                            )
                            ->setAttributes(AttributeFactory::createFromArray($data['attributes'] ?? []));
                        break;
                }

                continue;
            }
            $form->text("options[$key]", (isset($option['name'])) ? $option['name'] : $key)->setDescription(
                $option['description'] ?? ''
            );
        }

        $form->submit('save');

        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): void
    {
        $options = $this->widget->getOptions();
        $options->setValues($this->request->getParsedBody()['options'] ?? []);

        $this->widget->setOptions($options);

        $this->em->flush();
    }

    public function getWidget(): Widget
    {
        return $this->widget;
    }
}
