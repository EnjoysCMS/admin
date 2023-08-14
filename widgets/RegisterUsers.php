<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Widgets;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Block\AbstractWidget;
use EnjoysCMS\Core\Block\Annotation\Widget;
use EnjoysCMS\Core\Users\Entity\User;
use Twig\Environment;

#[Widget(
    name: 'Зарегистрированные пользователи',
    options: [
        'title' => 'Зарегистрированные пользователи',
        'background-gradient' => [
            'value' => 'No',
            'type' => 'type',
            'data' => ["No", "Yes"]
        ],
        'background' => [
            'value' => 'warning',
            'type' => 'select',
            'data' => [
                'primary',
                'secondary',
                'success',
                'danger',
                'warning',
                'info',
                'light',
                'dark',
                'white',
                'transparent'
            ]
        ],
        'gs' => [
            'min-h' => 9,
            'max-h' => 11,
            'min-w' => 2,
            'max-w' => 5,
            'h' => 9,
            'w' => 2,
        ]
    ]
)]
final class RegisterUsers extends AbstractWidget
{

    public function __construct(private Environment $twig, private EntityManager $em)
    {
    }

    public function view(): string
    {
        return $this->twig->render('@a/widgets/template/register_users.twig', [
            'widget' => $this->getEntity(),
            'countUsers' => count($this->em->getRepository(User::class)->findAll())
        ]);
    }
}
