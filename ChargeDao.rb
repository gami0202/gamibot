require 'pg'
require_relative './Charge'
require_relative './ChargeList'

class ChargeDao
    def get squadId
        chargeList = []
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("SELECT * FROM charges WHERE squad_id='#{squadId}'") do |result|
            result.each do |row|
                chargeList.push Charge.new(row["id"], row["owner"], row["value"], row["target"], row["comment"])
            end
        end
        return ChargeList.new(chargeList)
    end

    def getById id, squadId
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("SELECT * FROM charges WHERE id='#{id}' AND squad_id='#{squadId}'") do |result|
            result.each do |row|
                return Charge.new(row["id"], row["owner"], row["value"], row["target"], row["comment"])
            end
        end
        return nil
    end

    def post owner, value, target, comment, squadId
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("INSERT INTO charges (owner, value, target, comment, squad_id) VALUES ('#{owner}', '#{value}', '#{target}', '#{comment}', '#{squadId}')")
    end

    def delete id, squadId
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("DELETE FROM charges WHERE id='#{id}' AND squad_id='#{squadId}'")
    end

    def deleteAllBySquadId squadId
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("DELETE FROM charges WHERE squad_id='#{squadId}'")
    end
end
