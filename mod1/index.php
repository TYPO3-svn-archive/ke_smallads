<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Christian Bülter (buelter@kennziffer.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module 'Smallads' for the 'ke_smallads' extension.
 *
 * @author	Christian Bülter <buelter@kennziffer.com>
 */

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:ke_smallads/mod1/locallang.php');
#include ('locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
//require_once (PATH_t3lib.'class.t3lib_TSparser.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

class tx_kesmallads_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $table='tx_kesmallads_smallads';

	/**
	 *
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		/*
		if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[]=PATH_t3lib.'class.t3lib_tcemain.php';
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			'function' => Array (
				'title' => $LANG->getLL('title'),
				'exportAll' => $LANG->getLL('exportAll'),
				'exportReviewed' => $LANG->getLL('exportReviewed'),
				'exportReviewedUserdata' => $LANG->getLL('exportReviewedUserdata'),
				'delete' => $LANG->getLL('delete'),
				'unHideReviewed' => $LANG->getLL('unHideReviewed'),
			)
		);
		parent::menuConfig();
	}

		// If you chose 'web' as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

        // get the page TSconfig
        $this->pageTSconfig=t3lib_BEfunc::GetPagesTSconfig($this->id);
        $this->modTSconfig=$this->pageTSconfig['ke_smallads.'];

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
				</script>
			';

				// set css
			$this->doc->inDocStyles='.linkbutton { display:block; padding:5px; margin:10px 0 10px 0; border:1px solid black; background:#D6CDB1; }';

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br>'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.path').': '.t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'], -50);

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);

			// Render content:
			$this->moduleContent();

			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 */
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 */
	function moduleContent()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		switch((string)$this->MOD_SETTINGS['function'])	{
			case 'title':
				$content=$LANG->getLL('explanation');
				$content.='';
				$this->content.=$this->doc->section($LANG->getLL('title'),$content,0,1);
			break;
			case 'exportAll':
				$content=$LANG->getLL('exportAll_explanation').
							'<hr style="margin-top: 5px; margin-bottom: 5px;"></hr>';
					// db query for all smallads marked as reviewed
				$enableFields=t3lib_BEfunc::BEenableFields($this->table);
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
												'*',			// SELECT columns
												$this->table,	// table(s)
												'pid='.$this->id.$enableFields,	// WHERE clause
												'',				// groupBy
												'',				// orderBy
												'');			// LIMIT value
				// create the content
				$exportcontent.='<table>';
				$i=0;
				while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$i++;
					$exportcontent.='<tr><td colspan="2" style="border-top:1px solid black;">'.$i.'</td></tr>';
					$exportcontent.='<tr><td valign="_top">';
					$exportcontent.='<p>';
					$exportcontent.='<b>'.$row['cat'].':</b> ';
					if (!$this->modTSconfig['skipTitle']) $exportcontent.=$row['title'].'. ';
					$exportcontent.=$row['content'];
                    // if configured, replace all non number chars with a pre-defined char
                    if ($this->modTSconfig['telephoneNumberDivider']) {
                        $row['phone']=ereg_replace('[^0-9| |^a-z|^A-Z]',$this->modTSconfig['telephoneNumberDivider'],$row['phone']);
                    }
					if ($row['phone']) $exportcontent.=$LANG->getLL('phone').$row['phone'];
					if ($row['phone'] && $row['email']) $exportcontent.=', ';
					if ($row['email']) $exportcontent.=$LANG->getLL('email').$row['email'];
					$exportcontent.='</p>';
					$exportcontent.='</td><td width="50%" valign="_top">';
					if ($row['fe_user_uid']){
						$userdata=t3lib_BEfunc::getRecord('fe_users',$row['fe_user_uid']);
						$exportcontent.=$row['user'].'<br />';
						$exportcontent.=$userdata['name'].'<br />';
						$exportcontent.=$userdata['address'].'<br />';
						$exportcontent.=$userdata['zip'].' ';
						$exportcontent.=$userdata['city'].'<br />';
						$exportcontent.=$userdata['telephone'].'<br />';
						$exportcontent.=$userdata['email'].'<br />';
						$exportcontent.='ID: '.$userdata['uid'].'<br />';
					}
					$exportcontent.=nl2br($row['comment']);
					$exportcontent.='</td></tr>';
				}
				$exportcontent.='</table>';
				$content.=$exportcontent;
				$this->content.=$this->doc->section($LANG->getLL('exportAll_title'),$content,0,1);
			break;
			case 'exportReviewed':
				$content=$LANG->getLL('exportReviewed_explanation').
							'<hr style="margin-top: 5px; margin-bottom: 5px;"></hr>';
				// db query for all smallads marked as reviewed
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
												'*',			// SELECT columns
												$this->table,	// table(s)
												'pid='.$this->id.' AND reviewed=1 AND hidden=1 AND deleted=0',	// WHERE clause
												'',				// groupBy
												'',				// orderBy
												'');			// LIMIT value
				// create the content
				while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$exportcontent.='<p>';
					$exportcontent.='<b>'.$row['cat'].':</b> ';
					if (!$this->modTSconfig['skipTitle']) $exportcontent.=$row['title'].'. ';
					$exportcontent.=$row['content'];
                    // if configured, replace all non number chars with a pre-defined char
                    if ($this->modTSconfig['telephoneNumberDivider']) {
                        $row['phone']=ereg_replace('[^0-9| |^a-z|^A-Z]',$this->modTSconfig['telephoneNumberDivider'],$row['phone']);
                    }
					if ($row['phone']) $exportcontent.=$LANG->getLL('phone').$row['phone'];
					if ($row['phone'] && $row['email']) $exportcontent.=', ';
					if ($row['email']) $exportcontent.=$LANG->getLL('email').$row['email'];
					if ($row['iscommercial']) {
						$exportcontent.=$LANG->getLL('appendToCommercialSmallads');
					} else {
						$exportcontent.=$LANG->getLL('appendToNonCommercialSmallads');
					}
					$exportcontent.='</p>';
				}
				$content.=$exportcontent;
				$this->content.=$this->doc->section($LANG->getLL('exportReviewed_title'),$content,0,1);
			break;
			case 'exportReviewedUserdata':
				$content=$LANG->getLL('exportReviewedUserdata_explanation').'<hr style="margin-top: 5px; margin-bottom: 5px;"></hr>';
				// db query for all smallads marked as reviewed
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
												'*',			// SELECT columns
												$this->table,	// table(s)
												'pid='.$this->id.' AND reviewed=1 AND hidden=1 AND deleted=0',	// WHERE clause
												'',				// groupBy
												'',				// orderBy
												'');			// LIMIT value
				// create the content
				$exportcontent.='<table>';
				$i=0;
				while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$i++;
					$exportcontent.='<tr><td colspan="2" style="border-top:1px solid black;">'.$i.'</td></tr>';
					$exportcontent.='<tr><td valign="_top">';
					$exportcontent.='<p>';
					$exportcontent.='<b>'.$row['cat'].':</b> ';
					if (!$this->modTSconfig['skipTitle']) $exportcontent.=$row['title'].'. ';
					$exportcontent.=$row['content'];
                    // if configured, replace all non number chars with a pre-defined char
                    if ($this->modTSconfig['telephoneNumberDivider']) {
                        $row['phone']=ereg_replace('[^0-9| |^a-z|^A-Z]',$this->modTSconfig['telephoneNumberDivider'],$row['phone']);
                    }
					if ($row['phone']) $exportcontent.=$LANG->getLL('phone').$row['phone'];
					if ($row['phone'] && $row['email']) $exportcontent.=', ';
					if ($row['email']) $exportcontent.=$LANG->getLL('email').$row['email'];
					$exportcontent.='</p>';
					$exportcontent.='</td><td width="50%" valign="_top">';
					if ($row['fe_user_uid']){
						$userdata=t3lib_BEfunc::getRecord('fe_users',$row['fe_user_uid']);
						$exportcontent.=$row['user'].'<br />';
						$exportcontent.=$userdata['name'].'<br />';
						$exportcontent.=$userdata['address'].'<br />';
						$exportcontent.=$userdata['zip'].' ';
						$exportcontent.=$userdata['city'].'<br />';
						$exportcontent.=$userdata['telephone'].'<br />';
						$exportcontent.=$userdata['email'].'<br />';
						$exportcontent.='ID: '.$userdata['uid'].'<br />';
					}
					$exportcontent.=nl2br($row['comment']);
					$exportcontent.='</td></tr>';
				}
				$exportcontent.='</table>';
				$content.=$exportcontent;
				$this->content.=$this->doc->section($LANG->getLL('exportReviewedUserdata_title'),$content,0,1);
			break;
			case 'delete':
				$content=$LANG->getLL('delete_explanation').'<hr style="margin-top: 5px; margin-bottom: 5px;"></hr>';
				if ($this->CMD && $this->CMD=='doDelete') {
					$updateFields=array();
					$updateFields['deleted']=1;
					$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table,'pid='.$this->id.' AND hidden=0 AND deleted=0',$updateFields);
					$content.='<p><b>'.$GLOBALS['TYPO3_DB']->sql_affected_rows().' Kleinanzeigen wurden gelöscht.</b></p>';
				} else {
					$content.='<a href="index.php?SET[function]=delete&CMD=doDelete&id='.$this->id.'" class="linkbutton">Kleinanzeigen jetzt löschen!</a>';
				}
				$this->content.=$this->doc->section($LANG->getLL('delete_title'),$content,0,1);
			break;
			case 'unHideReviewed':
				$content=$LANG->getLL('unHideReviewed_explanation').'<hr style="margin-top: 5px; margin-bottom: 5px;"></hr>';
				if ($this->CMD && $this->CMD=='doUnHide') {
					$updateFields=array();
					$updateFields['hidden']=0;
					$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table,'pid='.$this->id.' AND reviewed=1 AND hidden=1 AND deleted=0',$updateFields);
					// $content.=$GLOBALS['TYPO3_DB']->UPDATEquery($this->table,'pid='.$this->id.' AND reviewed=1 AND hidden=1 AND deleted=0',$updateFields);
					$content.='<p><b>'.$GLOBALS['TYPO3_DB']->sql_affected_rows().' Kleinanzeigen wurden sichtbar gemacht.</b></p>';
				} else {
					$content.='<a href="index.php?SET[function]=unHideReviewed&CMD=doUnHide&id='.$this->id.'" class="linkbutton">Kleinanzeigen jetzt sichtbar machen!</a>';
				}
				$this->content.=$this->doc->section($LANG->getLL('unHideReviewed_title'),$content,0,1);
			break;
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_smallads/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_smallads/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_kesmallads_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
