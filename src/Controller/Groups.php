<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Groups extends BaseController
{

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $groupsRepository;

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $em,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($twig, $serverRequest, $em, $urlGenerator);
        $this->groupsRepository = $em->getRepository(\App\Entities\Groups::class);
    }

    public function list(): string
    {
        return $this->view(
            '@a/groups/list.twig',
            ['groups' => $this->groupsRepository->findAll()]
        );
    }


}
