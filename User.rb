class User
    attr_reader :userId, :userName, :squadId

    def initialize(userId, userName, squadId)
        @userId = userId
        @userName = userName
        @squadId = squadId
    end

    def display
        @userName + "\n"
    end

end