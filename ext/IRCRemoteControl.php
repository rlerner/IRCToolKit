<?php
/**
 * IRCToolKit Extension - IRCRemoteControl
 *
 * Allows a user to control the IRC Bot through designated rooms.
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

class IRCRemoteControl {
	public $extensionName = 'IRCRemoteControl';
	
	public $ownerName;
	public $consoleOutput = true;


	//Extension setup allows population of extension parameters specified in the parent INI file.
	public function extension_setup($params) {
		$this->ownerName = trim($params['extension.ircremotecontrol.owner']);
	}
	
	public function event_message($params,$parent) {
		
		if ($params['username']==$this->ownerName && $params['room']==$parent->nickName) { //Message from ownername, in a query window (accept requests)
			if ($this->isCommand($params['body'],'say')) {
				$parent->sendText(trim(substr($params['body'],3)));
			}

			if ($this->isCommand($params['body'],'use')) {
				$parent->setRoomFocus(trim(substr($params['body'],3)));
			}

			if ($this->isCommand($params['body'],'join')) {
				$parent->joinRoom(trim(substr($params['body'],4)));
			}

			if ($this->isCommand($params['body'],'part')) {
				$parent->partRoom(trim(substr($params['body'],4)));
			}

			if ($this->isCommand($params['body'],'nick')) {
				$parent->changeNick(trim(substr($params['body'],4)));
			}
			
			if ($this->isCommand($params['body'],'raw')) {
				$parent->sendRaw(trim(substr($params['body'],3)));
			}
			
			
			if ($this->isCommand($params['body'],'op')) {
				$parent->modeGiveOp(trim(substr($params['body'],2)),$parent->currentRoom);
			}
			if ($this->isCommand($params['body'],'deop')) {
				$parent->modeTakeOp(trim(substr($params['body'],4)),$parent->currentRoom);
			}
			if ($this->isCommand($params['body'],'voice')) {
				$parent->modeGiveVoice(trim(substr($params['body'],5)),$parent->currentRoom);
			}
			if ($this->isCommand($params['body'],'devoice')) {
				$parent->modeTakeVoice(trim(substr($params['body'],7)),$parent->currentRoom);
			}
			if ($this->isCommand($params['body'],'kick')) {
				$parent->kickUser(trim(substr($params['body'],4)),$parent->currentRoom,null);
			}
			if ($this->isCommand($params['body'],'topic')) {
				$parent->setTopic(trim(substr($params['body'],5)),$parent->currentRoom);
			}
			
			if ($this->isCommand($params['body'],'verboseon')) {
				$parent->verbose = true;
				$parent->sendTextToRoom("Verbose mode turned on",$this->ownerName);
			}
			if ($this->isCommand($params['body'],'verboseoff')) {
				$parent->verbose = false;
				$parent->sendTextToRoom("Verbose mode turned off",$this->ownerName);
			}
			
			
			if ($this->isCommand($params['body'],'rooms')) {
				$parent->sendTextToRoom(implode(',',array_keys($parent->roomList)),$this->ownerName);
			}
			if ($this->isCommand($params['body'],'extensions')) {
				$parent->sendTextToRoom(implode(',',array_keys($parent->extensionList)),$this->ownerName);
			}
			
			if ($this->isCommand($params['body'],'log')) {
				
				//Example of how to connect extensions together.
				if (in_array('IRCLogger',$parent->extensionMap)) {
					$parent->extensionList[$parent->extensionMap['IRCLogger']]->event_message(['body'=>"Log Request: " . trim(substr($params['body'],3))],$parent);
				}
				else {
					$parent->sendTextToRoom("Cannot log. IRCLogger extension not found.",$this->ownerName);
				}
			}

			/*
			Store current room, send to queried user, resume focus room.

			if ($this->isCommand($params['body'],'query')) {
				$parent->sendText(trim(substr($params['body'],3)));
			}*/

		}

	}



	//A cleaner way to check for commands
	public function isCommand($body,$command_name) {
		$body = trim(strtoupper($body));
		$command_name = trim(strtoupper($command_name));
		return (substr($body,0,strlen($command_name))==$command_name);
	}


}
