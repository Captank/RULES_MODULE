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

	/**
	 * @Setup
	 */
	public function setup() {
		$this->db->loadSQLFile($this->moduleName, "rules");
	}
	
	/**
	 * This command handler shows the rules
	 *
	 * @HandlesCommand("rules")
	 * @Matches("/^rules$/i")
	 */
	public function rulesCommand($message, $channel, $sender, $sendto, $args) {
		$rules = $this->getRulesFor($accessManager->getAccesslevelForCharacter($sender));
		if(count($rules)) {
			$msg = 'There are no rules set up for you.';
		}
		else {
			$msg = '';
			foreach($rules as $rule) {
				$msg.=$this->formatRule($rule);
			}
			$msg.='<center>'.$this->text->make_chatcmd('Accept the rules','/tell <myname> rules_sign').'</center>'
			$msg = $this->text->make_blob('Rules',$msg);
		}
		$sendto->reply($msg);
	}
	
	/**
	 * This command hanlder let someone sign the rules
	 *
	 * @HandlesCommand("rules_sign")
	 * @Matches("/^rules_sign$/i")
	 */
	public function signCommand($message, $channel, $sender, $sendto, $args) {
	
	}
	
	// $accessManager->getAccessLevelForCharacter($name)
	
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
		if(!$this->validateAccessLevel($accesslevel)) {
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
		if(!$this->validateAccessLevel($accesslevel)) {
			return Array();
		}
		$sql = 'SELECT `id`,`title`,`text` FROM `rules` WHERE `$accessLevel`=1 AND `lastchange`>? ORDER BY `id` ASC';
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
		if($accessLevel='superadmin'){
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
}

?>
