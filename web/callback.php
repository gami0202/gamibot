<?php

$testMode = false;

if ($testMode) {
	$text = "bot list";
	$userId = "a";

} else {
	$accessToken = getenv('LINE_CHANNEL_ACCESS_TOKEN');

	//ユーザーからのメッセージ取得
	$json_string = file_get_contents('php://input');
	$jsonObj = json_decode($json_string);

	$type = $jsonObj->{"events"}[0]->{"message"}->{"type"};
	//メッセージ取得
	$text = $jsonObj->{"events"}[0]->{"message"}->{"text"};
	//ReplyToken取得
	$replyToken = $jsonObj->{"events"}[0]->{"replyToken"};

	$userId = $jsonObj->{"events"}[0]->{"source"}->{"userId"};
	$lineUserName = $jsonObj->{"events"}[0]->{"source"}->{"userName"};
}

////////////////// include /////////////////////
include 'Charge.php';
include 'ChargeList.php';
include 'User.php';
include 'UserList.php';

////////////////// Util /////////////////////
function illegalArgumentResponse() {
	return [
		"type" => "text",
		"text" => "入力が正しくありません\n'bot'でヘルプが確認できます"
	];
}

function alreadyJoinedResponse($userId) {
	$users = new UserList();
	$userName = $users->getNameById($userId);
	return [
		"type" => "text",
		"text" => "あなたはすでに '" . $userName . "' として参加しています"
	];
}

function notExistUserNameResponse($userName) {
	$users = new UserList();
	return [
		"type" => "text",
		"text" => "指定されたユーザー " . $userName . " が存在しません\n現在の参加者は\n" . $users->display()
	];
}

function notJoinedUserResponse() {
	return [
		"type" => "text",
		"text" => "あなたはユーザーとして参加していません"
	];
}

function isAlreadyJoinUser($userId) {
	$users = new UserList();
	$existUserIds = $users->getIdArray();
	return in_array($userId, $existUserIds);
}

function isExistUserName($userName) {
	$users = new UserList();
	$existUserNames = $users->getNameArray();
	return in_array($userName, $existUserNames);
}

// userNameが見つからなかったときは、userIdをそのまま返します。
function getUserNameById($userId) {
	$users = new UserList();
	return $users->getNameById($userId);
}

function startWith($str, $prefix) {
	return substr($str,  0, strlen($prefix)) === $prefix;
}

function chargeMapToString($map) {
	$string = "";
	foreach ($map as $key => $value) {
		$string = $string . "〇" . $key . ": " . number_format($value) . "\n";
	}
	return $string;
}

////////////////// Main /////////////////////
if (!$testMode) {
	//メッセージ以外のときは何も返さず終了
	if($type != "text"){
		exit;
	}
}

$botStatus = file_get_contents('botStatus.txt');

if ($text == 'あんこう') {
	$response_format_text = [
  "type" => "template",
  "altText" => "this is a carousel template",
  "template" => [
      "type" => "carousel",
      "columns" => [
          [
            "title" => "支払い操作",
            "text" => "支払い操作です！",
            "actions" => [
			          [
			            "type" => "message",
			            "label" => "登録",
			            "text" => "charge add"
			          ],
			          [
			            "type" => "message",
			            "label" => "一覧",
			            "text" => "bot list"
			          ],
			          [
			            "type" => "message",
			            "label" => "清算",
			            "text" => "bot calc"
			          ]
            ]
          ],
          [
            "title" => "ユーザー操作",
            "text" => "ユーザー操作です！",
            "actions" => [
			          [
			            "type" => "message",
			            "label" => "参加",
			            "text" => "user join"
			          ],
			          [
			            "type" => "message",
			            "label" => "一覧",
			            "text" => "bot user list"
			          ],
			          [
			            "type" => "message",
			            "label" => "! 支払い削除 !",
			            "text" => "charge delete"
			          ]
            ]
          ],
	  	]
		]
	];

} else if ($text == "キャンセル") {
	$response_format_text = [
		"type" => "text",
		"text" => "現在の処理をキャンセルしました"
	];

	file_put_contents('botStatus.txt', "");

//// 支払い追加処理 ////
} else if ($text == "charge add") {
	if (!isAlreadyJoinUser($userId)) {
		$response_format_text = notJoinedUserResponse();
	} else {
		$users = new UserList();
		$response_format_text = [
			"type" => "text",
			"text" => "以下から対象を入力してください\n全員\n" . $users->display()
		];

		file_put_contents('botStatus.txt', "waiting target");
	}

} else if ($botStatus == "waiting target") {
	$target = $text;
	if ($target != '全員' && !isExistUserName($target)) {
		$response_format_text = notExistUserNameResponse($target);
	} else {
		$response_format_text = [
			"type" => "text",
			"text" => "金額を入力してください"
		];

		file_put_contents('tmpOwnerId.txt', $userId);

		if ($target == '全員') { // 内部処理では all
			file_put_contents('tmpTarget.txt', 'all');
		} else {
			file_put_contents('tmpTarget.txt', $text);
		}
		file_put_contents('botStatus.txt', "waiting charge");
	}

} else if ($botStatus == "waiting charge") {

	if ($userId != file_get_contents('tmpOwnerId.txt')) {
		// Nothing to do
	} else if (!is_numeric($text)) {
		$response_format_text = [
			"type" => "text",
			"text" => "半角数字を入力してください"
		];
	} else {
		$response_format_text = [
			"type" => "text",
			"text" => "コメントを入力してください(例: 〇〇代)"
		];
		file_put_contents('botStatus.txt', "waiting comment");
		file_put_contents('tmpValue.txt', $text);
	}

} else if ($botStatus == "waiting comment") {
	if ($userId != file_get_contents('tmpOwnerId.txt')) {
		// Nothing to do
	} else if (!isAlreadyJoinUser($userId)) {
		$response_format_text = notJoinedUserResponse();
	} else {
		$comment = $text;
		$target = file_get_contents('tmpTarget.txt');
		$value = file_get_contents('tmpValue.txt');
		$charges = new ChargeList();
		$newCharge = new Charge($charges->getNextId(), getUserNameById($userId), $value, $target, $comment);
		$newCharge->addDb();

		if ($newCharge->target == "all") {
			$response_format_text = [
				"type" => "text",
				"text" => $newCharge->owner . "さんが全員分として "
									. $newCharge->charge . "円を立て替えました。"
			];
		} else {
			$response_format_text = [
				"type" => "text",
				"text" => $newCharge->owner . "さんが" . $newCharge->target . "さんに "
									. $newCharge->charge . "円を立て替えました。"
			];
		}

		file_put_contents('botStatus.txt', "");
	}


//// 支払い削除 ////
} else if ($text == "charge delete") {
	$response_format_text = [
		"type" => "text",
		"text" => "削除するIDを入力してください"
	];
	file_put_contents('botStatus.txt', "waiting delete id");

} else if ($botStatus == "waiting delete id") {
	if (!is_numeric($text)) {
		$response_format_text = [
			"type" => "text",
			"text" => "半角数字を入力してください"
		];
	} else {
		$chargeList = new ChargeList();
		$response_format_text = $chargeList->delete($text);
		file_put_contents('botStatus.txt', "");
	}

//// ユーザー処理 ////
} else if ($text == "user join") {
	$req = explode(" ", $text);
	if (isAlreadyJoinUser($userId)) {
		$response_format_text = alreadyJoinedResponse($userId);
	} else if (count($req) == 2) {
		$response_format_text = [
			"type" => "text",
			"text" => "ユーザー名を入力してください"
		];

		file_put_contents('botStatus.txt', "waiting user name");
	}
} else if ($botStatus == "waiting user name") {
	$newUser = new User($userId, $text);
	$newUser->addDb();
	$users = new UserList();
	$response_format_text = [
		"type" => "text",
		"text" => $newUser->name . "が参加しました\n現在の参加者は\n" . $users->display()
	];

	file_put_contents('botStatus.txt', "");

//CLI
} else {
	// help表示
	if ($text == 'bot') {
		$response_format_text = [
			"type" => "text",
	    "text" => "[Help]\n"
								. "〇ユーザーとして参加:\n  bot join <人名>\n"
								. "〇参加者一覧:\n  bot user list\n"
								. "〇支払追加:\n  bot add <金額> <立替先(人名 or 'all')> <コメント>\n"
								. "〇支払一覧:\n  bot list\n"
								. "〇支払清算:\n  bot calc"
	  ];

	// 料金追加処理
	} else if (startWith($text, 'bot add')) {
		$req = explode(" ", $text);
		if (!isAlreadyJoinUser($userId)) {
			$response_format_text = notJoinedUserResponse();
		} else if (count($req) != 5) {
			$response_format_text = illegalArgumentResponse();
		} else if (!is_numeric($req[2])) {
			$response_format_text = illegalArgumentResponse();
		} else if ($req[3] != 'all' && !isExistUserName($req[3])) {
			$response_format_text = notExistUserNameResponse($req[3]);
		} else {
			$charges = new ChargeList();
			$newCharge = new Charge($charges->getNextId(), getUserNameById($userId), $req[2], $req[3], $req[4]);
			$newCharge->addDb();
			$response_format_text = [
				"type" => "text",
		    "text" => $newCharge->owner . "さんが" . $newCharge->target . "さんに、"
									. $newCharge->charge . "円を立て替えました。"
		  ];
		}

	// 料金一覧返却
	} else if ($text == 'bot list') {
		$charges = new ChargeList();
		$response_format_text = [
			"type" => "text",
			"text" => $charges->display()
		];

	// 料金清算処理
	} else if ($text == 'bot calc') {
		$charges = new ChargeList();
		$users = new UserList();

		// 全員分の建て替えのみをマップに格納
		$chargeMapOnlyToAll = array();
		foreach ($charges->chargeList as $charge) {
			if ($charge->target == 'all') {
				$chargeMapOnlyToAll[$charge->owner] += $charge->charge;
			}
		}

		// 全員分の建て替えの一人当たりの支払い額を算出
		$totalCharge = 0;
		foreach ($chargeMapOnlyToAll as $_owner => $charge) {
			$totalCharge += $charge;
		}
		$chargeAverage = $totalCharge / $users->userNum();

		// ユーザー分のマップを作成し、全員分の建て替えに対する支払額を格納
		$calcCharge	= array();
		foreach ($users->userList as $user) {
			$charge = $chargeAverage - $chargeMapOnlyToAll[$user->name];
			$calcCharge += array($user->name => $charge);
		}

		// 各ユーザーの支払い金額を作成
		foreach ($charges->chargeList as $charge) {
			if ($charge->target != 'all') {
				$calcCharge[$charge->owner] -= $charge->charge;
				$calcCharge[$charge->target] += $charge->charge;
			}
		}

		$response_format_text = [
			"type" => "text",
			"text" => "プラスの人が支払い、マイナスの人が受け取りをして下さい\n"
								. chargeMapToString($calcCharge)
		];

	// 支払い削除処理
	} else if (startWith($text, 'bot delete')) {
		$req = explode(" ", $text);
		if (count($req) != 3) {
			$response_format_text = illegalArgumentResponse();
		} else {
			$chargeList = new ChargeList();
			$response_format_text = $chargeList->delete($req[2]);
		}

	// ユーザー追加処理
	} else if (startWith($text, 'bot join')) {
		$req = explode(" ", $text);
		if (count($req) != 3) {
			$response_format_text = illegalArgumentResponse();
		} else if (isAlreadyJoinUser($userId)) {
			$response_format_text = alreadyJoinedResponse($userId);
		} else {
			$newUser = new User($userId, $req[2]);
			$newUser->addDb();
			$users = new UserList();
			$response_format_text = [
				"type" => "text",
		    "text" => $newUser->name . "が参加しました\n現在の参加者は\n" . $users->display()
		  ];
		}

	// 参加済みユーザーを一覧表示
	} else if ($text == 'bot user list') {
		$users = new UserList();
		$response_format_text = [
			"type" => "text",
			"text" => "現在の参加者は\n" . $users->display()
		];

	// データ全削除
	} else if ($text == 'bot clear') {
		$date = date("Y-m-d-H-i-s");
		rename("users.txt", $date . "_users.txt");
		rename("charges.txt", $date . "_charges.txt");
		$response_format_text = [
			"type" => "text",
			"text" => "記録された情報をすべて削除しました"
		];

	//Joke
	} else if (strpos($text, "ガルパン") !== false) {
		$response_format_text = [
			"type" => "text",
			"text" => "ガルパンはいいぞ"
		];
	}
}

if ($testMode) {
	echo $response_format_text["text"];
} else {
	$post_data = [
		"replyToken" => $replyToken,
		"messages" => [$response_format_text]
		];

	$ch = curl_init("https://api.line.me/v2/bot/message/reply");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json; charser=UTF-8',
	    'Authorization: Bearer ' . $accessToken
	    ));
	$result = curl_exec($ch);
	curl_close($ch);
}
