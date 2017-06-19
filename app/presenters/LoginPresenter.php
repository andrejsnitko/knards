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

        $new_user_id = $this->users->register($values);
        if($new_user_id) {
            $this->redirect('Homepage:');
        }
        /** TO-DO
         * Check for validity
         */
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

        $user_id = $this->users->authenticate($data);
        if($user_id) {
            $user->login($data[0], $data[1]);
            $this->redirect('Homepage:');
        }
        else $this->redirect('Login:');
        /** TO-DO
         * Error processing
         */
    }

}
