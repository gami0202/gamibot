require 'sinatra'   # gem 'sinatra'
require 'line/bot'  # gem 'line-bot-api'
require 'uri'
require_relative './Messages'
require_relative './UserDao'
require_relative './ChargeDao'

# for monitoring
get '/' do
  'Hello world!'
end

def client
  @client ||= Line::Bot::Client.new { |config|
    config.channel_secret = ENV["LINE_CHANNEL_SECRET"]
    config.channel_token = ENV["LINE_CHANNEL_TOKEN"]
  }
end

def reply_text(event, client, text)
  message = {
    type: 'text',
    text: text
  }
  client.reply_message(event['replyToken'], message)
end

def is_numeric?(text)
  true if Float(text) rescue false
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

def getSquadUserIds(event, client, squadId)
  case event['source']['type']
  when "group"
    # line認証済みアカウントまたはプレミアムアカウントのみで利用可能
    # 最大100人まで取得可能。それ以降も取得可能だが未実装。
    # see https://developers.line.biz/ja/reference/messaging-api/#get-group-member-user-ids-response 
    response = client.get_group_member_ids(squadId)
    result = JSON.parse(response.body)
    return result['memberIds']
  when "room"
    response = client.get_room_member_ids(squadId)
    result = JSON.parse(response.body)
    return result['memberIds']
  end
end

# 3桁ごとにカンマ区切り
def delimited(num)
  num.to_s.gsub(/(\d)(?=\d{3}+$)/, '\\1,')
end

def chargeMapToString(map)
	string = ""
  map.each do |k, v|
    string += "〇#{k}: #{delimited(v)}円\n"
  end
	return string
end

def payExampleToString(map)
	string = ""
  map.each do |reciever, payerMap|
    string += "〇受け取り: #{reciever}\n"
    payerMap.each do |payer, value|
      string += "  ・#{payer}: #{delimited(value)}円\n"
    end
  end
	return string
end

def getMemberProfile client, userId, squadType, squadId
  case squadType
  when "group"
    return client.get_group_member_profile(squadId, userId)
  when "room"
    return client.get_room_member_profile(squadId, userId)
  when "user"
    return client.get_profile(userId)
  end
end

def chargeAdd(event, client)
  # message = {
  #   type: 'text',
  #   text: event['postback']['data']
  # }
  # client.reply_message(event['replyToken'], message)

  userId = event['source']['userId']
  users = UserDao.new.get(getSquadId(event))

  if !users.isAlreadyJoin(userId)
    reply_text(event, client, 'あなたはユーザーとして参加していません')
  else
    userNames = users.getNameArray

    if userNames.count > 29 # carousel は 3*10 までしか表示できない
      reply_text(event, client, "以下から対象を入力してください\n全員\n#{users.display}")        
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
    squadType = event['source']['type']
    squadId = getSquadId(event)
    users = UserDao.new.get(squadId)
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
            reply_text(event, client, "現在の処理をキャンセルしました")

        elsif event.message["text"] == "ヘルプ"
          reply_text(event, client, "[ヘルプ]\n https://github.com/gami0202/gamibot/blob/master/README.md")
	
        elsif event.message["text"] == "bot"
          messageText = "[Help]\n"
          messageText << "〇ユーザーとして参加:\n  bot join\n"
          messageText << "〇参加者一覧:\n  bot user list\n"
          messageText << "〇支払追加:\n  bot add <金額> <立替先(人名 or 'all')> <コメント>\n"
          messageText << "〇支払一覧:\n  bot list\n"
          messageText << "〇支払清算:\n  bot calc\n"
          messageText << "〇支払削除:\n  bot delete <id>\n"
          messageText << "〇被立替額確認:\n  bot sum"
          reply_text(event, client, messageText)

        elsif event.message["text"].start_with?("bot add")
          req = event.message["text"].split
          if !users.isAlreadyJoin(userId)
            reply_text(event, client, 'あなたはユーザーとして参加していません')
          elsif req.count != 5 || !is_numeric?(req[2])
            reply_text(event, client, "入力が正しくありません\n'bot'でヘルプが確認できます")
          elsif req[3] != 'all' && !users.isExistUserName(req[3]) && users.getUserNameWithForwardMatch(req[3]).nil?
            reply_text(event, client, "指定されたユーザー #{req[3]} が存在しません\n現在の参加者は\n#{users.display}")
          else
            ownerName = users.getNameById(userId)
            value = req[2]
            target = users.getUserNameWithForwardMatch(req[3])
            comment = req[4]
      
            ChargeDao.new.post(ownerName, value, target, comment, squadId)
            reply_text(event, client, "[登録完了]\n#{ownerName}さんが#{target}さんに #{value}円を立て替えました")
          end

        elsif event.message["text"] == "bot list"
          charges = ChargeDao.new.get(squadId)
          reply_text(event, client, "[たてかえ一覧]\n#{charges.display}")

        elsif event.message["text"] == 'bot calc'
          charges = ChargeDao.new.get(squadId)

          # 全員宛のみをマップに格納
          chargeMapOnlyToAll = Hash.new
          charges.chargeList.each do |charge|
            if charge.target == 'all'
              chargeMapOnlyToAll.merge!({charge.owner => charge.charge}){|k, v1, v2| v1 + v2}
            end
          end

          # 全員宛の一人当たり支払い額を算出
          totalCharge = 0
          chargeMapOnlyToAll.each do |owner, charge|
            totalCharge += charge
          end
          chargeAverage = totalCharge / users.userList.count

          # ユーザー分のマップを作成し、全員宛の一人当たり支払い額を格納
          calcCharge = Hash.new
          users.userList.each do |user|
            if chargeMapOnlyToAll[user.userName].nil? # 全員宛の立替がない場合
              calcCharge.store(user.userName, chargeAverage) # chargeAverage=0のはず
            else
              calcCharge.store(user.userName, chargeAverage - chargeMapOnlyToAll[user.userName])
            end
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
              plusValues[owner] = value
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
          reply_text(event, client, messageText)
        
        elsif event.message["text"] == 'bot sum'
          charges = ChargeDao.new.get(squadId)

          # 全員宛の一人当たり支払い額を算出
          totalCharge = 0;
          charges.chargeList.each do |charge|
            totalCharge += charge.charge if charge.target == "all"
          end
          chargeAverage = totalCharge / users.userList.count

          # ユーザー分のマップを作成し、全員宛の一人当たり支払い額を格納
          calcCharge = Hash.new
          users.userList.each do |user|
            calcCharge.store(user.userName, chargeAverage)
          end

          # 各ユーザー宛の支払い額を加算
          charges.chargeList.each do |charge|
            calcCharge.merge!({charge.target => charge.charge}){|k, v1, v2| v1 + v2} if charge.target != "all"
          end

          reply_text(event, client, "[立替された合計額]\n#{chargeMapToString(calcCharge)}")

        elsif event.message["text"].start_with?("bot delete")
          req = event.message["text"].split
          if req.count != 3 || !is_numeric?(req[2])
            reply_text(event, client, "入力が正しくありません\n'bot'でヘルプが確認できます")
          else
            id = req[2]
            if ChargeDao.new.getById(id, squadId).nil?
              reply_text(event, client, "#{id}は削除できません")
            else
              chargeDao = ChargeDao.new.delete(id, squadId);
              reply_text(event, client, "#{id}を削除しました")
            end
          end

        elsif event.message["text"] == 'bot join'
          if users.isAlreadyJoin(userId)
            userName = users.getNameById(userId)
            reply_text(event, client, "あなたはすでに #{userName} として参加しています")

          else
            userProfile = getMemberProfile(client, userId, squadType, squadId)
            userProfile = JSON.parse(userProfile.read_body)
            userName = userProfile['displayName']
            UserDao.new.post(userId, userName, squadId)
            users = UserDao.new.get(squadId) # post後のものを再取得
            reply_text(event, client, "[ユーザ参加]\n #{userName} が参加しました\n現在の参加者は\n#{users.display}")
          end

        # line認証済みアカウントまたはプレミアムアカウントのみで利用可能
        elsif event.message["text"] == 'bot join all'
          if event['source']['type'] == "user"
            reply_text(event, client, "このコマンドはグループ/ルームのみ可能です。")
          else
            squadUserIds = getSquadUserIds(event, client, squadId)
            
            squadUserIds.each do |userId|
              next if users.isAlreadyJoin(userId)

              userProfile = getMemberProfile(client, userId, squadType, squadId)
              userProfile = JSON.parse(userProfile.read_body)
              userName = userProfile['displayName']
              userDao = UserDao.new.post(userId, userName, squadId)
            end

            users = UserDao.new.get(squadId) # post後のものを再取得
            reply_text(event, client, "[ユーザ参加]\nグループ/ルームメンバーが参加しました\n現在の参加者は\n#{users.display}")

          end
        elsif event.message["text"] == 'bot user list'
          reply_text(event, client, "[参加者一覧]\n現在の参加者は\n#{users.display}")

        # グループ/ルームに記録された情報をすべて削除して、退出する
        elsif event.message["text"] == 'bot leave'
          ChargeDao.new.deleteAllBySquadId(squadId)
          UserDao.new.deleteAllBySquadId(squadId)

          case squadType
          when "group"
            client.leave_group(squadId)
          when "room"
            client.leave_room(squadId)
          when "user"
            reply_text(event, client, "退出はグループ/ルームのみ可能です。")
          end

        elsif event.message["text"] == 'bot clear'
          ChargeDao.new.deleteAllBySquadId(squadId)
          UserDao.new.deleteAllBySquadId(squadId)
          reply_text(event, client, "記録された情報をすべて削除しました")


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