<?php
function parseIRC($line) {
	if ($line=="version") {
		return "1.4.0";
	}

	if (trim($line)=="") {
		return false;
	}

	//Gather Delimiters
	$Name_Delim = strpos($line,"!");
	$At_Delim = strpos($line,"@");
	$FSpace_Delim = strpos($line," ");
	$SSpace_Delim = strpos($line," ",$FSpace_Delim+1);
	$TSpace_Delim = strpos($line," ",$SSpace_Delim+1);
	//Get header.
	$ret['header'] = trim(substr($line,1,strpos($line,":",1)));
	$Header_Delim = strlen($ret['header']);
	
	if (stristr($ret['header']," PRIVMSG ")!==false) {
		$ret['method'] = "PRIVMSG";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['room'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}
	
	//The header doesn't have the ending :, so we'll just use strlen here.
	if (stristr($line," JOIN ")!==false && $ret['method']=='') {
		$ret['header'] = trim(substr($line,1,strlen($line)));
		$joinpos = stripos($ret['header']," JOIN ")+6;
		$ret['method'] = "JOIN";
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['room'] = trim(substr($line,$joinpos,strlen($ret['header'])));
	}
		
	 //The header doesn't have the ending :, so we'll just use strlen here.
	if (stristr($line," PART ")!==false && $ret['method']=='') {
		$ret['header'] = trim(substr($line,1,strlen($line)));
		$partpos = stripos($ret['header']," PART ")+6;
		$ret['method'] = "PART";
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['room'] = trim(substr($line,$partpos,strlen($ret['header'])));
	}
		
	if (stristr($line," QUIT ")!==false && $ret['method']=='') {
		$ret['method'] = "QUIT";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
	}

	if (stristr($ret['header']," NOTICE ")!==false)	{
		$ret['method'] = "NOTICE";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['room'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}

	//TODO: Errors out on room changes, like +t... But works for user mode changes
	 //The header doesn't have the ending :, so we'll just use strlen here.
	if (stristr($line," MODE ")!==false && $ret['method']=='') {
		$ret['header'] = trim(substr($line,1,strlen($line)));
		$modepos = stripos($ret['header']," MODE ")+6;
		$ret['method'] = "MODE";
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$spaceaftermode = stripos($ret['header']," ",$modepos)+1;
		$spaceafterroom = stripos($ret['header']," ",$spaceaftermode)+1;
		$spaceafterswitch = stripos($ret['header']," ",$spaceafterroom)+1;
		
		$ret['room'] = trim(substr($line,$modepos,$spaceaftermode-$modepos));
		$ret['switch'] = trim(substr($line,$spaceaftermode,$spaceafterroom-$spaceaftermode));
		$ret['applyusername'] = trim(substr($line,$spaceafterroom,strlen($ret['header'])));
	}
		
	if (stristr($ret['header']," NICK ")!==false) {
		$ret['method'] = "NICK";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['room'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}
		
	if (stristr($ret['header'],"PING ")!==false) {
		$ret['method'] = "PING";
		$ret['server'] = trim(substr($line,strlen($ret['header']),strlen($line)));
	}

	if (stristr($ret['header']," INVITE ")!==false) {
		// It only makes functional sense that you can see only your invites.
		$ret['method'] = "INVITE";
		$ret['room'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['applyusername'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}
	
	if (stristr($ret['header']," KICK ")!==false) {
		$ret['method'] = "KICK";
		$ret['room'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['applyusername'] = trim(substr($line,$TSpace_Delim,$Header_Delim-$TSpace_Delim));
		$ret['reason'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));		
	}

	if (stristr($ret['header']," TOPIC ")!==false) {
		$ret['method'] = "TOPIC";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['room'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}
	
	// CTCP Support Starts Here
	if ($ret['method'] == "PRIVMSG" && substr($ret['body'],1)==chr(1)) {
		$ctcp = str_replace(chr(1),'',$ret['body']);
		$user = $ret['username'];
		$ret = '';
		
		if (strtoupper(trim($ctcp))=='VERSION') {
			$ret['method'] = 'CTCPVERSION';
			$ret['username'] = $user;
		}
		if (substr(strtoupper(trim($ctcp)),4)=='PING') {
			$ret['method'] = 'CTCPPING';
			$ret['username'] = $user;
			$ret['extension'] = str_ireplace('ping','',$ctcp);
		}
	}
	
	if ($ret['method']=='')
		$ret = null;

	return $ret;
	}
