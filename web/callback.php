<?php

////////////////// include /////////////////////
include 'Charge.php';
include 'ChargeList.php';
include 'User.php';
include 'UserList.php';
require_once(__DIR__."/../vendor/autoload.php");

use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;

////////////////// env /////////////////////

$accessToken = getenv('LINE_CHANNEL_ACCESS_TOKEN');
$secretToken = getenv('LINE_CHANNEL_SECRET');
$httpClient = new CurlHTTPClient($accessToken);
$bot = new LINEBot($httpClient, ['channelSecret' => $secretToken]);

$testMode = false;
if ($testMode) {
	$text = "あんこう";
	$userId = "a";

} else {
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

////////////////// Util /////////////////////
function illegalArgumentResponse() {
	return new TextMessageBuilder("入力が正しくありません\n'bot'でヘルプが確認できます");
}

function alreadyJoinedResponse($userId) {
	$users = new UserList();
	$userName = $users->getNameById($userId);
	return new TextMessageBuilder("あなたはすでに '" . $userName . "' として参加しています");
}

function notExistUserNameResponse($userName) {
	$users = new UserList();
	return new TextMessageBuilder(
		"指定されたユーザー " . $userName . " が存在しません\n現在の参加者は\n" . $users->display());
}

function notJoinedUserResponse() {
	return new TextMessageBuilder('あなたはユーザーとして参加していません');
}

function notNumericResponse() {
	return new TextMessageBuilder('半角数字を入力してください');
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
	$chargeActions = array();
	array_push($chargeActions, new MessageTemplateActionBuilder("登録", "charge add"));
	array_push($chargeActions, new MessageTemplateActionBuilder("一覧", "bot list"));
	array_push($chargeActions, new MessageTemplateActionBuilder("清算", "bot calc"));

	$userActions = array();
	array_push($userActions, new MessageTemplateActionBuilder("参加", "user join"));
	array_push($userActions, new MessageTemplateActionBuilder("一覧", "bot user list"));
	array_push($userActions, new MessageTemplateActionBuilder("! 支払い削除 !", "charge delete"));

	$columns = array();
	array_push($columns, new CarouselColumnTemplateBuilder("支払い操作", "支払い操作です！", null, $chargeActions));
	array_push($columns, new CarouselColumnTemplateBuilder("ユーザー操作", "ユーザー操作です！", null, $userActions));

	$carousel = new CarouselTemplateBuilder($columns);
	$sendMessage = new TemplateMessageBuilder("this is a carousel template", $carousel);

} else if ($text == "キャンセル") {
	file_put_contents('botStatus.txt', "");
	$sendMessage = new TextMessageBuilder('現在の処理をキャンセルしました');

//// 支払い追加処理 ////
} else if ($text == "charge add") {
	if (!isAlreadyJoinUser($userId)) {
		$sendMessage = notJoinedUserResponse();
	} else {
		$users = new UserList();
		$sendMessage = new TextMessageBuilder(
			"以下から対象を入力してください\n全員\n" . $users->display());
		file_put_contents('botStatus.txt', "waiting target");
	}

} else if ($botStatus == "waiting target") {
	$target = $text;
	if ($target != '全員' && !isExistUserName($target)) {
		$sendMessage = notExistUserNameResponse($target);
	} else {
		$sendMessage = new TextMessageBuilder('金額を入力してください');

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
		$sendMessage = notNumericResponse();
	} else {
		$sendMessage = new TextMessageBuilder('コメントを入力してください(例: 〇〇代)');
		file_put_contents('botStatus.txt', "waiting comment");
		file_put_contents('tmpValue.txt', $text);
	}

} else if ($botStatus == "waiting comment") {
	if ($userId != file_get_contents('tmpOwnerId.txt')) {
		// Nothing to do
	} else if (!isAlreadyJoinUser($userId)) {
		$sendMessage = notJoinedUserResponse();
	} else {
		$comment = $text;
		$ownerName = getUserNameById($userId);
		$target = file_get_contents('tmpTarget.txt');
		$value = file_get_contents('tmpValue.txt');

		$chargeDao = new ChargeDao();
		$chargeDao->post($ownerName, $value, $target, $comment);

		if ($target == "all") {
			$sendMessage = new TextMessageBuilder(
				$ownerName . "さんが全員分として " . $value . "円を立て替えました");
		} else {
			$sendMessage = new TextMessageBuilder(
				$ownerName . "さんが" . $target . "さんに " . $value . "円を立て替えました");
		}

		file_put_contents('botStatus.txt', "");
	}


//// 支払い削除 ////
} else if ($text == "charge delete") {
	$sendMessage = new TextMessageBuilder("削除するIDを入力してください");
	file_put_contents('botStatus.txt', "waiting delete id");

} else if ($botStatus == "waiting delete id") {
	if (!is_numeric($text)) {
		$sendMessage = notNumericResponse();
	} else {
		$chargeDao = new ChargeDao();
		$chargeDao->delete($text);

		$sendMessage = new TextMessageBuilder($text . "を削除しました");
		file_put_contents('botStatus.txt', "");
	}

//// ユーザー処理 ////
} else if ($text == "user join") {
	$req = explode(" ", $text);
	if (isAlreadyJoinUser($userId)) {
		$sendMessage = alreadyJoinedResponse($userId);
	} else if (count($req) == 2) {
		$sendMessage = new TextMessageBuilder("ユーザー名を入力してください");
		file_put_contents('botStatus.txt', "waiting user name");
	}
} else if ($botStatus == "waiting user name") {
	$userName = $text;
	$userDao = new UserDao();
	$userDao->post($userId, $userName);

	$users = new UserList();
	$sendMessage = new TextMessageBuilder(
							$userName . "が参加しました\n現在の参加者は\n" . $users->display());

	file_put_contents('botStatus.txt', "");

//CLI
} else {
	// help表示
	if ($text == 'bot') {
		$sendMessage = new TextMessageBuilder(
							"[Help]\n"
							. "〇ユーザーとして参加:\n  bot join <人名>\n"
							. "〇参加者一覧:\n  bot user list\n"
							. "〇支払追加:\n  bot add <金額> <立替先(人名 or 'all')> <コメント>\n"
							. "〇支払一覧:\n  bot list\n"
							. "〇支払清算:\n  bot calc");

	// 料金追加処理
	} else if (startWith($text, 'bot add')) {
		$req = explode(" ", $text);
		if (!isAlreadyJoinUser($userId)) {
			$sendMessage = notJoinedUserResponse();
		} else if (count($req) != 5) {
			$sendMessage = illegalArgumentResponse();
		} else if (!is_numeric($req[2])) {
			$sendMessage = illegalArgumentResponse();
		} else if ($req[3] != 'all' && !isExistUserName($req[3])) {
			$sendMessage = notExistUserNameResponse($req[3]);
		} else {
			$ownerName = getUserNameById($userId);
			$value = $req[2];
			$target = $req[3];
			$comment = $req[4];

			$chargeDao = new ChargeDao();
			$chargeDao->post($ownerName, $value, $target, $comment);

			$sendMessage = new TextMessageBuilder($ownerName . "さんが" . $target . "さんに " . $value . " 円を立て替えました");
		}

	// 料金一覧返却
	} else if ($text == 'bot list') {
		$charges = new ChargeList();
		$sendMessage = new TextMessageBuilder($charges->display());

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

		$sendMessage = new TextMessageBuilder(
									"プラスの人が支払い、マイナスの人が受け取りをして下さい\n"
									. chargeMapToString($calcCharge));

	// 支払い削除処理
	} else if (startWith($text, 'bot delete')) {
		$req = explode(" ", $text);
		if (count($req) != 3) {
			$sendMessage = illegalArgumentResponse();
		} else if (!is_numeric($req[2])) {
			$sendMessage = notNumericResponse();
		} else {
			$id = $req[2];
			$chargeDao = new ChargeDao();
			$chargeDao->delete($id);

			$sendMessage = new TextMessageBuilder($id . "を削除しました");
		}

	// ユーザー追加処理
	} else if (startWith($text, 'bot join')) {
		$req = explode(" ", $text);
			if (isAlreadyJoinUser($userId)) {
				$sendMessage = alreadyJoinedResponse($userId);
			} else if (count($req) != 3) {
				$sendMessage = illegalArgumentResponse();
			} else {
			$userName = $req[2];
			$userDao = new UserDao();
			$userDao->post($userId, $userName);

			$users = new UserList();
			$sendMessage = new TextMessageBuilder(
				$userName . "が参加しました\n現在の参加者は\n" . $users->display());
		}

	// 参加済みユーザーを一覧表示
	} else if ($text == 'bot user list') {
		$users = new UserList();
		$sendMessage = new TextMessageBuilder("現在の参加者は\n" . $users->display());

	// データ全削除
	} else if ($text == 'bot clear') {
		$date = date("Y-m-d-H-i-s");
		rename("users.txt", $date . "_users.txt");
		rename("charges.txt", $date . "_charges.txt");
		$sendMessage = new TextMessageBuilder("記録された情報をすべて削除しました");

	//Joke
	} else if (strpos($text, "ガルパン") !== false) {
		$sendMessage = new TextMessageBuilder("ガルパンはいいぞ");
	}
}

if ($sendMessage != null) {
	$bot->replyMessage($replyToken, $sendMessage);
}
