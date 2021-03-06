<?php

namespace App\Presenters;

use Nette;
use Nette\Security\User;
use Nette\Application\UI\Form;



class LoginPresenter extends Nette\Application\UI\Presenter {
    private $users;
    private $user;

    public function __construct(\App\Model\Users $users) {
        $this->users = $users;
    }

    protected function createComponentSigninForm() {
        $form = new Form;
        $form->addText('username', 'Username:')
            ->setRequired('Please enter your username.');

        $form->addEmail('email', 'Email:')
            ->setRequired('Please enter your email.');

        $form->addPassword('password', 'Password:')
            ->setRequired('Please enter your password.');

        $form->addSubmit('send', 'Sign in');

        $form->onSuccess[] = [$this, 'signinFormSucceeded'];
        return $form;
    }

    public function signinFormSucceeded($form) {
        $values = $form->getValues();

        try {
            $new_user_id = $this->users->register($values);
        } catch(Nette\Database\UniqueConstraintViolationException $e) {
            $new_user_id = null;
            $form->addError($e->getMessage());
        }

        if($new_user_id) {
            $this->redirect('Homepage:');
        }
    }

    protected function createComponentLoginForm() {
        $form = new Form;
        $form->addText('username', 'Username:')
            ->setRequired('Please enter your username.');

        $form->addPassword('password', 'Password:')
            ->setRequired('Please enter your password.');

        $form->addSubmit('send', 'Log in');

        $form->onSuccess[] = [$this, 'loginFormSucceeded'];
        return $form;
    }

    public function loginFormSucceeded($form) {
        $values = $form->getValues();
        $data = [$values->username, $values->password];
        $user = $this->getUser();

        try {
            $user_id = $this->users->authenticate($data);
            
            if($user_id) {
                $user->login($data[0], $data[1]);
                $this->redirect('Homepage:');
            } else { 
                $form->addError('auth-error');
            }

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('auth-error');
        }
    }
}
