#! /usr/bin/ruby
# $ sudo gem install sqlite3-ruby
#
# this script
#   http://d.hatena.ne.jp/nacookan/20120214/1329186227
# skype
#   http://qiita.com/dai___chi/items/ea4ff70fca99edabae29
#   http://htks.kylemilnes.com/2013/03/sqlite3-skype-chat-history-group-chat.html
# sqlite3
#   http://www.dbonline.jp/sqlite/table/index2.html
# nkf
#   http://docs.ruby-lang.org/ja/1.8.7/class/NKF.html
# to gmail
#   http://yusukezzz.net/blog/archives/1691
#   http://d.hatena.ne.jp/unageanu/20090429/1241001029
# to evernote
#   http://qiita.com/ota42y/items/6c73164cac6645758206

#dryrun = true
dryrun = false
#debug = true
debug = false

userid = "<your id>"
maindb = File.expand_path('~/Library/Application Support/Skype/' + userid + '/main.db')
last_filename = File.expand_path('~/.skype2mail.last')
max_days = 5
max_entries = 1000

mail_config = {
  :server => 'smtp.gmail.com',
  :port => 587,
  :from => '<your gmail>',
  :to => '<recipient>',
  :password => '<your gmail password>',
}

require 'rubygems'
require 'sqlite3'
require 'net/smtp'
require 'tlsmail'
require 'nkf'
require 'base64'

include SQLite3

# load last timestamp
from = 0
if File.exist?(last_filename)
  f = open(last_filename)
  from = f.read.to_i
  f.close
end

# pre query
to = nil
sql = "select timestamp from Messages where ? < timestamp order by timestamp limit 1"
db = Database.new(maindb)
db.execute(sql, from) do |row|
  to_time = Time.at(row[0])
  to = Time.local(to_time.year, to_time.month, to_time.day).to_i + (60 * 60 * 24 * max_days)
end
today = Time.local(Time.now.year, Time.now.month, Time.now.day).to_i
to = today if to == nil || today < to

# main query
sql = <<SQL
  select
      conv.displayname,
      con.fullname,
      con.skypename,
      msg.timestamp,
      msg.body_xml,
      conv.id,
      msg.author
  from
    Messages msg
      inner join Conversations conv on msg.convo_id = conv.id
      left outer join Contacts con on msg.author = con.skypename
  where
        ? < msg.timestamp
    and msg.timestamp < ?
  order by
    msg.timestamp asc
SQL

puts "searching... #{Time.at(from)} - #{Time.at(to)}"
last = from
data = {}
keys = []
db = Database.new(maindb)
db.execute(sql, from, to) do |row|
  chat = "#{row[5]}"
  topic = "#{row[0] || row[2] || row[5]}"
  name = "#{row[1] || row[2] || row[6]}"
  skypename = "#{row[2] || row[6]}"
  time = row[3]
  body = "#{row[4]}"
  date = Time.at(time).strftime("%Y-%m-%d")
  keys << date
  data[date] = {} unless data.has_key?(date)
  data[date][chat] = [] unless data[date].has_key?(chat)
  data[date][chat] << [] if data[date][chat].empty? || data[date][chat].last.length == max_entries
  data[date][chat].last << { :topic => topic, :name => name, :skypename => skypename, :time => Time.at(time), :body => body }
  last = time
end
db.close

# mail
keys.uniq.sort.each do |date|
  data[date].each do |chat, list|
    list.each_index do |index|
      item = list[index]
      topic = ""
      body = ""
      item.each do |msg|
        topic = msg[:topic] if topic.empty?
        body += <<-BODY
#{msg[:time].strftime("%H:%M")} - #{msg[:name]} (#{msg[:skypename]})
#{msg[:body]}

        BODY
      end
      subject = "[skype] #{topic} #{date}" + (2 <= list.length ? " (#{index + 1}/#{list.length})" : "")
      subject_encoded = NKF.nkf('-jMm0', subject)
      body_encoded = Base64.encode64(NKF.nkf('-wm0', body))

      mail = <<-MAIL
From: #{mail_config[:from]}
To: #{mail_config[:to]}
Subject: #{subject_encoded}
Date: #{Time::now.strftime("%a, %d %b %Y %X")}
Mime-Version: 1.0
Content-Type: text/plain; charaset=utf-8
Content-Transfer-Encoding: base64

#{body_encoded}
      MAIL

      if debug
        puts "chat: " + chat
        puts "topic: " + topic
        puts "body: " + body
      end
      unless dryrun
        Net::SMTP.enable_tls(OpenSSL::SSL::VERIFY_NONE)
        Net::SMTP.start(mail_config[:server], mail_config[:port], 'localhost.localdomain', mail_config[:from], mail_config[:password], "plain") do |smtp|
          smtp.send_mail mail, mail_config[:from], mail_config[:to]
        end
      end
    end
  end
end

# save last timestamp
f = File.open(last_filename, 'w')
f.puts last
f.close

