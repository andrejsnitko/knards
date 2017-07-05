<?php

namespace App\Model;

use Nette;
use Nette\Utils\DateTime;



class Collections {
    private $database;

	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

    public function getCollectionsNames($user_id) {
        return $this->database->table('collections')->where('user_id', $user_id);
    }

    public function getCollections($user_id) {
        $collections = $this->database->table('collections')->where('user_id', $user_id);
        if(count($collections) != 0)
            foreach($collections as $index => $collection) {
                $result[$index]['id'] = $index;
                $result[$index]['name'] = $collection->name;
                $result[$index]['date_from'] = $collection->date_from;
                $result[$index]['date_to'] = $collection->date_to;
                $result[$index]['tags'] = null;
                $result[$index]['users'] = null;
                $tagIds = $this->database->table('collections_tags')->where('collection_id', $index);
                foreach($tagIds as $tagId)
                    $result[$index]['tags'][] = $this->database->table('tags')->where('id', $tagId->tag_id)->fetch();
                $userIds = $this->database->table('collections_users')->where('collection_id', $index);
                foreach($userIds as $userId)
                    $result[$index]['users'][] = $this->database->table('users')->where('id', $userId->user_id)->fetch();
            }
        else $result = null;
        return $result;
    }

    public function prepareCollection($collectionId) {
        /*
         * TO-DO
         * Handle errors on collection not found
         */
        $collectionData = $this->database->table('collections')->get($collectionId);

        // Fetch ids of users and/or tags specified to be followed in the collection
        // If not specified, fetch all ids
        $collectionUsers = $this->database->table('collections_users')->where('collection_id', $collectionId)->fetchAll();
        if(count($collectionUsers) == 0)
            $collectionUsers = $this->database->table('users')->fetchAll();

        $collectionTags = $this->database->table('collections_tags')->where('collection_id', $collectionId)->fetchAll();
        if(count($collectionTags) == 0)
            $collectionTags = $this->database->table('tags')->fetchAll();

        // Fetch all cards from each specified tag/user
        // Consider dates if specified
        // Do not include private cards
        // Result: an array containing cards from each specified tag/user from and/or to the specified date
        $cardSet = array();
        $final = array();
        foreach($collectionTags as $tag)
            foreach($collectionUsers as $cuser) {
                if($collectionData->date_from != null && $collectionData->date_to != null)
                    $cardSet = $this->database->query("SELECT cards.id, cards.user_id, cards.content, cards.created_at, cards_tags.tag_id FROM cards INNER JOIN cards_tags ON cards.id = cards_tags.card_id INNER JOIN users ON cards.user_id = users.id WHERE cards_tags.tag_id = ? AND users.id = ? AND cards.created_at > ? AND cards.created_at < ? AND cards.is_private = 0", $tag->id, $cuser->id, $collectionData->date_from, $collectionData->date_to)->fetchAll();
                else if($collectionData->date_from == null && $collectionData->date_to != null)
                    $cardSet = $this->database->query("SELECT cards.id, cards.user_id, cards.content, cards.created_at, cards_tags.tag_id FROM cards INNER JOIN cards_tags ON cards.id = cards_tags.card_id INNER JOIN users ON cards.user_id = users.id WHERE cards_tags.tag_id = ? AND users.id = ? AND cards.created_at < ? AND cards.is_private = 0", $tag->id, $cuser->id, $collectionData->date_to)->fetchAll();
                else if($collectionData->date_from != null && $collectionData->date_to == null)
                    $cardSet = $this->database->query("SELECT cards.id, cards.user_id, cards.content, cards.created_at, cards_tags.tag_id FROM cards INNER JOIN cards_tags ON cards.id = cards_tags.card_id INNER JOIN users ON cards.user_id = users.id WHERE cards_tags.tag_id = ? AND users.id = ? AND cards.created_at > ? AND cards.is_private = 0", $tag->id, $cuser->id, $collectionData->date_from)->fetchAll();
                else if($collectionData->date_from == null && $collectionData->date_to == null)
                    $cardSet = $this->database->query("SELECT cards.id, cards.user_id, cards.content, cards.created_at, cards_tags.tag_id FROM cards INNER JOIN cards_tags ON cards.id = cards_tags.card_id INNER JOIN users ON cards.user_id = users.id WHERE cards_tags.tag_id = ? AND users.id = ? AND cards.is_private = 0", $tag->id, $cuser->id)->fetchAll();
                $final = array_merge($final, $cardSet);
            }

        if(count($final) != 0) {
            $cardSet = $this->tagegize($final);
            $cardSet = $this->syncRevised($cardSet, $collectionData->user_id);
            usort($cardSet, 'self::sortWorstKnown');
            $cardSet = $this->finalize($cardSet);
        }
        $this->saveToJSON($cardSet, $collectionData->user_id);
    }

    // tagegize:
    // Input array:
    //      [index]    [card id]
    //                 [creator id]
    //                 [card text]
    //                 [creation date]
    //                 [tag id for the card] <- single index, creates array element for each tag for each card
    // Output array:
    //      [card id]  [card id]
    //                 [creator id]
    //                 [card text]
    //                 [creation date]
    //                 [array with tag names (strings)]
    public function tagegize($cardSet) {
        $result = array();
        foreach($cardSet as $card) {
            if(!array_key_exists($card['id'], $result)) {
                $result[$card['id']]['id'] = $card['id'];
                $result[$card['id']]['user_id'] = $card['user_id'];
                $result[$card['id']]['content'] = $card['content'];
                $result[$card['id']]['created_at'] = $card['created_at'];
                $result[$card['id']]['tags'][] = $this->database->table('tags')->where('id', $card['tag_id'])->fetchField('tag_name');
                $otherTags = $this->database->table('cards_tags')->where('tag_id != ?', $card['tag_id'])->where('card_id', $card['id'])->fetchAll();
                if(count($otherTags) != 0)
                    foreach($otherTags as $tag)
                        $result[$card['id']]['tags'][] = $this->database->table('tags')->where('id', $tag->tag_id)->fetchField('tag_name');
            }
        }
        return $result;
    }

    // syncRevised:
    // Input array:
    //      [card id]  [card id]
    //                 [creator id]
    //                 [card text]
    //                 [creation date]
    //                 [array with tag names (strings)]
    // Output array:
    //      same +
    //                 [score]
    //                 unset cards that were revised today
    public function syncRevised($cardSet, $userId) {
        $revised = $this->database->table('revised_cards')->where('user_id', $userId)->fetchAll();
        $revisedData = array();
        foreach($revised as $r['data']) {
            $revisedData[$r['data']['card_id']]['score'] = $r['data']['score'];
            $revisedData[$r['data']['card_id']]['last_revised'] = $r['data']['last_revised'];
        }

        foreach($cardSet as $index => $card)
            if(array_key_exists($index, $revisedData))
                if(date('Y-m-d', strtotime($revisedData[$index]['last_revised'])) == date('Y-m-d', time()))
                    unset($cardSet[$index]);
                else {
                    $cardSet[$index]['last_revised'] = $revisedData[$index]['last_revised'];
                    $cardSet[$index]['score'] = $revisedData[$index]['score'];
                }
            else {
                $cardSet[$index]['last_revised'] = null;
                $cardSet[$index]['score'] = 0;
            }

        return $cardSet;
    }

    // finalize:
    // replace in $cardSet:
    //                      user ids with usernames
    //                      dates to format like "1 June, 2017"
    public function finalize($cardSet) {
        $allUsers = $this->database->table('users')->fetchPairs('id', 'username');
        foreach($cardSet as $index => $card) {
            $cardSet[$index]['user_id'] = $allUsers[$card['user_id']];
            $cardSet[$index]['created_at'] = date('j F, Y', strtotime($card['created_at']));
            if($cardSet[$index]['last_revised'] != null)
                $cardSet[$index]['last_revised'] = date('j F, Y', strtotime($card['last_revised']));
        }

        return $cardSet;
    }

    public function updateCollection($data, $userId) {
        if(count($data) != 0)
            foreach($data as $card) {
                if($card['last_revised'] == 'new') {
                    $insertArray['user_id'] = $userId;
                    $insertArray['card_id'] = $card['id'];
                    $insertArray['score'] = $card['score'];
                    $this->database->table('revised_cards')->insert($insertArray);
                }
                if($card['last_revised'] == 'revised') {
                    $updateArray['score'] = $card['score'];
                    $updateArray['last_revised'] = date('Y-m-d', time());
                    $this->database->query("UPDATE revised_cards SET score=?, last_revised=? WHERE user_id=? AND card_id=?", $card['score'], date('Y-m-d', time()), $userId, $card['id']);
                }
            }
        unlink('nette.safe://' . WWW_DIR . '/data/user' . $userId . '.json');
    }

    public function getCollectionName($collectionId) {
        return $this->database->table('collections')->get($collectionId);
    }

    public function setNewRuleset($data) {
        $insertArray['name'] = $data->ruleset_name;
        $insertArray['user_id'] = $data->user_id;
        if($data->date_from != null)
            $insertArray['date_from'] = $data->date_from;
        if($data->date_to != null)
            $insertArray['date_to'] = $data->date_to;

        $newCollectionId = $this->database->table('collections')->insert($insertArray);

        if($data->tags != null)
            foreach($data->tags as $tagName) {
                $tag = $this->database->table('tags')->where('tag_name', $tagName)->fetch();
                if($tag)
                    $this->database->table('collections_tags')->insert(array('collection_id' => $newCollectionId,
                                                                             'tag_id' => $tag->id));
            }

        if($data->users != null)
            foreach($data->users as $userName) {
                $cuser = $this->database->table('users')->where('username', $userName)->fetch();
                if($cuser)
                    $this->database->table('collections_users')->insert(array('collection_id' => $newCollectionId,
                                                                              'user_id' => $cuser->id));
            }
    }

    public function setEditRuleset($data) {
        $updateArray['name'] = $data->ruleset_name;
        $updateArray['user_id'] = $data->user_id;
        if($data->date_from != null)
            $updateArray['date_from'] = $data->date_from;
        if($data->date_to != null)
            $updateArray['date_to'] = $data->date_to;

        $this->database->table('collections')->where('id', $data->id)->update($updateArray);

        $this->database->table('collections_tags')->where('collection_id', $data->id)->delete();
        $this->database->table('collections_users')->where('collection_id', $data->id)->delete();

        if($data->tags != null)
            foreach($data->tags as $tagName) {
                $tag = $this->database->table('tags')->where('tag_name', $tagName)->fetch();
                if($tag)
                    $this->database->table('collections_tags')->insert(array('collection_id' => $data->id,
                                                                             'tag_id' => $tag->id));
            }

        if($data->users != null)
            foreach($data->users as $userName) {
                $cuser = $this->database->table('users')->where('username', $userName)->fetch();
                if($cuser)
                    $this->database->table('collections_users')->insert(array('collection_id' => $data->id,
                                                                              'user_id' => $cuser->id));
            }
    }

    public function setDeleteRuleset($data) {
        $this->database->table('collections_tags')->where('collection_id', $data['collection_id'])->delete();
        $this->database->table('collections_users')->where('collection_id', $data['collection_id'])->delete();
        $this->database->table('collections')->where('id', $data['collection_id'])->delete();
    }

    // sortWorstKnown:
    // sorts $cardSet:
    //                  least scored first
    //                  same scored sort by date -> oldest first
    public function sortWorstKnown($cur, $next) {
        $curDate = strtotime($cur['last_revised']);
        $nextDate = strtotime($next['last_revised']);
        $curScore = $cur['score'];
        $nextScore = $next['score'];

        if($curScore < $nextScore)
            return -1;
        else if($curScore == $nextScore)
            return $curDate - $nextDate;
        else 
            return 1;
    }

    public function saveToJSON($cardSet, $userId) {
        $result = '[';
        foreach($cardSet as $card) {
            $result .= '{"id":"' . $card['id'] . '",';
            $result .= '"created_by":"' . $card['user_id'] . '",';
            $result .= '"content":"' . $card['content'] . '",';
            $result .= '"created_at":"' . $card['created_at'] . '",';
            $result .= '"tags":[';
            foreach($card['tags'] as $tag)
                $result .= '"' . $tag . '",';
            $result = substr($result, 0, -1);
            $result .= '],';
            $result .= '"last_revised":"' . $card['last_revised'] . '",';
            $result .= '"score":"' . $card['score'] . '"},';
        }
        $result = substr($result, 0, -1);
        $result .= ']';

        file_put_contents('nette.safe://' . WWW_DIR . '/data/user' . $userId . '.json', $result);
    }
}
