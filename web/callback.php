<?php

$testMode = false;

if ($testMode) {
	$text = "bot";  //TODO for test. plz delete
	$userId = "aaa";  //TODO for test. plz delete

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
		"text" => "入力が正しくありません。\n'bot'でヘルプが確認できます。"
	];
}

function alreadyJoinedResponse() {
	$users = new UserList();
	return [
		"type" => "text",
		"text" => "すでに参加しているユーザーです。\n現在の参加者は、\n" . $users->display()
	];
}

function notExistUserNameResponse($userName) {
	$users = new UserList();
	return [
		"type" => "text",
		"text" => "指定されたユーザー " . $userName . " が存在しません。\n現在の参加者は、\n" . $users->display()
	];
}

function notJoinedUserResponse() {
	$users = new UserList();
	return [
		"type" => "text",
		"text" => "あなたはユーザーとして参加していません。\n以下を実行して参加してください。\n"
							. "bot join <名前>"
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
	$userName = $users->getName($userId);
	if ($userName == null) {
		return $userId;
	}
	return $userName;
}

function startWith($str, $prefix) {
	return substr($str,  0, strlen($prefix)) === $prefix;
}

function mapToString($map) {
	$string = "";
	foreach ($map as $key => $value) {
		$string = $string . $key . ": " . $value . "\n";
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

// help表示
if ($text == 'bot') {
	$response_format_text = [
		"type" => "text",
    "text" => "[Help]\n"
							. "ユーザーとして参加: bot join <人名>\n"
							. "参加ユーザー一覧: bot user list\n"
							. "支払追加: bot add <支払い金額(数字のみ)> <支払者(人名 or 'all')>\n"
							. "支払一覧: bot list\n"
							. "支払清算: bot calc"
  ];

// 料金追加処理
} else if (startWith($text, 'bot add')) {
	$req = explode(" ", $text);
	if (!isAlreadyJoinUser($userId)) {
		$response_format_text = notJoinedUserResponse();
	} else if (count($req) != 4) {
		$response_format_text = illegalArgumentResponse();
	} else if (!is_numeric($req[2])) {
		$response_format_text = illegalArgumentResponse();
	} else if ($req[3] != 'all' && !isExistUserName($req[3])) {
		$response_format_text = notExistUserNameResponse($req[3]);
	} else {
		$charges = new ChargeList();
		$newCharge = new Charge($charges->getNextId(), getUserNameById($userId), $req[2], $req[3]);
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
		"text" => mapToString($calcCharge)
	];

// 支払い削除処理
} else if (startWith($text, 'bot delete')) {
	//TODO

// ユーザー追加処理
} else if (startWith($text, 'bot join')) {
	$req = explode(" ", $text);
	if (count($req) != 3) {
		$response_format_text = illegalArgumentResponse();
	} else if (isAlreadyJoinUser($userId)) {
		$response_format_text = alreadyJoinedResponse();
	} else {
		$newUser = new User($userId, $req[2]);
		$newUser->addDb();
		$users = new UserList();
		$response_format_text = [
			"type" => "text",
	    "text" => $newUser->name . "が参加しました。\n現在の参加者は、\n" . $users->display()
	  ];
	}

// 参加済みユーザーを一覧表示
} else if ($text == 'bot user list') {
	$users = new UserList();
	$response_format_text = [
		"type" => "text",
		"text" => "現在の参加者は、\n" . $users->display()
	];

// ユーザー削除処理
} else if (startWith($text, 'bot user delete')) {
	//TODO

} else if ($text == 'bot clear') {
	$date = date("Y-m-d-H-i-s");
	rename("users.txt", $date . "_users.txt");
	rename("charges.txt", $date . "_charges.txt");
	$response_format_text = [
		"type" => "text",
		"text" => "記録された情報をすべて削除しました。"
	];
}

if ($testMode) {
	echo $response_format_text["text"]; //TODO for test. plz delete
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
