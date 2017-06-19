<?php

namespace App\Model;

use Nette;



class Folders {
    private $database;

	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

    public function getFolders($user_id) {
        $folders = $this->database->table('folders')->where('user_id', $user_id);
        if(!$folders) $folders = null;
        return $folders;
    }

    public function getTags($folders_array) {
        foreach($folders_array as $folder) {
            $result = $this->database->table('tags_folders')->where('folder_id', $folder->id);

            if($result->count() > 0) {
                foreach($result as $tag_id) {
                    $tags[$folder->id][$tag_id->tag_id] = $this->database->table('tags')->get($tag_id->tag_id);
                }
            } else $tags[$folder->id] = null;
        }

        return $tags;
    }

    public function setNewFolder($data) {
        /*
         * TO-DO
         * Process errors
         */
        $newFolderId = $this->addFolder($data);

        if($data->tags != '') {
            $this->addNewTags($data->tags);
            $this->addRelations($newFolderId, $data->tags);
        }
    }

    public function setEditFolder($data) {
        /*
         * TO-DO
         * Process errors
         */
        $this->database->table('folders')->where('id', $data->id)->update(array('folder_name' => $data->folder_name));

        $this->deleteOrphanTags($data->id);
        $this->deleteRelations($data->id);

        if($data->tags != '') {
            $this->addNewTags($data->tags);
            $this->addRelations($data->id, $data->tags);
        }
    }

    public function setDeleteFolder($data) {
        /*
         * TO-DO
         * Process errors
         */
        $this->deleteOrphanTags($data->id);
        $this->deleteFolder($data->id);
    }

    public function addNewTags($tagsStr) {
        $tagsArray = explode(',', $tagsStr);

        // For each tag, check if it's already in table('tags')
        foreach($tagsArray as $tagName) {
            $tagsFound = $this->database->table('tags')->where('tag_name', $tagName);

            // If not -> add it and push new tag id to an array
            if(count($tagsFound) == 0) {
                $newTags[] = $this->database->table('tags')->insert(array('tag_name' => $tagName));
                $this->writeToJSON($tagName, 'append');
            }
        } 
    }

    public function deleteOrphanTags($folderId) {
        $tagsFound = $this->database->table('tags_folders')->where('folder_id', $folderId);
        foreach($tagsFound as $tag) {
            $tagExists = $this->database->table('tags_folders')->where('tag_id', $tag->tag_id)->where("folder_id != ?", $folderId);
            if(count($tagExists) == 0) {
                $tagRow = $this->database->table('tags')->where('id', $tag->tag_id);
                $tagName = $tagRow->fetchField('tag_name');
                $tagRow->delete();
                $this->writeToJSON($tagName, 'delete');
            }
        }
    }

    public function addRelations($folderId, $tagsStr) {
        $tagsArray = explode(',', $tagsStr);
        $tagsIds = array();

        foreach($tagsArray as $tag)
            $tagsIds[] = $this->database->table('tags')->where('tag_name', $tag)->fetchField('id');

        foreach($tagsIds as $tagId)
            $this->database->table('tags_folders')->insert(array('folder_id' => $folderId,
                                                                 'tag_id' => $tagId));
    }

    public function deleteRelations($folderId) {
        $this->database->table('tags_folders')->where('folder_id', $folderId)->delete();
    }

    public function addFolder($folderData) {
        return $this->database->table('folders')->insert(array('folder_name' => $folderData->folder_name,
                                                               'user_id' => $folderData->user_id));
    }

    public function deleteFolder($folderId) {
        $this->database->table('folders')->where('id', $folderId)->delete();
    }

    public function writeToJSON($data, $flag) {
        $data = '"' . $data . '"';
        if($flag == 'append') {
                $contents = file_get_contents('nette.safe://' . WWW_DIR . '/data/tags.json');
                $contents = substr($contents, 1, -1);
                $contents = str_replace(']', '', $contents);
                $contents = str_replace('[', '', $contents);
                if($contents != '')
                    $contents = explode(',', $contents);
                else
                    $contents = array();
                if(!in_array($data, $contents))
                    $contents[] = $data;
                file_put_contents('nette.safe://' . WWW_DIR . '/data/tags.json', '[' . implode(',', $contents) . ']');
        }
        if($flag == 'delete') {
                $contents = file_get_contents('nette.safe://' . WWW_DIR . '/data/tags.json');
                $contents = substr($contents, 1, -1);
                $contents = str_replace(']', '', $contents);
                $contents = str_replace('[', '', $contents);
                if($contents != '')
                    $contents = explode(',', $contents);
                else
                    $contents = array();
                $id = array_search($data, $contents);
                if($id)
                    unset($contents[$id]);
                file_put_contents('nette.safe://' . WWW_DIR . '/data/tags.json', '[' . implode(',', $contents) . ']');
        }
    }
}
