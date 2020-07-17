<?php
$accessToken = getenv('LINE_CHANNEL_ACCESS_TOKEN');

//ユーザーからのメッセージ取得
$json_string = file_get_contents('php://input');
$jsonObj = json_decode($json_string);

$type = $jsonObj->{"events"}[0]->{"message"}->{"type"};
//メッセージ取得
$text = $jsonObj->{"events"}[0]->{"message"}->{"text"};
//メッセージID取得
$messageId = $jsonObj->{"events"}[0]->{"message"}->{"id"};
//ユーザーID取得
$userId = $jsonObj->{"events"}[0]->{"source"}->{"userId"};
//ReplyToken取得
$replyToken = $jsonObj->{"events"}[0]->{"replyToken"};
//massage0
$massage0 = '';
//massage1(BOT)
$massage1 = '<br>[word_balloon id="2" position="R" size="S" balloon="line" name_position="under_avatar" radius="true" avatar_border="false" avatar_shadow="false"balloon_shadow="true" avatar_hide="false" font_size="12"]';
//massage2(User)
$massage2 = '[word_balloon id="1" position="L" size="S" balloon="talk" name_position="under_avatar" radius="true" avatar_border="false" avatar_shadow="false" balloon_shadow="true" avatar_hide="false" font_size="12"]';
//massageend()
$massageend = '[/word_balloon]';
$massageshop = '';
$massagecat = '';
$etc_messages = '';

//Sendgrid-1
require __DIR__ . '/../vendor/autoload.php';
$sendgrid = new SendGrid(getenv('SENDGRID_USERNAME'), getenv('SENDGRID_PASSWORD'));
$email    = new SendGrid\Email();
$email->addTo('wpbot@azo.jp')
	  ->setFrom('linebot@azo.jp');

//メッセージ以外のときは何も返さず終了
if($type != "text" && $type != "image"){
	exit;
}

//メッセージから店舗名を取得
if (strpos($text,'グレースショップ') !== false) {
	$massagecat = '308072';
	$massageshop = 'グレースショップ';
} else if (strpos($text,'フルグレース') !== false) {
	$massagecat = '307755';
	$massageshop = 'フルグレース';
}

//返信データ作成
//画像の場合、サーバーに保存
if($type == "image"){
  //画像ファイルのバイナリ取得
  $ch = curl_init("https://api.line.me/v2/bot/message/".$messageId."/content");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
   'Content-Type: application/json; charser=UTF-8',
   'Authorization: Bearer ' . $accessToken
   ));
  $result = curl_exec($ch);
  curl_close($ch);
  //画像ファイルの作成
  $filename = date('Ymd-His').'.jpg';
  $filemessage = '';
  $fp = fopen('./img/'.$filename, 'wb');
  if ($fp){
      if (flock($fp, LOCK_EX)){
          if (fwrite($fp,  $result ) === FALSE){
              $filemessage = '（自動応答）画像の受け取りに失敗しました';
          }else{
              $filemessage = '（自動応答）画像を受け取りました！';
          }
          flock($fp, LOCK_UN);
      }else{
          $filemessage = '（自動応答）画像の受け取りに失敗しました';
      }
  }
  fclose($fp);
  $filePath = "https://".$_SERVER['SERVER_NAME'] . "/img/".$filename;
  $imagetag = '<img src="'.$filePath.'">';
  //確認メッセージを送信
  $response_format_text = [
    "type" => "text",
    "text" => $filemessage
  ];
	$massage0 = '（画像添付）';
	$email->setSubject($messageId)
		  ->setHtml('tags: '.$userId.'<br>'.$massage1.$filemessage.$massageend.$massage2.$massage0.$massageend.$imagetag);
	$sendgrid->send($email);
} else if ((strpos($text,'🌹'))||(strpos($text,'クーポン')) !== false){
  exit;
} else if (strpos($text,'購入予定です（') !== false) {
  $response_format_text = [
    "type" => "template",
	"altText" => "購入前",
    "template" => [
      "type" => "buttons",
	  "title" => "お問い合わせフォーム",
      "text" => $massageshop."楽天市場のお問い合わせフォームが開きます",
      "actions" => [
          [
            "type" => "uri",
            "label" => "問い合わせ",
            "uri" => "https://ask.step.rakuten.co.jp/inquiry-form/?page=simple-inquiry-top&act=login&shop_id=".$massagecat
          ]
      ]
    ]
  ];
} else if (strpos($text,'で注文済です') !== false) {
  $response_format_text = [
    "type" => "template",
	"altText" => "注文済",
    "template" => [
      "type" => "buttons",
	  "thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/img/rakuten01.png",
	  "imageAspectRatio" => "square",
	  "title" => "ショップへ問い合わせる",
      "text" => "［購入履歴を表示］よりお問い合わせください",
      "actions" => [
          [
            "type" => "uri",
            "label" => "購入履歴を表示",
            "uri" => "https://sp.order.my.rakuten.co.jp/?fidomy=1"
          ],
          [
            "type" => "message",
            "label" => "納期・配送状況について",
            "text" => $massageshop."の納期・配送状況についておしえて"
          ],
          [
            "type" => "message",
            "label" => "返品・交換・キャンセル",
            "text" => $massageshop."の返品・交換・キャンセルをしたい"
          ],
          [
            "type" => "message",
            "label" => "その他よくある質問",
            "text" => $massageshop."のよくある質問を見たい"
          ]
      ]
    ]
  ];
  $massage0 = $text;
  $email->setSubject('['.$massagecat.']'.$messageId)
		->setHtml('tags: '.$userId.'<br>'.$massage2.$massage0.$massageend);
  $sendgrid->send($email);
} else if ((strpos($text,'のお支払いについて') !== false)||(strpos($text,'のお届け先変更方法が知りたい') !== false)||(strpos($text,'の領収書が欲しい') !== false)||(strpos($text,'の納期・配送状況についておしえて') !== false)) {
  $response_format_text = [
    "type" => "template",
	"altText" => "購入履歴",
    "template" => [
      "type" => "buttons",
	  "thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/img/rakuten01.png",
	  "imageAspectRatio" => "square",
	  "title" => "ショップ情報",
      "text" => "お届け先の変更、領収書の発行につきましてはこちらからご連絡ください。",
      "actions" => [
          [
            "type" => "uri",
            "label" => "購入履歴を表示",
            "uri" => "https://sp.order.my.rakuten.co.jp/?fidomy=1"
          ]
      ]
    ]
  ];
  $massage0 = $text;
  $email->setSubject('['.$massagecat.']'.$messageId)
		->setHtml('tags: '.$userId.'<br>'.$massage2.$massage0.$massageend);
  $sendgrid->send($email);
} else if (strpos($text,'の返品・交換・キャンセルをしたい') !== false) {
  $massageurl = 'fullgrace';
  if($massagecat == '308072'){
	  $massageurl = 'graceshop-2';
  }
  $response_format_text = [
    "type" => "template",
	"altText" => "購入履歴",
    "template" => [
      "type" => "buttons",
	  "thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/img/rakuten01.png",
	  "imageAspectRatio" => "square",
	  "title" => "ショップ情報",
      "text" => "返品・交換・キャンセルにつきましては、こちらからご連絡ください。",
      "actions" => [
          [
            "type" => "uri",
            "label" => "購入履歴を表示",
            "uri" => "https://sp.order.my.rakuten.co.jp/?fidomy=1"
          ],
          [
            "type" => "uri",
            "label" => "返品・交換ポリシー",
            "uri" => "https://www.rakuten.co.jp/".$massageurl."/info.html#companyBrokenExchange_sp"
          ]
      ]
    ]
  ];
  $massage0 = $text;
  $email->setSubject('['.$massagecat.']'.$messageId)
		->setHtml('tags: '.$userId.'<br>'.$massage2.$massage0.$massageend);
  $sendgrid->send($email);
} else if (strpos($text,'のよくある質問を見たい') !== false) {
  $response_format_text = [
    "type" => "template",
	"altText" => "質問",
    "template" => [
      "type" => "buttons",
	  "title" => "よくある質問",
      "text" => "よくある質問はこちら",
      "actions" => [
          [
            "type" => "message",
            "label" => "営業時間のご案内",
            "text" => $massageshop."🌹の営業時間をおしえて"
          ],
          [
            "type" => "message",
            "label" => "お支払いについて",
            "text" => $massageshop."のお支払いについて"
          ],
          [
            "type" => "message",
            "label" => "お届け先の変更",
            "text" => $massageshop."のお届け先変更方法が知りたい"
          ],
          [
            "type" => "message",
            "label" => "領収書が欲しい",
            "text" => $massageshop."の領収書が欲しい"
          ]
      ]
    ]
  ];
} else {
  $response_format_text = [
    "type" => "template",
	"altText" => "default",
    "template" => [
        "type" => "buttons",
        "text" => "このアカウントは自動応答のみでのご対応になります。\nはじめにご利用店舗とご利用状況をご選択ください。\n①フルグレース\n②グレースショップ",
        "actions" => [
            [
              "type" => "message",
              "label" => "①でご購入予定",
              "text" => "購入予定です（フルグレース）"
            ],
            [
              "type" => "message",
              "label" => "①でご注文済",
              "text" => "フルグレースで注文済です"
            ],
            [
              "type" => "message",
              "label" => "②でご購入予定",
              "text" => "購入予定です（グレースショップ）"
            ],
            [
              "type" => "message",
              "label" => "②でご注文済",
              "text" => "グレースショップで注文済です"
            ]
        ]
    ]
  ];
  $etc_messages = [
    "type" => "template",
	"altText" => "購入履歴",
    "template" => [
      "type" => "buttons",
	  "title" => "購入履歴を表示",
      "text" => "ご購入店舗がご不明な場合、こちらから購入履歴ページの閲覧と、お問い合わせが可能です。",
      "actions" => [
          [
            "type" => "uri",
            "label" => "購入履歴を表示",
            "uri" => "https://sp.order.my.rakuten.co.jp/?fidomy=1"
          ]
      ]
    ]
  ];
  if ((strpos($text,'納期') !== false)||(strpos($text,'変更') !== false)||(strpos($text,'返品') !== false)||(strpos($text,'名前') !== false)){
  //メール送信（納期、変更、返品、名前）
  $massage0 = $text;
  $email->setSubject($messageId)
		->setHtml('tags: '.$userId.'<br>'.$massage2.$massage0.$massageend);
  $sendgrid->send($email);
  }
}
if (!empty($etc_messages)) {
	$post_data = [
		"replyToken" => $replyToken,
		"messages" => [$response_format_text,$etc_messages,]
	];
} else {
	$post_data = [
		"replyToken" => $replyToken,
		"messages" => [$response_format_text]
	];
}

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
