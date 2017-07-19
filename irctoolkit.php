<?php
/**
 * IRCToolKit - The PHP IRC Solution.
 *
 * Provides a standard interface for creating IRC bots and clients, and handling server connections.
 *
 * PHP Version 5.4
 *
 * LICENSE: MIT
 *
 * @category
 * @package IRCToolKit
 * @author Robert Lerner
 * @copyright 2015-2017 Robert Lerner
 * @license https://opensource.org/licenses/MIT
 * @version 0.0.2
 * @link https://github.com/rlerner/IRCToolKit
 * @see http://semver.org/
 */

require_once 'parser.php';

class IRCToolkit{

		// Server Join Information
		public $serverName;
		public $portNumber;
		public $hostName;
		public $joinMode = 'Nope';
		public $initialRoom;

		public $currentRoom;
		public $roomList;
		public $nickName;
		public $realName;

		public $extensionDirectory;
		public $extensionList;
		public $extensionParams;
		public $extensionMap;

		public $verbose = false;
		public $safeMode = false;
		public $mute = false; //If true, disables sending text to server. TODO

		public $configINI;

		public $messageDelay = 5; //seconds
		public $messagePerCharDelay = 100000; //ms

		//Server stable and server wait state are probably useless, and should not be relied upon. Will probably be removed in the future.
		public $serverStable = false; //Goes true upon first PING
		public $serverWaitState = false; //If the server is idling (true = idling)

		public $toolkitVersion = "0.0.2";
		protected $socket;

		public function connect() {
			if ($this->hostName=='') {
				$this->hostName = gethostname();
			}
			if ($this->realName=='') {
				$this->realName = $this->nickName;
			}

			if (strlen($this->nickName) < 1 || strlen($this->nickName) > 9) {
				die('nickName property must be specified before calling ::connect(), and must be between 1 and 9 characters (inclusive).\n');
			}

			if (!$this->socket = fsockopen($this->serverName,$this->portNumber)) {
				die('connection failure');
				}

			//Sets the initial hostname and full user name (nickname in the place here).
			fputs($this->socket,"USER {$this->hostName} {$this->joinMode} unused :{$this->realName}\n");
			$this->changeNick($this->nickName);
			$this->joinRoom($this->initialRoom);
			$this->setRoomFocus($this->initialRoom);
		}

		// Auto-requires in extensions from ext/
		public function addExtensions($extensionList) {
			if ($this->safeMode) {
				return false;
			}

			// TODO: control extension config scope, e.g.
			// extension.IRCLol.whatever would only be available to IRCLol, and available as extensionParam "whatever"
			// TODO: update any existing extensions. Extensions can control the scope of their config with standard public/protected/private scopes.

			$extensions = explode(',',$extensionList);

			// Load extension parameters from INI
			foreach($this->configINI as $k=>$v) {
				if (substr($k,0,10)=='extension.') {
					$this->extensionParams[$k] = $v;
				}
			}

			// Initialize Extensions
			$extCount = 0;
			foreach ($extensions as $v) {
				require_once $this->extensionDirectory."$v.php";
				$this->extensionList[$extCount] = new $v;
				$this->extensionMap[$v] = $extCount;
				$this->consoleWrite("Extension '$v' loaded as #$extCount.");
				if (method_exists($this->extensionList[$extCount],'extension_setup')) {
					$this->extensionList[$extCount]->extension_setup($this->extensionParams);
				}
				$extCount++;
			}


		return true;
		}

		//Send text to the current room.
		public function sendText($text,$delay=true) {

			if ($delay) {
				sleep($this->messageDelay);
				for($i=0;$i<strlen($text);$i++)
					usleep($this->messagePerCharDelay);
			}

			$text = trim($text);
			if ($text==''){
				return false;
			}

			$this->consoleWrite("Bot Said: $text");
			RETURN fputs($this->socket, "PRIVMSG {$this->currentRoom} :".trim($text)."\n");
		}

		//Send raw String to the current room.
		public function sendRaw($text,$delay=true) {

			if ($delay) {
				sleep($this->messageDelay);
				for($i=0;$i<strlen($text);$i++)
					usleep($this->messagePerCharDelay);
			}

			$text = trim($text);
			if ($text==''){
				return false;
			}

			$this->consoleWrite("Bot Raw'd: $text");
			RETURN fputs($this->socket,"$text\n");
		}

		//Allows sending raw strings to a room that is not the current room.
		public function sendRawToRoom($text,$room,$delay=true) {
			$curRoom = $this->currentRoom;
			$this->setRoomFocus($room);
			$x = $this->sendRaw($text,$delay);
			$this->setRoomFocus($curRoom);
			return $x;
		}

		//Allows sending text to a room that is not the current room.
		public function sendTextToRoom($text,$room,$delay=true) {
			$curRoom = $this->currentRoom;
			$this->setRoomFocus($room);
			$x = $this->sendText($text,$delay);
			$this->setRoomFocus($curRoom);
			return $x;
		}

		public function sendNotice($text,$username,$delay=true) {
			if ($delay) {
				sleep($this->messageDelay);
				for($i=0;$i<strlen($text);$i++)
					usleep($this->messagePerCharDelay);
			}

			$text = trim($text);
			if ($text==''){
				return false;
			}

			$this->consoleWrite("Bot Noticed User $username, Said: $text");
			return fputs($this->socket, "NOTICE $username :".trim($text)."\n");
		}

		public function sendInvite($username,$room) {
			if (!$this->validRoomName($room)) {
				$this->consoleWrite("Cannot invite user $username to room $room. Room name invalid.");
				return false;
			}
			if (!$this->validNickName($username)) {
				$this->consoleWrite("Cannot invite user $username to room $room. Nick name invalid.");
				return false;
			}
			return fputs($this->socket, "INVITE $username :$room\n");
		}

		public function setTopic($topic,$room) {
			if (!$this->validRoomName($room)) {
				$this->consoleWrite("Cannot set topic '$topic' for room $room. Room name invalid.");
				return false;
			}
			return fputs($this->socket, "TOPIC $room :$topic\n");
		}

		// Room Focus is a room (you should already have joined it) to send some commands (sendText, for example) to.
		public function setRoomFocus($room) {
			// Bot doesn't need to be in room in some instances (e.g. channel mode -n)
			$this->currentRoom = trim($room);
			return true;
		}

		public function joinRoom($room,$password='') {
			if (trim($room)!='') {
				if (trim($password)!='') {
				$this->consoleWrite("JOIN: $room with password $password");
				$this->roomList[$room] = '';
				$room = '&'.str_replace('#','',$room);
				return fputs($this->socket,"JOIN $room $password\n");
				}
				else {
				$this->consoleWrite("JOIN: $room");
				$this->roomList[$room] = '';
				return fputs($this->socket,"JOIN $room\n");
				}
			}
		}

		public function partRoom($room) {
			if (trim($room)!='') {
				if (in_array($room,$this->roomList)) {
					$this->consoleWrite("PART: $room");
					unset($this->roomList[$room]);
					return fputs($this->socket,"PART $room\n");
				}
			}
			else {
				$this->consoleWrite("Not in room $room, cannot part.");
				return false;
			}
		}

		public function changeNick($nick) {
			if (trim($nick)!='') {
				$nick = trim($nick);
				if (strlen($nick)>9) {
					$this->consoleWrite("NICK: [FAIL] Cannot change to nick, over nine characters: $nick");
					return false;
				}

				$this->consoleWrite("NICK: $nick");
				$this->nickName = $nick;
				fputs($this->socket,'NICK '.$this->nickName."\n");
			return true;
			}
		return false;
		}

		public function modeGiveOp($nick,$room) {
			return $this->sendRaw("MODE $room +o $nick");
		}
		public function modeTakeOp($nick,$room) {
			return $this->sendRaw("MODE $room -o $nick");
		}
		public function modeGiveVoice($nick,$room) {
			return $this->sendRaw("MODE $room +v $nick");
		}
		public function modeTakeVoice($nick,$room) {
			return $this->sendRaw("MODE $room -v $nick");
		}
		public function kickUser($nick,$room,$reason='no reason') {
			return $this->sendRaw("KICK $room $nick :$reason");
		}

		// Helper Functions
		public function validRoomName($room) {
			$room = trim($room);
			if (!in_array(substr($room,1),['#','&','+','!'])) {
				return false;
			}
			if (strlen($room)<2 || strlen($room)>50) {
				return false;
			}

			if (stristr($room,' ')!==FALSE || stristr($room,chr(7))!==FALSE || stristr($room,',')!==FALSE || stristr($room,':')!==FALSE) {
				return false;
			}
			return true;
		}

		public function validNickName($nick) {
			$nick = trim($nick);
			return (strlen($nick)>0 && strlen($nick)<10);
		}

		// Client-Server Control Functions to Follow

		public function consoleWrite($text) {
			echo trim($text)."\n";

		}

		public function startServer() {
			while(1) {
				while($data = fgets($this->socket, 128)) {
					$this->serverWaitState = false;
					if ($this->verbose)
						echo "$data\n";

					$ex = explode(' ', $data);
					if($ex[0] == "PING") {

						if (!$this->serverStable) {
							$this->consoleWrite('Server is now: Stable');
							$this->serverStable = true;
						}

						fputs($this->socket, "PONG ".$ex[1]."\n");
					}

					//Parse incoming event
					$ret = parseIRC($data);

					if ($this->verbose && is_array($ret)) {
						foreach($ret as $k=>$v){
							$this->consoleWrite("$k = $v");
						}
					}

					$eventMap = [
						'PRIVMSG'	=>	'event_message'
						,'JOIN'		=>	'event_join'
						,'PART'		=>	'event_part'
						,'NOTICE'	=>	'event_notice'
						,'MODE'		=>	'event_mode'
						,'NICK'		=>	'event_nick'
						,'INVITE'	=>	'event_invite'
						,'TOPIC'	=>	'event_topic'
						,'PING'		=>	'event_ping'
						,'KICK'		=>	'event_kick'
						,'CTCPVERSION'=>'event_ctcp_version'
						,'CTCPPING'	=>	'event_ctcp_ping'
						,''			=>	'event_unknown'
					];

					// Do event hooks if a valid method is called
					if ($ret==null) {
						$ret['method'] = '';
					}

					$unhandled = true; // Instructs the server to handle the method if no extensions do.
					for ($i=0;$i<count($this->extensionList);$i++) {
						if (method_exists($this->extensionList[$i],$eventMap[$ret['method']])) {
							$unhandled = false;
							if ($eventMap[$ret['method']]=='event_unknown') {
								$this->extensionList[$i]->$eventMap[$ret['method']]($data,$this);
							}
							else {
								$this->extensionList[$i]->$eventMap[$ret['method']]($ret,$this);
							}
						}
					}

					if ($unhandled) {
						if ($ret['method']=='CTCPVERSION') {
							$this->sendNotice('SET VERSION LATER ETC',$ret['username']);
						}

					}
				}
			$this->serverWaitState = true;
			}
		}
	}
