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
  echo "Welcome at Slim Framework";
});
 
// buat route untuk webhook
$app->post('/webhook', function ($request, $response) use ($bot, $pass_signature, $httpClient)
{
    // get request body and line signature header
    $body        = file_get_contents('php://input');
    $signature   = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';
 
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
    if(is_array($data['events'])){
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if($event['message']['type'] == 'text')
                {
                    if($event['source']['userId']){
                        if (strtolower($event['message']['text']) == 'berangkat !'){
                            $flexTemplate = file_get_contents("flex_message.json"); // load template flex message
                            $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                                'replyToken' => $event['replyToken'],
                                'messages'   => [
                                    [
                                        'type'     => 'flex',
                                        'altText'  => 'Semangat menggapai mimpi !',
                                        'contents' => json_decode($flexTemplate)
                                    ]
                                ],
                            ]);
                            return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                       
                        }
                        
                        elseif(strtolower($event['message']['text'] == 'hai')){ // apabila dikenali userId nya, maka pesan dibalas dengan menyebut nama pengguna dan memanggil "adabot"
                            $userId     = $event['source']['userId'];
                            $getprofile = $bot->getProfile($userId);
                            $profile    = $getprofile->getJSONDecodedBody();
                            $greetings  = new TextMessageBuilder("Halo, ".$profile['displayName']);
                            
                            $result = $bot->replyMessage($event['replyToken'], $greetings);
                            return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                            echo "Send message who call adabot";
                        }

                        else {// send same message as reply to user
                            $result = $bot->replyText($event['replyToken'], $event['message']['text']);
                            return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus()); 
                        }                     
                    } 

                    else {// send same message as reply to user apabila userId blm diketahui
                        $result = $bot->replyText($event['replyToken'], $event['message']['text']);
                        return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus()); 
                    }  
                } 
                

                elseif ($event['message']['type'] == 'sticker'){ // apabila pesan yang dikirimkan berupa sticker, maka kirimkan sticker yang sama
                    $stickerID      = $event['message']['stickerId'];
                    $pakgID         = $event['message']['packageId'];
                    $stickerMessageBuilder = new StickerMessageBuilder($pakgID, $stickerID);
                    $result         = $bot->replyMessage($event['replyToken'], $stickerMessageBuilder);
                    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                }
                

                elseif // untuk pesan berupa audio, image, video dan file
                (
                    $event['message']['type'] == 'image' or
                    $event['message']['type'] == 'video' or
                    $event['message']['type'] == 'audio' or
                    $event['message']['type'] == 'file'
                ){
                    $basePath  = $request->getUri()->getBaseUrl();
                    $contentURL  = $basePath."/content/".$event['message']['id'];
                    $contentType = ucfirst($event['message']['type']);
                    $result = $bot->replyText($event['replyToken'],
                        $contentType. " haiiii, yang Anda kirim bisa diakses dari link:\n " . $contentURL);
                 
                    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                }
            }
        } 
    }
 
});
// untuk mendapatkan content API
$app->get('/content/{messageId}', function($req, $res) use ($bot)
{
    // get message content
    $route      = $req->getAttribute('route');
    $messageId  = $route->getArgument('messageId');
    $result     = $bot->getMessageContent($messageId);
 
    // set response
    $res->write($result->getRawBody());
 
    return $res->withHeader('Content-Type', $result->getHeader('Content-Type'));
});
file_put_contents('php://stderr', $output);
$app->run();