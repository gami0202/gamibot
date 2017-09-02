<?php
class Charge
{
    // プロパティの宣言
		public $id = '';
		public $owner = '';
    public $charge = '';
		public $target = '';

		function __construct($id, $owner, $charge, $target) {
			$this->id = $id;
			$this->owner = $owner;
			$this->charge = $charge;
			$this->target = $target;
		}

		// DB保存データに変換
		// カンマの前後にスペースを入れると、パース時にそのまま変数に入ってしまうので注意
		public function toCsv() {
        return $this->id . "," . $this->owner . "," . $this->charge . "," . $this->target . ",";
    }

    // 変数を、lineメッセージに表示される文字列に変換
    public function display() {
      return $this->id . ", " . $this->owner . ", " . $this->charge . ", " . $this->target . "\n";
    }

    // データベース(テキストファイル)に、料金情報を追記
		function addDb() {
			$file = 'charges.txt';
			file_put_contents($file, $this->toCsv(), FILE_APPEND);
		}
}
