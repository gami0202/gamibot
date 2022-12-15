require_relative './User'

class UserList
    attr_reader :userList

    def initialize(userList)
        @userList = userList
    end

    # line表示用文字列に変換
    def display
        ret = ""
        @userList.each do |user|
            ret += user.display
        end
        return ret
    end

    # ユーザーID一覧を配列で返却する
    def getIdArray
        userIds = []
        @userList.each do |user|
            userIds.push(user.userId)
        end
        return userIds
    end

    def getNameArray
        userNames = []
        @userList.each do |user|
            userNames.push(user.userName)
        end
        return userNames
    end

    # userNameが見つからなかったときは、userIdをそのまま返します。
    def getNameById(userId)
        @userList.each do |user|
            return user.userName if userId == user.userId
        end
        return userId
    end

    def isAlreadyJoin(userId)
        return getIdArray().include?(userId)
    end

    def isExistUserName(userName)
        return getNameArray().include?(userName)
    end

    # ユーザー一覧から、ユーザー名が前方一致したものを返却。
    # 最初にヒットしたものを返す。見つからなければnilを返す。
    # TODO 複数ヒットしたらエラーにできるように
    def getUserNameWithForwardMatch(userNamePart)
        return "all" if userNamePart == "all"

        @userList.each do |user|
            return user.userName if user.userName.start_with?(userNamePart)
        end
        return nil
    end

end