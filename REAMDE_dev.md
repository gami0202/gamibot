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

## DB再構築方法

### render上の操作

1. render上でDB作成  
作成後、"External Database URL" をメモする。

2. renderのWebサービス側で、[Environment] -> [DATABASE_URL] に、"External Database URL"を貼り付けて更新

### ローカルマシン上の操作

1. TablePlus を開く

2. 右クリック -> [New] -> [Import from URL] に、"External Database URL" を貼り付けてDB接続

3. [Ctrl + e] でクエリエディターを開く

4. dbフォルダの内容を貼り付け -> [Run Current]

### 動作確認

1. TablePlus を閉じる（無料版だとセッション数の問題で、renderアプリがDB接続エラー発生する）

2. line で bot を使って、応答が返ってくる
