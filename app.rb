require 'sinatra'   # gem 'sinatra'
require 'line/bot'  # gem 'line-bot-api'
require 'uri'
require_relative './Messages'
require_relative './UserDao'
require_relative './ChargeDao'

def client
  @client ||= Line::Bot::Client.new { |config|
    config.channel_secret = ENV["LINE_CHANNEL_SECRET"]
    config.channel_token = ENV["LINE_CHANNEL_TOKEN"]
  }
end

def getSquadId(event)
  case event['source']['type']
  when "group"
    return event['source']['groupId']
  when "room"
    return event['source']['roomId']
  when "user"
    return event['source']['userId']
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

  userId = event['source']['userId']
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
    userId = event['source']['userId']
    users = UserDao.new.get(getSquadId(event))
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
	
        elsif event.message["text"] == "bot"
          messageText = "[Help]\n"
          messageText << "〇ユーザーとして参加:\n  bot join\n"
          messageText << "〇参加者一覧:\n  bot user list\n"
          messageText << "〇支払追加:\n  bot add <金額> <立替先(人名 or 'all')> <コメント>\n"
          messageText << "〇支払一覧:\n  bot list\n"
          messageText << "〇支払清算:\n  bot calc\n"
          messageText << "〇支払削除:\n  bot delete <id>\n"
          messageText << "〇被立替額確認:\n  bot sum"
          message = {
            type: 'text',
            text: messageText
          }
          client.reply_message(event['replyToken'], message)

        elsif event.message["text"].start_with?("bot add")
          req = event.message["text"].split
          if !isAlreadyJoin(userId, users)
            message = {
              type: 'text',
              text: 'あなたはユーザーとして参加していません'
            }
            client.reply_message(event['replyToken'], message)
          elsif req.count != 5 || !req[2].integer?
            message = {
              type: 'text',
              text: "入力が正しくありません\n'bot'でヘルプが確認できます"
            }
            client.reply_message(event['replyToken'], message)
          elsif req[3] != 'all' && !users.isExistUserName(req[3]) && users.getUserNameWithForwardMatch(req[3]) == null
            message = {
              type: 'text',
              text: "指定されたユーザー #{req[3]} が存在しません\n現在の参加者は\n#{users.display}"
            }
            client.reply_message(event['replyToken'], message)
          else
            ownerName = users.getNameById(userId);
            value = req[2];
            target = users.getUserNameWithForwardMatch(req[3]);
            comment = req[4];
      
            ChargeDao.new.post(ownerName, value, target, comment, squadId)
            message = {
              type: 'text',
              text: "[登録完了]\n#{ownerName}さんが#{target}さんに #{value}円を立て替えました"
            }
            client.reply_message(event['replyToken'], message)
          end

        elsif event.message["text"] == "bot list"
          charges = ChargeDao.new.get(getSquadId(event));
          message = {
            type: 'text',
            text: "[たてかえ一覧]\n#{charges.display}"
          }
          client.reply_message(event['replyToken'], message)

        elsif event.message["text"] == 'bot calc'
          charges = ChargeDao.new.get(getSquadId(event));

          # 全員宛のみをマップに格納
          chargeMapOnlyToAll = Hash.new
          charges.chargeList.each do |charge|
            if charge.target == 'all'
              chargeMapOnlyToAll.merge!({charge.owner => charge.charge}){|k, v1, v2| v1 + v2}
            end
          end

          # 全員宛の一人当たり支払い額を算出
          totalCharge = 0;
          chargeMapOnlyToAll.each do |owner, charge|
            totalCharge += charge
          end
          chargeAverage = totalCharge / users.userList.count;

          # ユーザー分のマップを作成し、全員宛の一人当たり支払い額を格納
          calcCharge = Hash.new
          users.userList.each do |user|
            charge = chargeAverage - chargeMapOnlyToAll[user.name]
            calcCharge.store(user.name, charge)
          end

          # 各ユーザー宛の支払い金額を作成
          charges.chargeList.each do |charge|
            if charge.target != "all"
              calcCharge[charge.owner] -= charge.charge
              calcCharge[charge.target] += charge.charge
            end
          end

          messageText = "[精算]\nプラスの人が支払い、マイナスの人が受け取りをして下さい\n#{chargeMapToString(calcCharge)}"

          # //// 支払い例の計算 ////
		      # プラスマイナスを分ける
          plusValues = Hash.new
          minusValues = Hash.new
          calcCharge.each do |owner, value|
            if value < 0
              minusValues[owner] = -1 * value
            else
              plusValues[owner] = value;
            end
          end
          
          # 絶対値が大きい順にソート
          plusValues = plusValues.sort_by { |_, v| v }.reverse.to_h
          minusValues = minusValues.sort_by { |_, v| v }.reverse.to_h

          # プラスの大きい人からマイナスの大きい人へ支払いをしていく
          payExample = Hash.new
          minusValues.each do |reciever, rvalue|
            recieveMap = Hash.new
            restRecieveValue = rvalue

            plusValues.each do |payer, pvalue|
              if pvalue == 0
                next
              elsif restRecieveValue <= pvalue # reciverの受け取り完了
                recieveMap[payer] = restRecieveValue
                plusValues[payer] -= restRecieveValue
                break
              else
                recieveMap.store(payer, restPayValue)
                recieveMap[payer] = pvalue
                restRecieveValue -= pvalue
                plusValues[payer] = 0
              end
            end
            payExample[reciever] = recieveMap
          end

          messageText += "\n支払い例\n#{payExampleToString(payExample)}"
          message = {
            type: 'text',
            text: messageText
          }
          client.reply_message(event['replyToken'], message)
        
        elsif event.message["text"] == 'bot sum'
          # TODO

        elsif event.message["text"].start_with?("bot delete")
          # TODO

        elsif event.message["text"] == 'bot join'
          if users.isAlreadyJoinUser(userId)
            userName = users.getNameById(userId)
            message = 
            {
                type: 'text',
                text: "あなたはすでに #{userName} として参加しています"
            }
            client.reply_message(event['replyToken'], message)

          else
            userProfile = getMemberProfile(bot, userId, $squadType, $squadId);
            userName = userProfile['displayName'];
    
            if userName == null || $userName == ""
              message = 
              {
                  type: 'text',
                  text: "ライン名が取得できません。\nボットを友達に追加するか、次のコマンドで参加してください\nbot join <名前>"
              }
              client.reply_message(event['replyToken'], message)
    
            else
              $userDao = UserDao,new.post(userId, userName, squadId)
              users = UserDao.new.get(squadId) # post後のものを再取得
              message = 
              {
                  type: 'text',
                  text: "[ユーザ参加]\n #{userName} が参加しました\n現在の参加者は\n#{$users.display}"
              }
              client.reply_message(event['replyToken'], message)
            end
          end
    

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