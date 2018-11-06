<?php
require __DIR__ . '/vendor/autoload.php';
 
use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;
 
// set false for production
$pass_signature = true;
 
// set LINE channel_access_token and channel_secret
$channel_access_token = "Gw1bZ6cwKxln54UvtUmN1ymqp6/sVk8aMLZPGBbIku3Veh13BmOrDw4x8FrGz6Qz9oHV8jd/mwZtsNpv2vY4pNU0HJyCxdO3HgVEYMMqWXaGH5goOcUM7jv2ldz7L61OsWu4vV/gZxI/qg+ZeUkwfgdB04t89/1O/w1cDnyilFU=";
$channel_secret = "e2ae44d00a1fb7bdd23b2d66f58a3530";
 
// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
 
$configs =  [
    'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);
 
// buat route untuk url homepage
$app->get('/', function($req, $res)
{
  echo "Hello Sang Pejuang !, Gan Batte !!!!";
});
 
// buat route untuk webhook
$app->post('/webhook', function ($request, $response) use ($bot, $pass_signature)
{
    // get request body and line signature header
    $body      = file_get_contents('php://input');
    $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';
 
    // log body and signature
    file_put_contents('php://stderr', 'Body: '.$body);
 
    if($pass_signature === false)
    {
        // is LINE_SIGNATURE exists in request header?
        if(empty($signature)){
            return $response->withStatus(400, 'Signature not set');
        }
 
        // is this request comes from LINE?
        if(! SignatureValidator::validateSignature($body, $channel_secret, $signature)){
            return $response->withStatus(400, 'Invalid signature');
        }
    }
 
    // kode aplikasi nanti disini

    $data = json_decode($body, true);
    if(is_array($data['events']))
    {
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if($event['message']['type'] == 'text')
                {
                    // untuk model Content API
                    if(
                        $event['message']['type'] == 'image' or
                        $event['message']['type'] == 'video' or
                        $event['message']['type'] == 'audio' or
                        $event['message']['type'] == 'file'
                    ){
                        $basePath  = $request->getUri()->getBaseUrl();
                        $contentURL  = $basePath."/content/".$event['message']['id'];
                        $contentType = ucfirst($event['message']['type']);
                        $result = $bot->replyText($event['replyToken'],
                            $contentType. " yang Anda kirim bisa diakses dari link:\n " . $contentURL);
                    
                        return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                    }
                    else {
                        // send same message as reply to user
                        // $result = $bot->replyText($event['replyToken'], $event['message']['text']);
                        // $result = $bot->replyText($replyToken, 'ini pesan balasan');
                        // $textMessageBuilder = new TextMessageBuilder('ini pesan balasan');
                        // $result = $bot->replyMessage($replyToken, $textMessageBuilder);

                        // or we can use replyMessage() instead to send reply message
                        // $textMessageBuilder1 = new TextMessageBuilder('ini adalah pesan balasan 1');
                        // $textMessageBuilder2 = new TextMessageBuilder('ini adalah pesan balasan 2');
                        // $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);

                        // try to rplay using sticker
                        // $packageid = 1;
                        // $stickerid = 13;
                        // $stickerMessageBuilder = new StickerMessageBuilder($packageid, $stickerid);
                        // $result = $bot->replyMessage($event['replyToken'], $stickerMessageBuilder);
                        
                        // try to reply multiple message (2 Message and 1 sticker)
                        // $textMessageBuilder1 = new TextMessageBuilder('ini adalah pesan balasan 1');
                        // $textMessageBuilder2 = new TextMessageBuilder('ini adalah pesan balasan 2');
                        // $stickerMessageBuilder = new StickerMessageBuilder(1,106);

                        // $multiMessageBuilder = new MultiMessageBuilder();
                        // $multiMessageBuilder -> add($textMessageBuilder1);
                        // $multiMessageBuilder -> add($textMessageBuilder2);
                        // $multiMessageBuilder -> add($stickerMessageBuilder);

                        // terjadi kesalahan sebelumnya harusnya tanda -> , tp malah =
                        
                        $stickerMessageBuilder = new StickerMessageBuilder(1,106);

                        $result = $bot->replyMessage($event['replyToken'], $stickerMessageBuilder);
                        
                        return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                        

                    }
                }
            }
        } 
    }
});

$app->get('/pushmessage', function($req, $res) use ($bot)
{
    // send push message to user
    $userId = 'U0c39fbef2dfcab2b38de2e70586d805b'; // user id nya jaler
    $textMessageBuilder = new TextMessageBuilder('Halo ade, ini pesan push');
    $stickerMessageBuilder = new StickerMessageBuilder(1,106);

    $multiMessageBuilder = new MultiMessageBuilder();
    $multiMessageBuilder -> add($textMessageBuilder);
    $multiMessageBuilder -> add($stickerMessageBuilder);

    $result = $bot->pushMessage($userId, $multiMessageBuilder);
   
    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
});
file_put_contents('php://stderr', $output);

// content API
$app->get('/content/{messageId}', function($req, $res) use ($bot)
{
    // get message content
    $route      = $req->getAttribute('route');
    $messageId = $route->getArgument('messageId');
    $result = $bot->getMessageContent($messageId);
 
    // set response
    $res->write($result->getRawBody());
 
    return $res->withHeader('Content-Type', $result->getHeader('Content-Type'));
});

$app->run();



// $app->get('/profile', function($req, $res) use ($bot)
// {
//     // get user profile
//     $userId = 'Ub24ed1de83ce73879ebeb84b20c5153e'; // userID jaler
//     $result = $bot->getProfile($userId);
   
//     return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
// });

// $app->get('/pushmessage', function($req, $res) use ($bot)
// {
//     // send push message to user
//     $userId = 'Ub24ed1de83ce73879ebeb84b20c5153e'; // user id nya jaler
//     $textMessageBuilder = new TextMessageBuilder('Halo jaler, ini pesan push');
//     $result = $bot->pushMessage($userId, $textMessageBuilder);
   
//     return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
// });

// diletakan sebelum code $app->run();