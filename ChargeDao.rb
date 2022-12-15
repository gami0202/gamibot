require 'pg'
require './Charge'
require './ChargeList'

def get squadId
    chargeList = []
    conn = PG::Connection.new(ENV["DATABASE_URL"])
    conn.exec("SELECT * FROM charges WHERE squad_id='#{squadId}'") do |result|
        result.each do |row|
            chargeList.push User.new(row["user_id"], row["user_name"], row["squad_id"])
        end
    end
    return ChargeList.new(chargeList)
end

def post owner, value, target, comment, squadId
    conn = PG::Connection.new(ENV["DATABASE_URL"])
    conn.exec("INSERT INTO users (owner, value, target, comment, squad_id) VALUES ('#{owner}', '#{value}', '#{target}', '#{comment}', '#{squadId}')")
end

def delete id, squadId
    conn = PG::Connection.new(ENV["DATABASE_URL"])
    conn.exec("DELETE FROM charges WHERE id='#{id}' AND squad_id='#{squadId}'")
end

def deleteAllBySquadId squadId
    conn = PG::Connection.new(ENV["DATABASE_URL"])
    conn.exec("DELETE FROM charges WHERE squad_id='#{squadId}'")
end