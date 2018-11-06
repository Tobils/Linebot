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

    // untuk membedakan antara chat d group dan pesan dari user single
    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if(
                    $event['source']['type'] == 'group' or
                    $event['source']['type'] == 'room'
                ){ // group atau room char]t
                    if($event['source']['userId']){

                        $userId     = $event['source']['userId'];
                        $getprofile = $bot->getProfile($userId);
                        $profile    = $getprofile->getJSONDecodedBody();
                        $greetings  = new TextMessageBuilder("Hallo, " .$profile ['displayName']);
                        
                        $stickerMessageBuilder = new StickerMessageBuilder(1,106);
                        $multiMessageBuilder   = new MultiMessageBuilder();
                        $multiMessageBuilder->add($greetings);
                        $multiMessageBuilder->add($stickerMessageBuilder); 
                        $result     = $bot->replyMessage($event['replyToken'], $multiMessageBuilder);
                        return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                    }  
                } else { // single chat
                    $result = $bot->replyText($event['replyToken'], $event['message']['text']);
                    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());

                 }
            }
        }
    }

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
    // untuk mendapat profile API
    $app->get('/profile', function($req, $res) use ($bot)
    {
        // get user profile
        $route  = $req->getAttribute('route');
        $userId = $route->getArgument('userId');
        $result = $bot->getProfile ($userId);
    
        return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
    });
});
file_put_contents('php://stderr', $output);
$app->run();