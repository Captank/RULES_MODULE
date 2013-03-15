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
 *		help        = 'rules.txt'
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

		$sql = 'SELECT `signtime` FROM `rules_signs` WHERE `player`=? LIMIT 0,1';
		$time = $this->db->query($sql,$eventObj->sender);
		$time = (count($time)?$time[0]->signtime:0);
		
		$rules = $this->getUnsignedRules($accessLevel,$time);
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
		$accessLevel = $this->accessManager->getAccesslevelForCharacter($eventObj->sender);
		if(AccessManager::$ACCESS_LEVELS[$accessLevel]<7 || AccessManager::$ACCESS_LEVELS[$accessLevel]==0)
			return;

		$sql = 'SELECT `signtime` FROM `rules_signs` WHERE `player`=? LIMIT 0,1';
		$time = $this->db->query($sql,$eventObj->sender);
		$time = (count($time)?$time[0]->signtime:0);
		
		$rules = $this->getUnsignedRules($accessLevel,$time);
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
				// get online+channel list and run through as in else
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
	public function getRulesFor($accessLevel) {
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
	public function getUnsignedRules($accessLevel,$signTime) {
		if(!$this->validateAccessLevel($accessLevel)) {
			return Array();
		}
		$sql = "SELECT `id`,`title`,`text` FROM `rules` WHERE `$accessLevel`=1 AND `lastchange`>=? ORDER BY `id` ASC";
		return $this->db->query($sql,$signTime);
	}
	
	/**
	 * Validates a given accesslevel, if its superadmin it will be changed
	 * to admin
	 *
	 * @param string &$accessLevel - access level value that has to be validated
	 * @return bool - returns true if the given access level is valid
	 */
	private function validateAccessLevel(&$accessLevel){
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
	public function formatRule($rule,$full=false,$short=false){
		$msg = "<highlight>#{$rule->id} {$rule->title}<end>";
		if($long){
			$msg.=' '.date('c',$rule->lastchange)." by {$rule->lastchangeby}";
		}
		$msg.="<br>";
		if($long){
			$access = Array();
			if($rule->admin)
				$access[] = 'admins';
			if($rule->mod)
				$access[] = 'moderators';
			if($rule->guild)
				$access[] = 'guild members';
			if($rule->member)
				$access[] = 'members';
			if($rule->all)
				$access[] = 'all other';
			$msg.=implode(', ',$access).'<br>';
		}
		return $msg.($short?preg_replace("~^(.{50}[^\\s]*)\\s.*$~","$1 ...",$rule->text):$rule->text).'<br><br><pagebreak>';
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
