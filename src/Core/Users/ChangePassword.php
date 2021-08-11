<?php


namespace App\Module\Admin\Core\Users;


use App\Module\Admin\Exception\NotEditableUser;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use App\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\User;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChangePassword implements ModelInterface
{
    private User $user;
    private ObjectRepository|EntityRepository $usersRepository;
    private RendererInterface $renderer;


    /**
     * @throws NotEditableUser
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->usersRepository = $this->em->getRepository(User::class);
        $this->user = $this->getUser();
        $this->renderer = new Bootstrap4();
    }

    /**
     * @throws NotEditableUser
     * @throws NoResultException
     */
    public function getUser(): User
    {
        $user = $this->usersRepository->find(
            $this->serverRequest->get('id')
        );

        if ($user === null) {
            throw new NoResultException();
        }

        if (!$user->isEditable()){
            throw new NotEditableUser('User is not editable');
        }

        return $user;
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->updatePassword();
        }

        $this->renderer->setForm($form);

        return [
            '_title' => 'Смена пароля пользователя | Пользователи | Admin | ' . Setting::get('sitename'),
            'form' => $this->renderer,
            'username' => $this->user->getLogin(),
            'user' => $this->user
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(
            [
                'method' => 'POST'
            ]
        );

        $form->text('password', 'Новый пароль')->addRule(Rules::REQUIRED);


        $form->submit('submit1', 'Сменить пароль');

        return $form;
    }

    private function updatePassword()
    {
        $this->user->genAdnSetPasswordHash($this->serverRequest->post('password'));
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('admin/users'));
    }
}
