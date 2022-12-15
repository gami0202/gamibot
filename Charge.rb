class Charge
    attr_reader :id, :owner, :charge, :target, :comment

    def initialize(id, owner, charge, target, comment)
        @id = id
        @owner = owner
        @charge = charge
        @target = target
        @comment = comment
    end

    def display
        return "#{@id}, #{@owner}, #{@charge}, 全員, #{@comment}\n" if @target == "all"
        return "#{@id}, #{@owner}, #{@charge}, #{@target}, #{@comment}\n"
    end

end