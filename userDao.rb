require 'pg'
require_relative './User'
require_relative './UserList'

class UserDao
    def get_all
        userList = []
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("SELECT * FROM users") do |result|
            result.each do |row|
                userList.push User.new(row["user_id"], row["user_name"], row["squad_id"])
            end
        end
        return UserList.new(userList)
    end

    def get squadId
        userList = []
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("SELECT * FROM users WHERE squad_id='#{squadId}'") do |result|
            result.each do |row|
                userList.push User.new(row["user_id"], row["user_name"], row["squad_id"])
            end
        end
        return UserList.new(userList)
    end

    def post userId, userName, squadId
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("INSERT INTO users (user_id, user_name, squad_id) VALUES ('#{userId}', '#{userName}', '#{squadId}')")
    end

    def deleteAllBySquadId squadId
        conn = PG::Connection.new(ENV["DATABASE_URL"])
        conn.exec("DELETE FROM users WHERE squad_id='#{squadId}'")
    end
end
