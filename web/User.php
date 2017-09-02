<?php
class User
{
    // プロパティの宣言
		public $id = '';
		public $name = '';

		function __construct($id, $name) {
			$this->id = $id;
			$this->name = $name;
		}

		// DB保存データに変換
		// カンマの前後にスペースを入れると、パース時にそのまま変数に入ってしまうので注意
		public function toCsv() {
        return $this->id . "," . $this->name . ",";
    }

    // 変数を、lineメッセージに表示される文字列に変換
    public function display() {
      return $this->name . "\n";
    }

    function addDb() {
    	$file = 'users.txt';
    	file_put_contents($file, $this->toCsv(), FILE_APPEND);
    }

}
