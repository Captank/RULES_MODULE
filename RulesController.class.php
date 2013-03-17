<?php

/**
 * Author:
 *  - Captank (RK2)
 *
 * @Instance
 *
 *	@DefineCommand(
 *		command     = 'rules',
 *		accessLevel = 'all',
 *		description = 'shows the rules',
 *		help        = 'rules.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'rules_sign',
 *		accessLevel = 'all',
 *		description = 'sign the rules',
 *		help        = 'rules.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'signed',
 *		accessLevel = 'mod',
 *		description = 'checks if some one signed',
 *		help        = 'rulesadmin.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'rulesadmin',
 *		accessLevel = 'mod',
 *		description = 'administrating the rules',
 *		help        = 'rulesadmin.txt'
 *	)
 */
class RulesController {

	/**
	 * Name of the module.
	 * Set automatically by module loader.
	 */
	public $moduleName;

	/** @Inject */
	public $chatBot;
	
	/** @Inject */
	public $accessManager;
	
	/** @Inject */
	public $db;
	
	/** @Inject */
	public $text;
	
	private $levels = Array('admin','mod','guild','member','all');
	private $statesText = Array(-1=>' does not exist.',0=>' has <red>not signed<end>.',1=>' needs to <yellow>resign<end>.',2=>' has <green>signed<end>',3=>' has <green>no rules to sign<end>.');
	
	/**
	 * @Setup
	 */
	public function setup() {
		$this->db->loadSQLFile($this->moduleName, "rules");
	}
	
	/**
	 * @Event("logon")
	 * @Description("Spam rules if not signed")
	 */
	public function spamRulesIfNeeded($eventObj) {
		$accessLevel = $this->accessManager->getAccesslevelForCharacter($eventObj->sender);
		if(AccessManager::$ACCESS_LEVELS[$accessLevel]>=7 || AccessManager::$ACCESS_LEVELS[$accessLevel]==0) {
			return;
		}

		$rules = $this->getUnsignedRules($eventObj->sender);
		if(count($rules)>0) {
			$msg = '';
			foreach($rules as $rule) {
				$msg.=$this->formatRule($rule);
			}
			$msg.='<center>'.$this->text->make_chatcmd('Accept the rules','/tell <myname> rules_sign').'</center>';
			$msg = $this->text->make_blob('Rules',$msg);
			$this->chatBot->sendTell('You neeed to sign the '.$msg, $eventObj->sender);
		}
	}
	
	/**
	 * @Event("joinPriv")
	 * @Description("Spam rules if not signed")
	 */
	public function joinPrivateChannelMessageEvent($eventObj) {
		$rules = $this->getUnsignedRules($eventObj->sender);
		if(count($rules)>0) {
			$msg = '';
			foreach($rules as $rule) {
				$msg.=$this->formatRule($rule);
			}
			$msg.='<center>'.$this->text->make_chatcmd('Accept the rules','/tell <myname> rules_sign').'</center>';
			$msg = $this->text->make_blob('Rules',$msg);
			$this->chatBot->sendTell('You neeed to sign the '.$msg, $eventObj->sender);
		}
	}
	
	/**
	 * This command handler shows the rules
	 *
	 * @HandlesCommand("rules")
	 * @Matches("/^rules$/i")
	 */
	public function rulesCommand($message, $channel, $sender, $sendto, $args) {
		$rules = $this->getRulesFor($this->accessManager->getAccesslevelForCharacter($sender));
		if(count($rules)==0) {
			$msg = 'There are no rules set up for you.';
		}
		else {
			$msg = '';
			foreach($rules as $rule) {
				$msg.=$this->formatRule($rule);
			}
			$msg.='<center>'.$this->text->make_chatcmd('Accept the rules','/tell <myname> rules_sign').'</center>';
			$msg = $this->text->make_blob('Rules',$msg);
		}
		$sendto->reply($msg);
	}
	
	/**
	 * This command handler let someone sign the rules
	 *
	 * @HandlesCommand("rules_sign")
	 * @Matches("/^rules_sign$/i")
	 */
	public function signCommand($message, $channel, $sender, $sendto, $args) {
		$sql = 'REPLACE INTO `rules_signs` (`player`,`signtime`) VALUES (?,?)';
		$this->db->exec($sql,$sender,time());
		$sendto->reply("You signed the rules.");
	}
	
	/**
	 * This command handler shows the sign status of players
	 *
	 * @HandlesCommand("signed")
	 * @Matches("/^signed (all)$/i")
	 * @Matches("/^signed (.+)$/")
	 */
	 public function signedCommand($message, $channel, $sender, $sendto, $args) {
	 	$args[1] = preg_split("|\\s+|",$args[1],-1,PREG_SPLIT_NO_EMPTY);
		$msg = '';
	 	if(count($args[1])==1){
	 		if(strtolower($args[1][0])=='all') {
				$sql = 'SELECT `name` FROM `online` ORDER BY `name` ASC';
				$olist = $this->db->query($sql);
				
				foreach($olist as $player) {
		 			$state = $this->getSignedState($player->name);
		 			$msg .= $player->name.$this->statesText[$state].'<br>';
		 		}
		 		$msg = $this->text->make_blob('Sign states',$msg);
			}
			else {
				$state = $this->getSignedState($args[1][0]);
				$msg = $args[1][0].$this->statesText[$state];
			}
	 	}
	 	else {
	 		foreach($args[1] as $player) {
	 			$state = $this->getSignedState($player);
	 			$msg .= $player.$this->statesText[$state].'<br>';
	 		}
	 		$msg = $this->text->make_blob('Sign states',$msg);
	 	}
	 	$sendto->reply($msg);
	 }
	 
	/**
	 * This command handler shows details for the rules
	 *
	 * @HandlesCommand("rulesadmin")
	 * @Matches("/^rulesadmin show$/i")
	 * @Matches("/^rulesadmin show ([a-z]+)$/i")
	 * @Matches("/^rulesadmin show ([a-z]+) (long)$/i")
	 */
	public function rulesAdminShowCommand($message, $channel, $sender, $sendto, $args) {
		$rules = Array();
		$long = false;
		$count = count($args);
		switch($count) {
			case 1:
					$rules = $this->getRules(true);
				break;
			case 2:
					$args[1] = strtolower($args[1]);
					if($args[1]=='long') {
						$long = true;
						$rules = $this->getRules(true);
					}
					elseif(in_array($args[1],$this->levels)) {
						$rules = $this->getRulesFor($args[1],true);
					}
					else {
						echo "QQ\n";
						return;
					}
				break;
			case 3:
					$args[1] = strtolower($args[1]);
					if(in_array($args[1],$this->levels)) {
						$rules = $this->getRulesFor($args[1],true);
						$long = true;
					}
					else {
						echo "QQ\n";
						return;
					}
				break;
		}
		$msg = "";
		if(count($rules)==0){
			$msg = 'There are no rules set up for you.';
		}
		else {
			foreach($rules as $rule) {
				$msg.=$this->formatRule($rule,true,$long);
			}
			$msg=$this->text->make_blob("Rules info",$msg);
		}
		$sendto->reply($msg);
	}
	
	/**
	 * This command handler adds rules
	 *
	 * @HandlesCommand("rulesadmin")
	 * @Matches("/^rulesadmin add ([a-z]+) (.+)$/i")
	 * @Matches("/^rulesadmin add '([^']+)' (.+)$/i")
	 * @Matches('/^rulesadmin add "([^"]+)" (.+)$/i')
	 */
	public function rulesAdminAddCommand($message, $channel, $sender, $sendto, $args) {
		$sql = "INSERT INTO `rules` (`title`,`text`,`lastchange`,`lastchangeby`) VALUES (?,?,?,?);";
		$this->db->exec($sql,$args[1],$args[2],time(),$sender);
		$id = $this->db->lastInsertId();
		$msg = '<br><center>'.$this->text->make_chatcmd('edit groups',"/tell <myname> rulesadmin edit groups $id").'</center>';
		$msg = $this->text->make_blob("edit groups",$msg);
		$sendto->reply("The rule '<highlight>{$args[1]}<end>' was added as #$id. $msg");
	}
	
	/**
	 * This command handler is for editing rules
	 *
	 * @HandlesCommand("rulesadmin")
	 * @Matches("/^rulesadmin edit (\d+) (groups)$/i")
	 * @Matches("/^rulesadmin edit (\d+) (groups) (admin|mod|guild|member|all) (1|0)$/i")
	 * @Matches("/^rulesadmin edit (\d+) (title|text) (.+)$/i")
	 */
	public function rulesAdminEditCommand($message, $channel, $sender, $sendto, $args) {
		$msg = '';
		$sql = 'SELECT `title`,`admin`,`mod`,`guild`,`member`,`all` FROM `rules` WHERE `id`=? LIMIT 1';
		$rule = $this->db->query($sql,$args[1]);
		if(count($rule)==0) {
			$msg = "Rule #{$args[1]} does not exist.";
		}
		else {
			$args[2] = strtolower($args[2]);
			if($args[2]=='groups') {
						if(count($args)==3) {
							$msg = 'Title: <highlight>'.$rule[0]->title.'<end><br>';
							foreach($this->levels as $level) {
								$msg.="<tab><highlight>$level<end> is ";
								if($rule[0]->$level) {
									$msg.='<green>enabled<end> ';
								}
								else {
									$msg.='<red>disabled<end>';
								}
								$msg.='<br><tab><tab>'.$this->text->make_chatcmd('enable',"/tell <myname> rulesadmin edit {$args[1]} groups $level 1").'<tab>'.$this->text->make_chatcmd('disable',"/tell <myname> rulesadmin edit {$args[1]} groups $level 0").'<br><br>';
							}
							$msg = $this->text->make_blob("Rule #{$args[1]} groups",$msg);
						}
						else {
							$sql = "UPDATE `rules` SET `lastchange`=?,`lastchangeby`=?,`{$args[3]}`=? WHERE `id`=?";
							$this->db->exec($sql,time(),$sender,$args[4],$args[1]);
							$msg = "Rule #{$args[1]} updated.";
						}
					break;
			}
			else {
				$sql = "UPDATE `rules` SET `lastchange`=?,`lastchangeby`=?,`{$args[2]}`=? WHERE `id`=?";
				$this->db->exec($sql,time(),$sender,$args[3],$args[1]);
				$msg = "Rule #{$args[1]} updated.";
			}
		}
		$sendto->reply($msg);
	}
	
	/**
	 * This method returns all rules ids, titles and texts.
	 * If $full is given and and true, the rule object will also contain
	 * administrative data (change time, changed by who, groups)
	 *
	 * @param bool $full - defines if all data will be returned
	 * @return array returns an array of the the rules (db row object)
	 */
	public function getRules($full=false) {
		$sql = 'SELECT `id`,`title`,`text`'.($full?',`lastchange`,`lastchangeby`,`admin`,`mod`,`guild`,`member`,`all`':'').' FROM `rules` ORDER BY `id` ASC';
		return $this->db->query($sql);
	}
	
	/**
	 * This method returns all rules ids, titles and texts, which
	 * are meant for a given access level
	 *
	 * @param string $accessLevel - given group, can be superadmin, admin, mod, guild, member, all
	 * @return array returns an array of the the rules (db row object), if $accessLevel is invalid it returns an empty array
	 */
	public function getRulesFor($accessLevel,$full=false) {
		if(!$this->validateAccessLevel($accessLevel)) {
			return Array();
		}
		$sql = 'SELECT `id`,`title`,`text`'.($full?',`lastchange`,`lastchangeby`,`admin`,`mod`,`guild`,`member`,`all`':'')." FROM `rules` WHERE `$accessLevel`=1 ORDER BY `id` ASC";
		return $this->db->query($sql);
	}

	/**
	 * This method returns all rules ids, titles and texts, which
	 * are meant for a given access level and changed after a specific time
	 *
	 * @param string $accessLevel - given group, can be superadmin, admin, mod, guild, member, all
	 * @param int $signTime - the specific time
	 * @return array returns an array of the the rules (db row object), if $accessLevel is invalid it returns an empty array
	 */	
	public function getUnsignedRulesFor($accessLevel,$signTime) {
		if(!$this->validateAccessLevel($accessLevel)) {
			return Array();
		}
		$sql = "SELECT `id`,`title`,`text` FROM `rules` WHERE `$accessLevel`=1 AND `lastchange`>=? ORDER BY `id` ASC";
		return $this->db->query($sql,$signTime);
	}
	
	/**
	 * This method returns all rules ids, titles and texts, which
	 * are meant for aplayer
	 *
	 * @param string $player - name of the character
	 * @return array returns an array of the the rules (db row object), if $accessLevel is invalid it returns an empty array
	 */	
	public function getUnsignedRules($player) {
		$accessLevel = $this->accessManager->getAccesslevelForCharacter($player);

		$sql = 'SELECT `signtime` FROM `rules_signs` WHERE `player`=? LIMIT 0,1';
		$time = $this->db->query($sql,$player);
		$time = (count($time)?$time[0]->signtime:0);
		
		return $this->getUnsignedRulesFor($accessLevel,$time);
	}
	
	/**
	 * Validates a given accesslevel, if its superadmin it will be changed
	 * to admin
	 *
	 * @param string &$accessLevel - access level value that has to be validated
	 * @return bool - returns true if the given access level is valid
	 */
	private function validateAccessLevel(&$accessLevel) {
		$accessLevel = strtolower($accessLevel);
		if($accessLevel=='superadmin'){
			$accessLevel='admin';
			return true;
		}
		return in_array($accessLevel,$this->levels);
	}
	
	/**
	 * This method returns an AOML formated text, which represents the given rule
	 *
	 * @param object $rule - db row object of the rule
	 * @param bool $full - defines if administrative info will be displayed as well
	 * @param bool $short - if set, the rule text will be shortened
	 * @return string - the AOML formated text
	 */
	public function formatRule($rule,$full=false,$long=false) {
		$msg = "<highlight>#{$rule->id} {$rule->title}<end>";
		if($full){
			$msg.=' '.date('c',$rule->lastchange)." by {$rule->lastchangeby}";
		}
		$msg.="<br>";
		if($full){
			$access = Array();
			if($rule->admin)
				$access[] = 'admin';
			if($rule->mod)
				$access[] = 'mod';
			if($rule->guild)
				$access[] = 'guild';
			if($rule->member)
				$access[] = 'member';
			if($rule->all)
				$access[] = 'all';
			$msg.=implode(', ',$access).' '.$this->text->make_chatcmd('edit',"/tell <myname> rulesadmin edit groups {$rule->id}").'<br>';
		}
		return $msg.($long?$rule->text:preg_replace("~^(.{10}[^\\s]*)\\s.*$~","$1 ...",$rule->text)).'<br><br><pagebreak>';
	}
	
	/**
	 * This method returns the signed state of a given player
	 *
	 * @param string $player - character name   this is a reference parameter
	 * @return int - -1 if $player is not a player, 0 if he hasnt signed, 1 if he has to resign, 2 if is signed, 3 if he hasnt to sign any rules
	 */
	public function getSignedState(&$player) {
		$player = ucfirst(strtolower($player));
		if(!$this->chatBot->get_uid($player))
			return -1;
		$sql = 'SELECT `signtime` FROM `rules_signs` WHERE `player`=? LIMIT 0,1';
		$time = $this->db->query($sql,$player);
		$accessLevel = $this->accessManager->getAccesslevelForCharacter($player);
		$this->validateAccessLevel($accessLevel);
		$sql = "SELECT COUNT(*) AS COUNT FROM `rules` WHERE `$accessLevel`=1 AND `lastchange`>=?";
		$count = $this->db->query($sql,(count($time)?$time[0]->signtime:0));
		$count = intval($count[0]->COUNT);
		if($count==0 && count($time)==0) {// count = 0 && $time = null
			return 3;
		}
		elseif($count==0) {
			return 2;
		}
		elseif (count($time)==0) {
			return 0;
		}
		else {
			return 1;
		}
	}
}

?>
