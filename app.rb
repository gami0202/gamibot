require 'sinatra'   # gem 'sinatra'
require 'line/bot'  # gem 'line-bot-api'
require './messages'

def client
  @client ||= Line::Bot::Client.new { |config|
    config.channel_secret = ENV["LINE_CHANNEL_SECRET"]
    config.channel_token = ENV["LINE_CHANNEL_TOKEN"]
    # config.endpoint = "http://localhost:8080"
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
      case event.type
      when Line::Bot::Event::MessageType::Text
        if event.message["text"] == "あんこう"
            client.reply_message(event['replyToken'], carousel)
        end
      # when Line::Bot::Event::Postback
      #   message = {
      #     type: 'text',
      #     text: event.postback['data']
      #   }
      #   client.reply_message(event['replyToken'], message)
      end
    end
  end
end