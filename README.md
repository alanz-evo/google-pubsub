## 初始化設定
```php artisan vendor:publish --provider="AlanzEvo\GooglePubsub\Providers\GooglePubsubProvider" --tag="config"```<br />
執行完指令後，將會產生出 `config/pubsub.php` 這個設定檔案 

## 設定檔說明
```PHP
return [
    // 連線設定
    'connections' => [
        /**
         *  詳細參數可以參考 http://googleapis.github.io/google-cloud-php/#/docs/cloud-pubsub/v1.34.1/pubsub/pubsubclient
         *  當中 __construct 的 config 說明
         */

        // 所有設定都是用 Google\Cloud\PubSub\PubSubClient 的預設值
        'basic' => [],

        // 從指定路徑的檔案中取得授權證書
        'useKeyFilePath' => [
            'keyFilePath' => '/path/to/key.json',
        ],

        // 從 array 設定授權證書的內容
        'keyFile' => [
            'keyFile' => [...],
        ],
    ],

    'listeners' => [
        // Listener 的設定
        'my-listener' => [
            'messageLockSec' => 30,  // 每則 Message 在處理時的鎖定時間，避免同時間處理到同一則 Message 用
            'maxMessages' => 1,  // 每次從 PubSub 抓下來的 Message 數量
            'handler' => 'App\\Handler',  // 負責處理 Message 的 Handler，必需為實作 AlanzEvo\Abstracts\AbstractHandler 的 Class
            'throwable_handler' => 'App\\ThrowableHandler',  // 當處理者發生錯誤時，要處理錯誤的 Handler，必需為實作 AlanzEvo\Abstracts\AbstractThrowableHandler 的 Class
            'subscriptionId' => 'sample',  // Google PubSub 上的 subscription id
            'connection' => 'basic',  // 對應到 connections 下的連線設定
        ],
    ],
];
```
#### 範例
###### 範例一
- 設定一個新連線，名稱為 `my-connection`，授權證書從 `/path/to/key.json` 中取得
- 建立一個 listener，名稱為 `my-listener`，並指定連線為 `my-connection`
- 從 `my-subscription` Pull Messages
- 不指定處理錯誤的 Handler
- 一次 Pull 10 筆 Messages
- 在 30 秒內，同一筆 Message 不能被其他 Listener 處理

```PHP
return [
    'connections' => [
        '`my-connection' => [
            'keyFilePath' => '/path/to/key.json',
        ],
    ],

    'listeners' => [
        'my-listener' => [
            'messageLockSec' => 30,
            'max_messages' => 10,
            'handler' => 'App\\Handler',
            'subscriptionId' => 'my-subscription',
            'connection' => 'my-connection',
        ],
    ],
];
```

## Listener 指令
`php artisan listen-pubsub-message {listener} [--subscriptionId=] [--sleep=] [--once] [--ackBeforeHandling]`

#### 參數說明
- listener: 設定檔中的 Listener 名稱
- subscriptionId: 指定 subscriptionId 取代 listener 中指定的 subscriptionId
- sleep: 每個 Message 處理完後的緩衝時間，單位是 ms
- once: 完成一組 Messages 後，就釋放掉 Process
- ackBeforeHandling: 不論未來 Handler 執行成功與否，執行前就回覆 acknowledge 到 Google PubSub

#### 範例
###### 範例一
- 指定監聽 `my-listener`
- 每執行完一次 Handler 就暫停 `1000 ms` (預設值)
- Handler 執行完不釋放 process
- Handler 執行成功後才回 acknowledge 給 Google PubSub

`php artisan my-listener`

###### 範例二
- 指定監聽 `my-listener`
- 每執行完一次 Handler 就暫停 `500 ms`
- Handler 執行結束就釋放 process
- 在執行 Handler 之前，不論之後成功或失敗都回 acknowledge 給 Google PubSub

`php artisan my-listener --sleep=500 --once --ackBeforeHandling`

## Listener supervisor 指令
一次監聽 `pubsub.listeners` 中所有的 Listeners，當 Listener 發生中斷，會自動重啟
每一個 Listener 將會產生一個新 process

`php artisan pubsub-supervisor [--sleep=] [--once] [--ackBeforeHandling]`

#### 參數說明
- sleep: 每個 Message 處理完後的緩衝時間，單位是 ms
- once: 完成一組 Messages 後，就釋放掉 Process
- ackBeforeHandling: 不論未來 Handler 執行成功與否，執行前就回覆 acknowledge 到 Google PubSub

## Handler 和 ThrowableHandler 說明
Handler 和 ThrowableHandler 的用途，分別是用來處理 Message 和處理中的例外處理<br />

#### Handler 範例
```PHP
use AlanzEvo\GooglePubsub\Abstracts\AbstractHandler;

class MyHandler extends AbstractHandler
{
    public function handle()
    {
        $data = json_decode($this->message->data(), true);
        $info = $this->subscriptionInfo;
        $topic = $info['topic'] ?? '';
        // Do something
    }
}

```

#### ThrowableHandler 範例
```PHP
use AlanzEvo\GooglePubsub\Abstracts\AbstractThrowableHandler;

class MyThrowableHandler extends AbstractThrowableHandler
{
    public function handle()
    {
        $data = json_decode($this->message->data(), true);
        $info = $this->subscriptionInfo;
        $topic = $info['topic'] ?? '';
        $errorMessage = $this->throwable->getMessage();
        
        // Do something
    }
}

```

## 特別說明
- 當連線的授權證書，是從 `keyFilePath` 指定 json 檔，有可能因為 Publish 太頻繁而發生 `Failed to open stream: Too many open files`，可以試著改用 `keyFile`
- 建議安裝 gRPC extension，可以避免 Publisher 和 Subscriber 發生 `Failed to open stream: Too many open files` 的發生。安裝請參考: https://cloud.google.com/php/grpc