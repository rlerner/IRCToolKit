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
require_once 'irctoolkit.php';
set_time_limit(0);
echo "--------------------------------------------------------------\n";
echo "| IRCToolKit Client Bootstrap Script                         -\n";
echo "| Copyright (c) 2013-2017 Robert Lerner, All Rights Reserved -\n";
echo "--------------------------------------------------------------\n\n";
echo "Loading Configuration File [ircbot.conf]...";
if (!is_readable("ircbot.conf")) {
	die("Cannot read file.\n\n");
}
$_SERVER['ircbot']['config'] = parse_ini_file("ircbot.conf");
echo "done.\n";
echo "Start with --safemode to disable extensions.\n";

$safeMode = false;
if (is_array($argv)) {
	if (in_array('--safemode',$argv)) {
		$safeMode = true;
		echo "[SAFEMODE] Safemode is on. Extensions will not be loaded.\n";
	}
}

$bot = new IRCToolKit;
$bot->serverName = $_SERVER['ircbot']['config']['irc_host_name'];
$bot->portNumber = $_SERVER['ircbot']['config']['irc_port_number'];
$bot->nickName = $_SERVER['ircbot']['config']['default_user_name'];
$bot->realName = $_SERVER['ircbot']['config']['default_real_name'];
$bot->initialRoom = $_SERVER['ircbot']['config']['control_channel'];
$bot->extensionDirectory = $_SERVER['ircbot']['config']['extension_directory'];
//Connect must be prior to startServer();
$bot->connect();

$bot->verbose = $_SERVER['ircbot']['config']['verbose'];
$bot->messageDelay = $_SERVER['ircbot']['config']['anti_flood_delay'];
$bot->messagePerCharDelay = $_SERVER['ircbot']['config']['character_delay'];
$bot->safeMode = $safeMode;
$bot->configINI = $_SERVER['ircbot']['config'];
$bot->addExtensions($_SERVER['ircbot']['config']['extensions']);
$bot->startServer();

