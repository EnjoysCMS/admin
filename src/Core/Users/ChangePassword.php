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

class ChangePassword implements ModelInterface
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
     * @var ObjectRepository
     */
    private ObjectRepository $usersRepository;
    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;
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
        $this->usersRepository = $usersRepository;
        $this->renderer = $renderer;

        $this->user = $this->usersRepository->find(
            $this->serverRequest->get('id')
        );

        if ($this->user === null) {
            Error::code(404);
        }
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->updatePassword();
        }

        $this->renderer->setForm($form);

        return [
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
