require 'sinatra'   # gem 'sinatra'
require 'line/bot'  # gem 'line-bot-api'

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

# def carousel
#     {
#         "type": "template",
#         "altText": "this is a carousel template",
#         "template": {
#             "type": "carousel",
#             "columns": [
#             {
#                 "thumbnailImageUrl": "https://example.com/bot/images/item1.jpg",
#                 "imageBackgroundColor": "#FFFFFF",
#                 "title": "this is menu",
#                 "text": "description",
#                 "defaultAction": {
#                 "type": "uri",
#                 "label": "View detail",
#                 "uri": "http://example.com/page/123"
#                 },
#                 "actions": [
#                 {
#                     "type": "postback",
#                     "label": "Buy",
#                     "data": "action=buy&itemid=111"
#                 },
#                 {
#                     "type": "postback",
#                     "label": "Add to cart",
#                     "data": "action=add&itemid=111"
#                 },
#                 {
#                     "type": "uri",
#                     "label": "View detail",
#                     "uri": "http://example.com/page/111"
#                 }
#                 ]
#             },
#             {
#                 "thumbnailImageUrl": "https://example.com/bot/images/item2.jpg",
#                 "imageBackgroundColor": "#000000",
#                 "title": "this is menu",
#                 "text": "description",
#                 "defaultAction": {
#                 "type": "uri",
#                 "label": "View detail",
#                 "uri": "http://example.com/page/222"
#                 },
#                 "actions": [
#                 {
#                     "type": "postback",
#                     "label": "Buy",
#                     "data": "action=buy&itemid=222"
#                 },
#                 {
#                     "type": "postback",
#                     "label": "Add to cart",
#                     "data": "action=add&itemid=222"
#                 },
#                 {
#                     "type": "uri",
#                     "label": "View detail",
#                     "uri": "http://example.com/page/222"
#                 }
#                 ]
#             }
#             ],
#             "imageAspectRatio": "rectangle",
#             "imageSize": "cover"
#         }
#     }
# end

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
      case event.type
      when Line::Bot::Event::MessageType::Text
        # message = {
        #   type: 'text',
        #   text: event.message['text']
        # }
        # client.reply_message(event['replyToken'], message)
        client.reply_message(event['replyToken'], carousel)
      end
    #   when Line::Bot::Event::Postback
    #     message = {
    #       type: 'text',
    #       text: event.postback['action']
    #     }
    #     client.reply_message(event['replyToken'], message)
    #   end
    end
  end

  "OK"
end