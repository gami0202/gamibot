require 'sinatra'   # gem 'sinatra'
require 'line/bot'  # gem 'line-bot-api'
require 'uri'

def client
  @client ||= Line::Bot::Client.new { |config|
    config.channel_secret = ENV["LINE_CHANNEL_SECRET"]
    config.channel_token = ENV["LINE_CHANNEL_TOKEN"]
  }
end

def carousel
  {
      "type": "template",
      "altText": "this is a carousel template",
      "template": {
          "type": "carousel",
          "columns": [
              {
                  "title": "支払い操作",
                  "text": "支払い操作です！",
                  "actions": [
                      {
                          "type": "postback",
                          "label": "登録",
                          "data": "action=chargeAdd"
                      },
                      {
                          "type": "postback",
                          "label": "一覧",
                          "data": "action=botList"
                      },
                      {
                          "type": "postback",
                          "label": "清算",
                          "data": "action=botCalc"
                      }
                  ]
              },
              {
                  "title": "ユーザー操作",
                  "text": "ユーザー操作です！",
                  "actions": [
                      {
                          "type": "postback",
                          "label": "参加",
                          "data": "action=botJoin"
                      },
                      {
                          "type": "postback",
                          "label": "一覧",
                          "data": "action=botUserList"
                      },
                      {
                          "type": "postback",
                          "label": "ダミー",
                          "data": "これはダミーです"
                      }
                  ]
              },
              {
                  "title": "支払い操作(追加機能)",
                  "text": "支払い操作(追加機能)です！",
                  "actions": [
                      {
                          "type": "postback",
                          "label": "立替された合計額",
                          "data": "action=botSum"
                      },
                      {
                          "type": "postback",
                          "label": "! 支払い削除 !",
                          "data": "action=chargeDelete"
                      },
                      {
                          "type": "postback",
                          "label": "ダミー",
                          "data": "これはダミーです"
                      }
                  ]
              }
          ]
      }
  }
end

post '/callback' do
  body = request.body.read

  signature = request.env['HTTP_X_LINE_SIGNATURE']
  unless client.validate_signature(body, signature)
    halt 400, {'Content-Type' => 'text/plain'}, 'Bad Request'
  end

  events = client.parse_events_from(body)

  events.each do |event|
    case event
    when Line::Bot::Event::Message
      # case event.type
      # when Line::Bot::Event::MessageType::Text
        if event.message["text"] == "あんこう"
          client.reply_message(event['replyToken'], carousel)
          
        elsif event.message["text"] == "キャンセル"
            File.open("botStatus.txt", mode = "w"){|f|
              f.write("")
            }

            message = {
              type: 'text',
              text: "現在の処理をキャンセルしました"
            }
            client.reply_message(event['replyToken'], message)

        elsif event.message["text"] == "ヘルプ"
          message = {
            type: 'text',
            text: "[ヘルプ]\n https://github.com/gami0202/gamibot/blob/master/README.md"
          }
          client.reply_message(event['replyToken'], message)
	
        end
      # end
    when Line::Bot::Event::Postback
      postback_hash = Hash[URI::decode_www_form(event['postback']['data'])]
      if postback_hash["action"] == "chargeAdd"
        message = {
          type: 'text',
          text: event['postback']['data']
        }
        client.reply_message(event['replyToken'], message)
      end
    end
  end

  "OK"
end