require 'sinatra'   # gem 'sinatra'
require 'line/bot'  # gem 'line-bot-api'

def client
  @client ||= Line::Bot::Client.new { |config|
    config.channel_secret = ENV["LINE_CHANNEL_SECRET"]
    config.channel_token = ENV["LINE_CHANNEL_TOKEN"]
  }
end

# def carousel
# {
#   "type": "template",   # 必須
#   "altText": "this is a carousel template",   # 必須
#   "template": {         # 必須
#       "type": "carousel",  # 必須
#       "columns": [      # 必須
#           # 1つ目のカラム
#           {
#             "title": "支払い操作",
#             "text": "支払い操作です！",   # 必須
#             "actions": [    # 必須
#                 # 1つ目のボタン(カラム1)
#                 {
#                     "type": "postback",
#                     "label": "登録",
#                     "data": "action=chargeAdd"
#                 },
#                 # 2つ目のボタン(カラム1)
#                 {
#                     "type": "postback",
#                     "label": "一覧",
#                     "data": "action=botList"
#                 },
#                 # 3つ目のボタン(カラム1)
#                 {
#                     "type": "postback",
#                     "label": "清算",
#                     "data": "action=botCalc"
#                 }
#             ]
#           }
#     #       # 2つ目のカラム
#     #       {
#     #         "thumbnailImageUrl": "https://example.com/bot/images/item2.jpg",
#     #         "imageBackgroundColor": "#000000",
#     #         "title": "this is menu",
#     #         "text": "description",   # 必須
#     #         "defaultAction": {
#     #             "type": "uri",
#     #             "label": "View detail",
#     #             "uri": "http://example.com/page/222"
#     #         },
#     #         "actions": [   # 必須
#     #             # 1つ目のボタン(カラム2)
#     #             {
#     #                 "type": "postback",
#     #                 "label": "Buy",
#     #                 "data": "action=buy&itemid=222"
#     #             },
#     #             # 2つ目のボタン(カラム2)
#     #             {
#     #                 "type": "postback",
#     #                 "label": "Add to cart",
#     #                 "data": "action=add&itemid=222"
#     #             },
#     #             # 3つ目のボタン(カラム2)
#     #             {
#     #                 "type": "uri",
#     #                 "label": "View detail",
#     #                 "uri": "http://example.com/page/222"
#     #             }
#     #         ]
#     #       }
#       ]
#   }
# }
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
        message = {
          type: 'text',
          text: event.message['text']
        #   text: carousel
        }
        client.reply_message(event['replyToken'], message)
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