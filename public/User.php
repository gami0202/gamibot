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

    // 変数を、lineメッセージに表示される文字列に変換
    public function display() {
      return $this->name . "\n";
    }
}
