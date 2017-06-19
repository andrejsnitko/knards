<?php

namespace App\Model;

use Nette;



class Cards {
    private $database;

	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

    public function getCards($user_id) {
        $cards = $this->database->table('cards')->where('user_id', $user_id)->order('created_at DESC');
        if(!$cards) $cards = null;
        return $cards;
    }

    public function saveCard($data) {
        $card_id = $this->database->table('cards')->insert(array('user_id' => $data['user_id'],
                                                                 'content' => $data['text'],
                                                                 'is_private' => $data['private'] == 'true' ? 1 : 0));
        $tags = explode(',', $data['tags']);
        foreach($tags as $tag)
            $this->database->table('cards_tags')->insert(array('card_id' => $card_id,
                                                               'tag_id' => $tag));
    }

    public function editCard($data) {
        $this->database->table('cards_tags')->where('card_id', $data['id'])->delete();
        $this->database->query('UPDATE cards SET content=?, is_private=? WHERE id=?', $data['text'], $data['private'], $data['id']);

        $tags = explode(',', $data['tags']);
        foreach($tags as $tag)
            $this->database->table('cards_tags')->insert(array('card_id' => $data['id'],
                                                               'tag_id' => $tag));
    }

    public function deleteCard($cardId) {
        $this->database->table('revised_cards')->where('card_id', $cardId)->delete();
        $this->database->table('cards')->where('id', $cardId)->delete();
    }

    public function getCard($index) {
        return $this->database->table('cards')->get($index);
    }

    public function getCardTags($index) {
        $result = '';
        $rows = $this->database->table('cards_tags')->where('card_id', $index);
        if($rows)
            foreach($rows as $row)
                $result .= $this->database->table('tags')->get($row->tag_id)->tag_name . ',';
        $result = substr($result, 0, -1);
        return $result;
    }
}
