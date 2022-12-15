class Charge
    def initialize(id, owner, charge, target, comment)
        @id = id
        @owner = owner
        @charge = charge
        @target = target
        @comment = comment
    end

    def display
        @userName + "\n"
    end

    public function display() {
        return "#{@id}, #{@owner}, #{@charge}, 全員, #{@comment}\n" if @target == "all"
        return "#{@id}, #{@owner}, #{@charge}, #{@target}, #{@comment}\n"
    }

end