require './Charge'

class ChargeList
    def initialize(chargeList)
        @chargeList = chargeList
    end

    # line表示用文字列に変換
    def display
        ret = "id, 支払者, 金額, 立替先, コメント\n"
        @chargeList.each do |charge|
            ret += charge.display
        end
        return ret
    end
end