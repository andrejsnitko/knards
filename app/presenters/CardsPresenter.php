<?php

namespace App\Presenters;

use Nette;
use Nette\Security;
use Nette\Application\UI\Form;


class CardsPresenter extends BasePresenter {
    private $folders;
    private $cards;
    private $collections;

    public function __construct(\App\Model\Folders $folders, \App\Model\Cards $cards, \App\Model\Collections $collections) {
        $this->folders = $folders;
        $this->cards = $cards;
        $this->collections = $collections;
    }

    public function renderNew() {
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

    public function renderList() {
        $userData = $this->getUser();
        $this->template->userData = $userData;

        $folders = $this->folders->getFolders($userData->id);
        if(!$folders) {
            $this->error('No folders found for this user.');
        }

        $tags = $this->folders->getTags($folders);

        $cards = $this->cards->getCards($userData->id);
        $card_tags = array();
        if($cards)
            foreach($cards as $index => $card)
                $card_tags[$index]['tags'] = $this->cards->getCardTags($index);
        $this->template->folders = $folders;
        $this->template->tags = $tags;
        $this->template->cards = $cards;
        $this->template->card_tags = $card_tags;
        $this->template->collectionsNames = $this->collections->getCollectionsNames($userData->id);
    }

    public function renderEdit($card_id) {
        $userData = $this->getUser();
        $this->template->userData = $userData;

        $folders = $this->folders->getFolders($userData->id);
        if(!$folders) {
            $this->error('No folders found for this user.');
        }

        $tags = $this->folders->getTags($folders);
        $card = $this->cards->getCard($card_id);
        $card_tags = $this->cards->getCardTags($card_id);
        if($card_tags != '')
            $card_tags = explode(',', $card_tags);
        else
            $card_tags = null;

        $this->template->folders = $folders;
        $this->template->tags = $tags;
        $this->template->card = $card;
        $this->template->card_tags = $card_tags;
        $this->template->collectionsNames = $this->collections->getCollectionsNames($userData->id);
    }

    public function renderRevise($collectionId) {
        $userData = $this->getUser();
        $this->template->userData = $userData;

        $this->collections->prepareCollection($collectionId);
        $this->template->collectionName = $this->collections->getCollectionName($collectionId);
        $this->template->collectionsNames = $this->collections->getCollectionsNames($userData->id);
    }

    public function handleContinue($collectionId) {
        $userData = $this->getUser();
        $data = (object)[];
        $data = $this->request->getPost('cards');
        $this->collections->updateCollection($data, $userData->id);
        $this->redirect('Homepage:');
    }

    public function handleCardDelete() {
        $this->redirect('Homepage:');
    }

    protected function createComponentSaveCardForm() {
        $form = new Form;
        $form->addText('tags', '')
            ->setRequired('A card must have at least one tag assigned to it.');

        $form->addTextArea('text', '')
            ->setRequired('Cannot save an empty card.');

        $form->addText('private', '');

        $form->addSubmit('send', 'Save');

        $form->onSuccess[] = [$this, 'saveCardFormSucceeded'];
        return $form;
    }

    public function saveCardFormSucceeded($form) {
        $values = $form->getValues();
        $values['user_id'] = $this->getUser()->id;

        $this->cards->saveCard($values);

        $this->redirect('Cards:new');
    }

    protected function createComponentEditCardForm() {
        $form = new Form;
        $form->addText('id', '');

        $form->addText('tags', '')
            ->setRequired('A card must have at least one tag assigned to it.');

        $form->addTextArea('text', '')
            ->setRequired('Cannot save an empty card.');

        $form->addText('private', '');
        $form->addText('toDelete', '');

        $form->addSubmit('send', 'Save');

        $form->onSuccess[] = [$this, 'editCardFormSucceeded'];
        return $form;
    }

    public function editCardFormSucceeded($form) {
        $values = $form->getValues();
        $values['user_id'] = $this->getUser()->id;

        if($values->toDelete == 1)
            $this->cards->deleteCard($values->id);
        else
            $this->cards->editCard($values);

        $this->redirect('Cards:list');
    }

}
