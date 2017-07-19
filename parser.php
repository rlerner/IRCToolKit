<?php
/**
 * IRCToolKit - The PHP IRC Solution.
 *
 * Provides a standard interface for creating IRC bots and clients, and handling server connections.
 *
 * PHP Version 5.4+
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
function parseIRC($line) {
	if (trim($line)=="") {
		return false;
	}

	if ($line=="version") {
		return "1.4.0";
	}

	// Initialize return array since not all return types use all variables.
	// You can't test with isset(), but it removes PHP_NOTICES for undefined variables.
	$ret = [
		"method"	=> ""
		,"body"		=> ""
		,"username"	=> "" 
		,"client"	=> ""
		,"network"	=> ""
		,"channel"	=> ""
		,"header"	=> ""
		,"switch"	=> ""
		,"applyusername" => ""
	];


	// Gather Delimiters
	$Name_Delim = strpos($line,"!");
	$At_Delim = strpos($line,"@");
	$FSpace_Delim = strpos($line," ");
	$SSpace_Delim = strpos($line," ",$FSpace_Delim+1);
	$TSpace_Delim = strpos($line," ",$SSpace_Delim+1);
	// Get header.
	$ret['header'] = trim(substr($line,1,strpos($line,":",1)));
	$Header_Delim = strlen($ret['header']);
	
	if (stristr($ret['header']," PRIVMSG ")!==false) {
		$ret['method'] = "PRIVMSG";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['channel'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}
	
	if (stristr($line," JOIN ")!==false && $ret['method']=='') {
		// The header doesn't have the ending :, so we'll just use strlen here.
		$ret['header'] = trim(substr($line,1,strlen($line)));
		$joinpos = stripos($ret['header']," JOIN ")+6;
		$ret['method'] = "JOIN";
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['channel'] = trim(substr($line,$joinpos,strlen($ret['header'])));
	}
		
	if (stristr($line," PART ")!==false && $ret['method']=='') {
		// The header doesn't have the ending :, so we'll just use strlen here.
		$ret['header'] = trim(substr($line,1,strlen($line)));
		$partpos = stripos($ret['header']," PART ")+6;
		$ret['method'] = "PART";
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['channel'] = trim(substr($line,$partpos,strlen($ret['header'])));
	}
		
	if (stristr($line," QUIT ")!==false && $ret['method']=='') {
		$ret['method'] = "QUIT";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
	}

	if (stristr($ret['header']," NOTICE ")!==false) {
		$ret['method'] = "NOTICE";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['channel'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}


	// TODO: Errors out on channel changes, like +t... But works for user mode changes
	if (stristr($line," MODE ")!==false && $ret['method']=='') {
		// The header doesn't have the ending :, so we'll just use strlen here.
		$ret['header'] = trim(substr($line,1,strlen($line)));
		$modepos = stripos($ret['header']," MODE ")+6;
		$ret['method'] = "MODE";
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$spaceaftermode = stripos($ret['header']," ",$modepos)+1;
		$spaceafterchannel = stripos($ret['header']," ",$spaceaftermode)+1;
		$spaceafterswitch = stripos($ret['header']," ",$spaceafterchannel)+1;
		
		$ret['channel'] = trim(substr($line,$modepos,$spaceaftermode-$modepos));
		$ret['switch'] = trim(substr($line,$spaceaftermode,$spaceafterchannel-$spaceaftermode));
		$ret['applyusername'] = trim(substr($line,$spaceafterchannel,strlen($ret['header'])));
	}
		
	if (stristr($ret['header']," NICK ")!==false) {
		$ret['method'] = "NICK";
		$ret['body'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['channel'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}
		
	if (stristr($ret['header'],"PING ")!==false) {
		$ret['method'] = "PING";
		$ret['server'] = trim(substr($line,strlen($ret['header']),strlen($line)));
	}

	if (stristr($ret['header']," INVITE ")!==false) {
		$ret['method'] = "INVITE";
		$ret['channel'] = trim(substr($line,strlen($ret['header'])+1,strlen($line)));
		$ret['username'] = trim(substr($line,1,$Name_Delim-1));
		$ret['client'] = substr($line,$Name_Delim+1,$At_Delim-($Name_Delim+1));
		$ret['network'] = substr($line,$At_Delim+1,$FSpace_Delim-($At_Delim+1));
		$ret['applyusername'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}
	
	if (stristr($ret['header']," KICK ")!==false) {
		$ret['method'] = "KICK";
		$ret['channel'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
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
		$ret['channel'] = substr($line,$SSpace_Delim+1,$TSpace_Delim-($SSpace_Delim+1));
	}
	
	// CTCP Processing
	if ($ret["method"]=="PRIVMSG" && ord(substr(trim($ret["body"]),0,1))==1) {
		echo "CTCP Command to be processed\n";

		$ctcpRequest = strtoupper(trim($ret["body"],chr(1)));

		switch ($ctcpRequest) {
			case "VERSION": 
				$ret["method"] = "CTCPVERSION";
			break;
			case "PING":
				$ret["method"] = "CTCPPING";
			break;
			case "TIME":
				$ret["method"] = "CTCPTIME";
			break;
			default:
				$ret["method"] = "CTCPUNKNOWN";
			break;
		}

	}

	if ($ret['method']=="") {
		$ret = null;
	}
	return $ret;
}
