require_relative './User'

class UserList
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
            userNames.push(user.name)
        end
        return userNames
    end

    # userNameが見つからなかったときは、userIdをそのまま返します。
    def getNameById(userId)
        @userList.each do |user|
            return user.name if userId == user.id
        end
        return userId
    end

    def isAlreadyJoin(userId)
        return getIdArray().include?($userId)
    end

    def isExistUserName(userName)
        return getNameArray().include?($userName)
    end

    # ユーザー一覧から、ユーザー名が前方一致したものを返却。
    # 最初にヒットしたものを返す。見つからなければnullを返す。
    # TODO 複数ヒットしたらエラーにできるように
    def getUserNameWithForwardMatch(userNamePart)
        return "all" if userNamePart == "all"

        @userList.each do |user|
            return user.name if user.name.start_with?(userNamePart)
        end
        return null
    end

end