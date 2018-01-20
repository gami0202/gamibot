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
use \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

////////////////// env /////////////////////

$accessToken = getenv('LINE_CHANNEL_ACCESS_TOKEN');
$secretToken = getenv('LINE_CHANNEL_SECRET');
$httpClient = new CurlHTTPClient($accessToken);
$bot = new LINEBot($httpClient, ['channelSecret' => $secretToken]);

//ユーザーからのメッセージ取得
$json_string = file_get_contents('php://input');
$jsonObj = json_decode($json_string);

$type = $jsonObj->{"events"}[0]->{"type"};

switch ($type) {
	case "message":
		$text = $jsonObj->{"events"}[0]->{"message"}->{"text"};
		break;
	case "postback":
		$postback = $jsonObj->{"events"}[0]->{"postback"}->{"data"};
		parse_str($postback, $data);
		$action = $data["action"];
		break;
	default:
		exit;
}

//メッセージ取得
//ReplyToken取得
$replyToken = $jsonObj->{"events"}[0]->{"replyToken"};

$userId = $jsonObj->{"events"}[0]->{"source"}->{"userId"};

$squadType = $jsonObj->{"events"}[0]->{"source"}->{"type"};
switch ($squadType) {
	case "group":
		$squadId = $jsonObj->{"events"}[0]->{"source"}->{"groupId"};
		break;
	case "room":
		$squadId = $jsonObj->{"events"}[0]->{"source"}->{"roomId"};
		break;
	case "user":
		$squadId = $userId;
		break;
	default:
		$squadId = $userId;
}

$users = new UserList($squadId);

////////////////// Util /////////////////////
function illegalArgumentResponse() {
	return new TextMessageBuilder("入力が正しくありません\n'bot'でヘルプが確認できます");
}

function alreadyJoinedResponse($userId, $users) {
	$userName = $users->getNameById($userId);
	return new TextMessageBuilder("あなたはすでに '" . $userName . "' として参加しています");
}

function notExistUserNameResponse($userName, $users) {
	return new TextMessageBuilder(
		"指定されたユーザー " . $userName . " が存在しません\n現在の参加者は\n" . $users->display());
}

function notJoinedUserResponse() {
	return new TextMessageBuilder('あなたはユーザーとして参加していません');
}

function notNumericResponse() {
	return new TextMessageBuilder('半角数字を入力してください');
}

function isAlreadyJoinUser($userId, $users) {
	$existUserIds = $users->getIdArray();
	return in_array($userId, $existUserIds);
}

function isExistUserName($userName, $users) {
	$existUserNames = $users->getNameArray();
	return in_array($userName, $existUserNames);
}

// ユーザー一覧から、ユーザー名が前方一致したものを返却。
// 最初にヒットしたものを返す。見つからなければnullを返す。
//TODO 複数ヒットしたらエラーにできるように
function getUserNameWithForwardMatch($userNamePart, $users) {
	foreach ($users->userList as $user) {
		if (startWith($user->name, $userNamePart)) {
	    return $user->name;
		}
	}
	return null;
}

function startWith($str, $prefix) {
	return substr($str,  0, strlen($prefix)) === $prefix;
}

function chargeMapToString($map) {
	$string = "";
	foreach ($map as $key => $value) {
		$string = $string . "〇" . $key . ": " . number_format($value) . "円\n";
	}
	return $string;
}

function payExampleToString($map) {
	$string = "";
	foreach ($map as $reciever => $payerMap) {
		$string = $string . "〇受け取り: " . $reciever . "\n";
		foreach ($payerMap as $payer => $value) {
			$string = $string . "  ・" . $payer . ": " . number_format($value) . "円\n";
		}
	}
	return $string;
}

////////////////// Main /////////////////////
$botStatus = file_get_contents('botStatus.txt');

if ($text == 'あんこう') {
	$chargeActions = array();
	array_push($chargeActions, new PostbackTemplateActionBuilder("登録", "action=chargeAdd"));
	array_push($chargeActions, new PostbackTemplateActionBuilder("一覧", "action=botList"));
	array_push($chargeActions, new PostbackTemplateActionBuilder("清算", "action=botCalc"));

	$userActions = array();
	array_push($userActions, new PostbackTemplateActionBuilder("参加", "action=botJoin"));
	array_push($userActions, new PostbackTemplateActionBuilder("一覧", "action=botUserList"));
	array_push($userActions, new PostbackTemplateActionBuilder("! 支払い削除 !", "action=chargeDelete"));

	$columns = array();
	array_push($columns, new CarouselColumnTemplateBuilder("支払い操作", "支払い操作です！", null, $chargeActions));
	array_push($columns, new CarouselColumnTemplateBuilder("ユーザー操作", "ユーザー操作です！", null, $userActions));

	$carousel = new CarouselTemplateBuilder($columns);
	$sendMessage = new TemplateMessageBuilder("this is a carousel template", $carousel);

} else if ($text == "キャンセル") {
	file_put_contents('botStatus.txt', "");
	$sendMessage = new TextMessageBuilder('現在の処理をキャンセルしました');

//// 支払い追加処理 ////
} else if ($action == "chargeAdd") {
	if (!isAlreadyJoinUser($userId, $users)) {
		$sendMessage = notJoinedUserResponse();
	} else {
		$userNames = $users->getNameArray();

		if (count($userNames) > 29) { // carousel は 3*10 までしか表示できない
			$sendMessage = new TextMessageBuilder(
				"以下から対象を入力してください\n全員\n" . $users->display());
		} else {
			$columns = array();
			$actions = array();

			array_push($actions, new MessageTemplateActionBuilder("全員", "全員"));
			$index = 1;
			foreach ($userNames as $userName) {
				array_push($actions, new MessageTemplateActionBuilder($userName, $userName));
				if ($index++ %3 == 2) { // carousel のアクションは3つまで
					array_push($columns, new CarouselColumnTemplateBuilder("対象一覧", "たてかえ対象を選んでください", null, $actions));
					$actions = array();
				}
			}

			if (count($actions) % 3 != 0) {
				array_push($actions, new MessageTemplateActionBuilder("ダミー", "ダミー"));
				if (count($actions) % 3 != 0) {
					array_push($actions, new MessageTemplateActionBuilder("ダミー", "ダミー"));
				}
				array_push($columns, new CarouselColumnTemplateBuilder("対象一覧", "たてかえ対象を選んでください", null, $actions));
			}

			$carousel = new CarouselTemplateBuilder($columns);
			$sendMessage = new TemplateMessageBuilder("this is a carousel template", $carousel);
		}

		file_put_contents('botStatus.txt', "waiting target");
	}

} else if ($botStatus == "waiting target") {
	$target = $text;
	if ($target != '全員' && !isExistUserName($target, $users)) {
		$sendMessage = notExistUserNameResponse($target, $users);
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
	} else if (!isAlreadyJoinUser($userId, $users)) {
		$sendMessage = notJoinedUserResponse();
	} else {
		$comment = $text;
		$ownerName = $users->getNameById($userId);
		$target = file_get_contents('tmpTarget.txt');
		$value = file_get_contents('tmpValue.txt');

		$chargeDao = new ChargeDao();
		$chargeDao->post($ownerName, $value, $target, $comment, $squadId);

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
} else if ($action == "chargeDelete") {
	$sendMessage = new TextMessageBuilder("削除するIDを入力してください");
	file_put_contents('botStatus.txt', "waiting delete id");

} else if ($botStatus == "waiting delete id") {
	if (!is_numeric($text)) {
		$sendMessage = notNumericResponse();
	} else {
		$chargeDao = new ChargeDao();
		$chargeDao->delete($text, $squadId);

		//TODO 削除できなかったら、メッセージを変える
		$sendMessage = new TextMessageBuilder($text . "を削除しました");
		file_put_contents('botStatus.txt', "");
	}

//CLI
} else {
	// help表示
	if ($text == 'bot') {
		$sendMessage = new TextMessageBuilder(
							"[Help]\n"
							. "〇ユーザーとして参加:\n  bot join\n"
							. "〇参加者一覧:\n  bot user list\n"
							. "〇支払追加:\n  bot add <金額> <立替先(人名 or 'all')> <コメント>\n"
							. "〇支払一覧:\n  bot list\n"
							. "〇支払清算:\n  bot calc\n"
							. "〇支払削除:\n  bot delete <id>");

	// 料金追加処理
	} else if (startWith($text, 'bot add')) {
		$req = explode(" ", $text);
		if (!isAlreadyJoinUser($userId, $users)) {
			$sendMessage = notJoinedUserResponse();
		} else if (count($req) != 5) {
			$sendMessage = illegalArgumentResponse();
		} else if (!is_numeric($req[2])) {
			$sendMessage = illegalArgumentResponse();
		} else if ($req[3] != 'all' && !isExistUserName($req[3], $users)
		 	&& getUserNameWithForwardMatch($req[3], $users) == null) {
			$sendMessage = notExistUserNameResponse($req[3], $users);
		} else {
			$ownerName = $users->getNameById($userId);
			$value = $req[2];
			$target = getUserNameWithForwardMatch($req[3], $users);
			$comment = $req[4];

			$chargeDao = new ChargeDao();
			$chargeDao->post($ownerName, $value, $target, $comment, $squadId);

			$sendMessage = new TextMessageBuilder($ownerName . "さんが" . $target . "さんに " . $value . " 円を立て替えました");
		}

	// 料金一覧返却
	} else if ($text == 'bot list' || $action == "botList") {
		$charges = new ChargeList($squadId);
		$sendMessage = new TextMessageBuilder($charges->display());

	// 料金清算処理
	} else if ($text == 'bot calc' || $action == "botCalc") {
		$charges = new ChargeList($squadId);

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

		$message = "プラスの人が支払い、マイナスの人が受け取りをして下さい\n"
									. chargeMapToString($calcCharge);

		//// 支払い例の計算 ////
		// プラスマイナスを分ける
		$plusValues = array();
		$minusValues = array();
		foreach ($calcCharge as $owner => $value) {
			if ($value < 0) {
				$minusValues[$owner] = -1 * $value;
			} else {
				$plusValues[$owner] = $value;
			}
		}

		// 絶対値が大きい順にソート
		arsort($plusValues);
		arsort($minusValues);

		// プラスの大きい人からマイナスの大きい人へ支払いをしていく
		$payExample = array();
		foreach ($minusValues as $reciever => $rvalue) {
		  $recieveMap = array();
		  $restRecieveValue = $rvalue;

		  foreach ($plusValues as $payer => $pvalue) {
		    if ($pvalue == 0) {
		      continue;
		    } else if ($restRecieveValue <= $pvalue) { // reciverの受け取り完了
		      $recieveMap[$payer] = $restRecieveValue;
		      $plusValues[$payer] -= $restRecieveValue;
		      break;
		    } else {
		      array_merge($recieveMap, array($payer => $restPayValue));
		      $recieveMap[$payer] = $pvalue;
		      $restRecieveValue -= $pvalue;
		      $plusValues[$payer] = 0;
		    }
		  }
		  $payExample[$reciever] = $recieveMap;
		}

		$message = $message . "\n"
			. "支払い例\n" . payExampleToString($payExample);

		$sendMessage = new TextMessageBuilder($message);

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
			$chargeDao->delete($id, $squadId);

			//TODO 削除できなかったら、メッセージを変える
			$sendMessage = new TextMessageBuilder($id . "を削除しました");
		}

	// ユーザー追加処理
	} else if ($text == 'bot join' || $action == "botJoin") {
			if (isAlreadyJoinUser($userId, $users)) {
				$sendMessage = alreadyJoinedResponse($userId, $users);
			} else {
				$response = $bot->getProfile($userId);
			  $profile = $response->getJSONDecodedBody();
			  $userName = $profile['displayName'];

				$userDao = new UserDao();
				$userDao->post($userId, $userName, $squadId);

				$users = new UserList($squadId); // post後のものを再取得
				$sendMessage = new TextMessageBuilder(
					$userName . "が参加しました\n現在の参加者は\n" . $users->display());
		}

	// 参加済みユーザーを一覧表示
	} else if ($text == 'bot user list' || $action == "botUserList") {
		$sendMessage = new TextMessageBuilder("現在の参加者は\n" . $users->display());

	// データ全削除
	} else if ($text == 'bot clear') {
		$chargeDao = new ChargeDao();
		$userDao = new UserDao();

		$chargeDao->deleteAll();
		$userDao->deleteAll();

		$sendMessage = new TextMessageBuilder("記録された情報をすべて削除しました");

	//Joke
	} else if (strpos($text, "ガルパン") !== false) {
		$sendMessage = new TextMessageBuilder("ガルパンはいいぞ");
	}
}

if ($sendMessage != null) {
	$bot->replyMessage($replyToken, $sendMessage);
}
