<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


namespace Frdlweb;
use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusConfig;
use ViaThinkSoft\OIDplus\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\OIDplusObject;
use ViaThinkSoft\OIDplus\OIDplusException; 

class OIDplusPhp extends OIDplusObject {
	private $className;

	public function __construct($className) {
		// No syntax checks
		$this->className = $className;
	}

	public static function parse($node_id) {
		@list($namespace, $className) = explode(':', $node_id, 2);
		if ($namespace !== self::ns()) return false;
		return new self($className);
	}

	public static function objectTypeTitle() {
		return _L('PHP classes');
	}

	public static function objectTypeTitleShort() {
		return _L('Class');
	}

	public static function ns() {
		return 'php';
	}

	public static function root() {
		return self::ns().':';
	}

	public function isRoot() {
		return $this->className === '';
	}

	public function nodeId($with_ns=true) {
		return $with_ns ? self::root().$this->className : $this->className;
	}

	public function addString($str) {
		if ($this->isRoot()) {
			return self::root() . $str;
		} else {
			return $this->nodeId() . '\\' . $str;
		}
	}

	public function crudShowId(OIDplusObject $parent) {
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	public function defaultTitle() {
		$ary = explode('\\', $this->className); // TODO: but if an arc contains "\", this does not work. better read from db?
		$ary = array_reverse($ary);
		return $ary[0];
	}

	public function isLeafNode() {
		return false;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusPhp::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->any()) {
				$content  .= _L('Please select an object in the tree view at the left to show its contents.');
			} else {
				$content  .= _L('Currently, no misc. objects are registered in the system.');
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage root objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Available objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			
			$title = $this->getTitle();
              $content .= '<h1><code>\\'.$this->nodeId(false).'</code></h1>';
			
			$content .= '<h3>'._L('Description').'</h3>%%DESC%%'; // TODO: add more meta information about the object type
			
			if( OIDplus::baseConfig()->getValue('PLUGIN_PHP_TYPE_LINK_TO_WEBFAN', false ) ){
			$content.='<p><strong><a webfan-php-class-link="'.$this->nodeId(false).'" href="https://webfan.de/install/?source='.urlencode($this->nodeId(false)).'" target="_blank">Soure Code</a></strong></p>';
			}
			
			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subordinate objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Subordinate objects').'</h2>';
				}
				$content .= '%%CRUD%%';
				
			}//not leaf
			
			
		}//not root
	}

	public function one_up() {
		$oid = $this->className;

		$p = strrpos($oid, '\\');
		if ($p === false) return self::parse($oid);
		if ($p == 0) return self::parse('\\');

		$oid_up = substr($oid, 0, $p);

		return self::parse(self::ns().':'.$oid_up);
	}

	public function distance($to) {
		if (!is_object($to)) $to = OIDplusObject::parse($to);
		if (!($to instanceof $this)) return false;

		$a = $to->className;
		$b = $this->className;

		if (substr($a,0,1) == '\\') $a = substr($a,1);
		if (substr($b,0,1) == '\\') $b = substr($b,1);

		$ary = explode('\\', $a);
		$bry = explode('\\', $b);

		$min_len = min(count($ary), count($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return false;
		}

		return count($ary) - count($bry);
	}

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
	//	return $this->ns().'_'.md5($this->nodeId(false));
		return $this->ns().str_replace(['\\', "/"], [\DIRECTORY_SEPARATOR, \DIRECTORY_SEPARATOR], $this->nodeId(false));
	}

	public static function treeIconFilename($mode) {
		return 'img/page_php.gif';
	}
}
