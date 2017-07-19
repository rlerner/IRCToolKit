# IRCToolKit
A simple, yet robust IRC Client Framework in PHP

Supports acting as a client for most typical IRC exchanges. CTCP commands are still hit-and-miss.

There are some TODOs, formatting issues, and what not, but the project is largely usable.


## Installing Requirements (PHP, git), And cloning into this repo
This command is for Ubuntu 17.04, though you will easily find other installation information with a quick search.
```bash
sudo apt-get update && sudo apt-get upgrade -y
sudo apt-get install php git -y
cd /opt
sudo mkdir IRCToolKit
sudo chown YOURUSER:YOURUSER /opt/IRCToolKit
git clone https://github.com/rlerner/IRCToolKit/
```


Everything below this line pertains to users of the Client Bootstrap (bootstrap.php) file... which most of you will be.
***

## Configuring IRCToolKit
To configure IRCToolKit, you must modify "ircbot.conf". Below, find the configuration options with an "x", these are the items you will need (or should) configure before starting the software initially.

| Set This Up | Configuration Option | Use |
|:---:|---|---|
| x | default_user_name | The bot's name as it appears in the IRC room |
| | default_real_name | The bot's "real name", generally the vendor string of the IRC software |
| x | irc_host_name | The server you're connecting to (chat.freenode.net, etc) |
| | irc_port_number | Most of them are 6667, so default should work |
| x | control_channel | This is the channel where the account will receive commands from |
| | console_show_others | Shows incoming IRC messages in the console |
| | anti_flood_delay | Causes an *x* delay between each sent message. |
| | character_delay | Causes an *x* * *char_count* delay between each sent message. (Emulates Typing) |
| x | extension_directory | Configures where your extensions are stored |
| x | extensions | Comma-Seperated List of Extensions to Load (use proper case here) |
| | verbose | Cranks up the console output to contain debug messages too |
| | ctcp_version | When a *CTCP VERSION* request comes in, it returns this string |
| | | -- Extension Configuration Would Be Below -- |





## Starting IRC Toolkit using the Client Bootstrap



### Footnotes
There are a few nomenclature issues within the code that I still need to iron out. I started the project in 2013 without initially intending to release it, however it is very usable and it would be a waste to keep it private, plus the modding/extension interface should be stable enough to build upon.

| Actual Term | You may see |
| ------------- | ------------- |
| Room | Channel |
| Name | Nick |
| Server | Client

Yea, that last one is a real doozy.
