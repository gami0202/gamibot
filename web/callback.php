<?php
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


////////////////// include /////////////////////
include 'Charge.php';
include 'ChargeList.php';
include 'User.php';
include 'UserList.php';

////////////////// Util /////////////////////
function illegalArgumentResponse() {
	return [
		"type" => "text",
		"text" => "入力が正しくありません。"
	];
}

function alreadyJoinedResponse() {
	$users = new UserList();
	return [
		"type" => "text",
		"text" => "すでに参加しているユーザーです。\n現在の参加者は、\n" . $users->display()
	];
}

function isAlreadyJoinUser($newUserId) {
	$users = new UserList();
	$existUserIds = $users->getIdArray();
	return in_array($newUserId, $existUserIds);
}

function startWith($str, $prefix) {
	return substr($str,  0, strlen($prefix)) === $prefix;
}

////////////////// Main /////////////////////
メッセージ以外のときは何も返さず終了
if($type != "text"){
	exit;
}

// $text = "bot join unknown3";  //TODO for test. plz delete
// $userId = "ddd";  //TODO for test. plz delete

//返信データ作成
if ($text == 'bot') {
	$response_format_text = [
		"type" => "text",
    "text" => '追加方法: bot add <支払い金額(数字のみ)> <支払者(人名 or "全員")>'
  ];

} else if (startWith($text, 'bot add')) {
	$req = explode(" ", $text);
	if (count($req) != 4) {
		$response_format_text = illegalArgumentResponse();
	} else {
		$charges = new ChargeList();
		$newCharge = new Charge($charges->getNextId(), $userId, $req[2], $req[3]);
		$newCharge->addDb();
		$response_format_text = [
			"type" => "text",
	    "text" => $newCharge->display()
	  ];
	}

} else if ($text == 'bot list') {
	$charges = new ChargeList();
	$response_format_text = [
		"type" => "text",
		"text" => $charges->display()
	];

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
}

echo $response_format_text["text"]; //TODO for test. plz delete


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
