require 'pg'

conn = PG::Connection.new(ENV["DATABASE_URL"])

def get
    conn.exec_params('SELECT * FROM users')
end

def get squadId
    conn.exec_params('SELECT * FROM users WHERE squad_id="$1"', [squadId])
end

