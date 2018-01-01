<?php
class Charge
{
		public $id = '';
		public $owner = '';
    public $charge = '';
		public $target = '';
		public $comment = '';

		function __construct($id, $owner, $charge, $target, $comment) {
			$this->id = $id;
			$this->owner = $owner;
			$this->charge = $charge;
			$this->target = $target;
			$this->comment = $comment;
		}

    // 変数を、lineメッセージに表示される文字列に変換
    public function display() {
      return $this->id . ", " . $this->owner . ", " . $this->charge . ", "
			. $this->target . ", " . $this->comment . "\n";
    }
}
