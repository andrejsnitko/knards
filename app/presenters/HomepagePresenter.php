<?php

namespace App\Presenters;

use Nette;
use Nette\Security;


class HomepagePresenter extends BasePresenter {
    private $user;
    private $collections;

    protected function startup() {
        parent::startup();

        if(!$this->getUser()->isLoggedIn()) {
            if($this->getUser()->getLogoutReason() === Nette\Security\IUserStorage::INACTIVITY) {
                /**
                 * TO-DO
                 * Login due to inactivity
                 */
            }
            $this->redirect('Login:');
        }
    }

    public function __construct(\App\Model\Collections $collections) {
        $this->collections = $collections;
    }

    public function renderDefault() {
        $userData = $this->getUser();
        $this->template->userData = $userData;

        $this->template->collectionsNames = $this->collections->getCollectionsNames($userData->id);
    }
}
