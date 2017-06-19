<?php

namespace App\Presenters;

use Nette;
use Nette\Security;
use Nette\Application\UI\Form;


class CollectionsPresenter extends BasePresenter {
    private $folders;
    private $cards;
    private $collections;

    public function __construct(\App\Model\Folders $folders, \App\Model\Cards $cards, \App\Model\Collections $collections) {
        $this->folders = $folders;
        $this->cards = $cards;
        $this->collections = $collections;
    }

    public function renderDefault() {
        $userData = $this->getUser();
        $this->template->userData = $userData;

        $this->template->collections = $this->collections->getCollections($userData->id);
        $this->template->collectionsNames = $this->collections->getCollectionsNames($userData->id);
    }

    public function handleDeleteCollection() {
        if($this->isAjax()) {
            $data['collection_id'] = $this->request->getPost('delete');
            $this->collections->setDeleteRuleset($data);
            $this->redirect('Collections:');
        }
    }

    protected function createComponentSaveRulesetForm() {
        $form = new Form;

        $form->addText('id', '');

        $form->addText('ruleset_name', '')
            ->setRequired('A collection must have a name.');

        $form->addText('tags', '');
        $form->addText('users', '');
        $form->addText('date_from', '');
        $form->addText('date_to', '');

        $form->addSubmit('send', '');

        $form->onSuccess[] = [$this, 'saveRulesetFormSucceeded'];
        return $form;
    }

    public function saveRulesetFormSucceeded($form) {
        $values = $form->getValues();
        $values->user_id = $this->getUser()->id;
        if($values->tags != '')
            $values->tags = explode(',', $values->tags);
        else
            $values->tags = null;
        if($values->users != '')
            $values->users = explode(',', $values->users);
        else
            $values->users = null;
        if($values->date_from != '') {
            $values->date_from = str_replace('/', '-', $values->date_from);
            $values->date_from = date("Y-m-d", strtotime($values->date_from));
        }
        else
            $values->date_from = null;
        if($values->date_to != '') {
            $values->date_to = str_replace('/', '-', $values->date_to);
            $values->date_to = date("Y-m-d", strtotime($values->date_to));
        }
        else
            $values->date_to = null;

        if($values->id != '')
            $this->collections->setEditRuleset($values);
        else
            $this->collections->setNewRuleset($values);

        $this->redirect('Collections:');
    }

}
