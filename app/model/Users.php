<?php

namespace App\Model;

use Nette;
use Nette\Security as NS;



class Users implements NS\IAuthenticator {

	private $database;

	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

    public function register($data) {
        $userSalt = $data['username'];
        $data['password'] = NS\Passwords::hash($data['password'] . $userSalt);
        $user_id = $this->database->table('users')->insert($data);
        $defaultFolderId = $this->database->table('folders')->insert(array('user_id' => $user_id,
                                                                           'folder_name' => 'default folder'));
        $this->database->table('tags_folders')->insert(array('tag_id' => 1,
                                                             'folder_id' => $defaultFolderId));
        $collId = $this->database->table('collections')->insert(array('name' => 'Revise own cards',
                                                                      'user_id' => $user_id));
        $this->database->table('collections_users')->insert(array('collection_id' => $collId,
                                                                  'user_id' => $user_id));
        $collId = $this->database->table('collections')->insert(array('name' => 'Revise Andrej\'s cards',
                                                                      'user_id' => $user_id));
        $this->database->table('collections_users')->insert(array('collection_id' => $collId,
                                                                  'user_id' => 1));
        $this->writeToJSON($data['username'], 'append');
        return $user_id;
    }

    public function authenticate(array $credentials) {
        list($username, $password) = $credentials;
        $user = $this->database->table('users')
            ->where('username', $username)->fetch();

        if(!$user) {
            throw new NS\AuthenticationException('User not found.');
        }

        if(!NS\Passwords::verify($password . $username, $user->password)) {
            throw new NS\AuthenticationException('Invalid password.');
        }

        return new NS\Identity($user->id, 'user', ['username' => $user->username]);
    }

    public function writeToJSON($data, $flag) {
        $data = '"' . $data . '"';
        if($flag == 'append') {
                $contents = file_get_contents('nette.safe://' . WWW_DIR . '/data/users.json');
                $contents = substr($contents, 1, -1);
                $contents = str_replace(']', '', $contents);
                $contents = str_replace('[', '', $contents);
                if($contents != '')
                    $contents = explode(',', $contents);
                else
                    $contents = array();
                if(!in_array($data, $contents))
                    $contents[] = $data;
                file_put_contents('nette.safe://' . WWW_DIR . '/data/users.json', '[' . implode(',', $contents) . ']');
        }
        if($flag == 'delete') {
                $contents = file_get_contents('nette.safe://' . WWW_DIR . '/data/users.json');
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
                file_put_contents('nette.safe://' . WWW_DIR . '/data/users.json', '[' . implode(',', $contents) . ']');
        }
    }
}
