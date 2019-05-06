# 概要

このボットは旅行時の立て替え清算を想定して作られています。（※旅行以外にも活用可）

駐車場代などの立替発生後、すぐにこのボットに金額を入力しておきます。  
これだけで、後で忘れずにかつ簡単に清算ができます。

## 背景

旅行時は、駐車場代やチケットのまとめ買いなど、誰かが全員分の支払いを立て替えるシーンが発生します。  
これらのレシートをすべて取っておいて、旅行最終日に清算するのは面倒です。回収忘れも起こりえます。  
そんな時にこのボットを使ってください。

# 基本的な使い方

1. ボットをlineグループに追加

1. ボットのトップページを表示  
`あんこう` とメッセージ送信。ここからボットの操作をします。

1. 全員がユーザーとして参加  
各自が `参加` をタップ。

1. 立替登録  
`登録` をタップ。その後ボットからの応答に従って入力。

1. 精算確認  
`清算` をタップ。

その他の機能は、実際にいじってみてください。

# トラブルシューティング

1. 立替登録などの操作を中断したい場合  
`キャンセル` とメッセージ送信。

1. バグなどで操作がうまくいかず、一度すべてリセットしたい場合  
`bot clear` とメッセージ送信。ただし、このボットに登録された立替情報・ユーザー情報がすべて削除されるので、注意してください。
