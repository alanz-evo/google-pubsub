## 初始化設定
```php artisan vendor:publish --provider="AlanzEvo\GooglePubsub\Providers\GooglePubsubProvider" --tag="config"```

## Listener 指令
`php artisan listen-pubsub-message {listener} [--sleep=(ms seconds)] [--once] [--ackBeforeHandling]`

## 其他
- 建議安裝 gRPC extension，可以避免發生 `Failed to open stream: Too many open files in /path/to/vendor/google/cloud-core/src/RequestBuilder.php:159` 的發生。安裝請參考: https://cloud.google.com/php/grpc