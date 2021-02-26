<?php


namespace App\Modules\Admin\Core\Users;


use App\Components\Helpers\Error;
use App\Components\Helpers\Redirect;
use App\Modules\Admin\Core\ModelInterface;
use App\Entities\Users;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Delete implements ModelInterface
{
    /**
     * @var EntityManager
     */
    private EntityManager $em;
    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;
    private ObjectRepository $usersRepository;
    private ?Users $user;

    public function __construct(
        EntityManager $em,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        ObjectRepository $usersRepository,
        RendererInterface $renderer
    ) {
        $this->em = $em;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
        $this->usersRepository = $usersRepository;

        $this->user = $this->usersRepository->find(
            $this->serverRequest->get('id')
        );

        if ($this->user === null || !$this->user->isEditable()) {
            Error::code(404);
        }
    }

    public function getContext(): array
    {
        $form = $this->getForm();


        if ($form->isSubmitted()) {
            $this->deleteUser();
        }

        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer,
            'username' => $this->user->getLogin(),
            'user' => $this->user,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/users') => 'Список пользователей',
                'Удаление пользователя',
            ]
        ];
    }

    private function deleteUser()
    {
        $this->em->remove($this->user);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('admin/users'));
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setMethod('post');
        $form->text('check-delete')->addClass('d-none')->setAttribute('disabled')->addRule(
            Rules::CALLBACK,
            'Этого пользователя нельзя удалить',
            function () {
                $total_admins = $this->usersRepository->createQueryBuilder('u')
                    ->select('COUNT(u) as cnt')
                    ->join('u.groups', 'g')
                    ->where('g.id = :id')
                    ->setParameter('id', \App\Entities\Users::ADMIN_GROUP_ID)
                    ->getQuery()
                    ->getSingleResult()['cnt'];

                if ($this->user->isAdmin() && $total_admins <= 1) {
                    return false;
                }

                return true;
            }
        );
        $form->submit('confirm-delete', 'Удалить')->addClass('btn-danger');
        return $form;
    }
}
