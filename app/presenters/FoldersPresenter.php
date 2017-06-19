<?php

namespace App\Presenters;

use Nette;
use Nette\Security;
use Nette\Application\UI\Form;


class FoldersPresenter extends BasePresenter {
    private $folders;
    private $collections;

    public function __construct(\App\Model\Folders $folders, \App\Model\Collections $collections) {
        $this->folders = $folders;
        $this->collections = $collections;
    }

    public function renderDefault() {
        $userData = $this->getUser();
        $this->template->userData = $userData;

        $folders = $this->folders->getFolders($userData->id);
        if(!$folders) {
            $this->error('No folders found for this user.');
        }

        $tags = $this->folders->getTags($folders);

        $this->template->folders = $folders;
        $this->template->tags = $tags;
        $this->template->collectionsNames = $this->collections->getCollectionsNames($userData->id);
        
    }

    public function handleCloseFolders() {
        /*
         * TO-DO
         * Redirect to parent
         */
        $this->redirect('Homepage:');
    }

    public function handleDeleteFolder() {
        if($this->isAjax()) {
            $data = (object)[];
            $data->id = $this->request->getPost('delete');
            $this->folders->setDeleteFolder($data);
            $this->redirect('Folders:');
        }
    }

    protected function createComponentSaveFolderForm() {
        $form = new Form;

        $form->addText('id', '');

        $form->addText('folder_name', '')
            ->setRequired('A folder must have a name.');

        $form->addText('tags', '');

        $form->addSubmit('send', '');

        $form->onSuccess[] = [$this, 'saveFolderFormSucceeded'];
        return $form;
    }

    public function saveFolderFormSucceeded($form) {
        $values = $form->getValues();
        $values->user_id = $this->getUser()->id;

        if($values->id != '')
            $this->folders->setEditFolder($values);
        else
            $this->folders->setNewFolder($values);

        $this->redirect('Folders:');
    }

}
