[![Deploy to Render](http://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy)

## ローカル検証方法

1. botの向き先をLINEシミュレーターへ変更

```
  @client ||= Line::Bot::Client.new { |config|
    config.channel_secret = ENV["LINE_CHANNEL_SECRET"]
    config.channel_token = ENV["LINE_CHANNEL_TOKEN"]
    config.endpoint = "http://localhost:8080" # この行を追加
  }
```

2. bot立ち上げ

```
bundle exec ruby app.rb
```

3. LINEシミュレータ立ち上げ

see: https://github.com/kenakamu/LINESimulator/blob/master/README_ja.md

```
npm install -g line-simulator
line-simulator
```
