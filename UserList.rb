require './User'

class UserList
    def initialize(userList)
        @userList = userList
    end

    def display
        ret = ""
        @userList.each do |user|
            ret += user.display
        end
        return ret
    end
end