require 'sinatra'   # gem 'sinatra'
require 'line/bot'  # gem 'line-bot-api'
require 'uri'
require_relative './Messages'
require_relative './UserDao'

def client
  @client ||= Line::Bot::Client.new { |config|
    config.channel_secret = ENV["LINE_CHANNEL_SECRET"]
    config.channel_token = ENV["LINE_CHANNEL_TOKEN"]
  }
end

def getSquadId(event)
  case event.source.type
  when "group"
    return event.source["groupId"]
  when "room"
    return event.source["roomId"]
  when "user"
    return event.source["userId"]
  end
end

def chargeMapToString(map)
	string = ""
  map.each do |k, v|
    string += "〇#{k}: #{v.to_s(:delimited)}円\n"
  end
	return $string
end

def payExampleToString(map)
	string = ""
  map.each do |reciever, payerMap|
    string += "〇受け取り: #{reciever}\n"
    payerMap.each do |payer, value|
      string += "  ・#{payer}: #{value.to_s(:delimited)}円\n"
    end
  end
	return string
end

def getMemberProfile
  # TODO
end

def chargeAdd(event, client)
  # message = {
  #   type: 'text',
  #   text: event['postback']['data']
  # }
  # client.reply_message(event['replyToken'], message)

  userId = event.source["userId"]
  users = UserDao.new.get(getSquadId(event))

  if !isAlreadyJoinUser(userId, users)
    client.reply_message(event['replyToken'], Messages.new.notJoinedUser) 
  else
    userNames = users.getNameArray

    if userNames.count > 29 # carousel は 3*10 までしか表示できない
      message = {
        type: 'text',
        text: "以下から対象を入力してください\n全員\n#{users.display}"
      }
      client.reply_message(event['replyToken'], message)              
    else
      # TODO
    end
  end
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
          client.reply_message(event['replyToken'], Messages.new.carousel)
          
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
        chargeAdd(event, client)
      end
    end
  end

  "OK"
end