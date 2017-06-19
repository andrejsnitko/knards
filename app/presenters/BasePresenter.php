<?php


namespace App\Presenters;

use Nette;
use Nette\Security\User;



abstract class BasePresenter extends Nette\Application\UI\Presenter {

    public function handleSignOut() {
        $this->getUser()->logout();
        $this->redirect('Homepage:');
    }

}
