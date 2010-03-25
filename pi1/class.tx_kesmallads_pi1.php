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
 * Plugin 'Smallads' for the 'ke_smallads' extension.
 *
 * @author	Christian Bülter <buelter@kennziffer.com>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php'); // needed for checking filenames when uploading pictures

class tx_kesmallads_pi1 extends tslib_pibase {
	var $prefixId='tx_kesmallads_pi1';						// Same as class name
	var $scriptRelPath='pi1/class.tx_kesmallads_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey='ke_smallads';								// The extension key.
	var $uploadFolder='uploads/tx_kesmallads/'; 			// hmm, maybe I should get the value from $TCA
	var $table='tx_kesmallads_smallads';					// Tablenames where the Smallads are stored
	var $mode_selector;										// Mode the plugin is running in. Set via Flexforms in the Backend.
	var $filevar='attachment'; 								// Typo3 forgives that name
	var $searchmode=0; 										// 0=> full list, 1=> short list, only linked headers; set in the plugin
	var $siteRelPath;										// Path to this extension from the main directory.
	var $formName = 'kesmalladsform';						// Name of the HTML-form for new smallads

	/**
	 * main function for ke_smallads extension
	 */
	function main($content,$conf)	{/*{{{*/
		$content .= '';
		$this->siteRelPath=t3lib_extMgm::siteRelPath($this->extKey); 	// get the path to this extension from main directory
		$this->pi_initPIflexform(); 									// Init and get the flexform data of the plugin
		$this->postVars=t3lib_div::_POST();								// get all the POST-Variables

		// get the pid list
		if (strstr($this->cObj->currentRecord,'tt_content'))	{
			$conf['pidList']=$this->cObj->data['pages'];
			$conf['recursive']=$this->cObj->data['recursive'];
		}

		// if no pid is set, use current page
		$conf['pidList'] = $conf['pidList'] ? $conf['pidList'] : $GLOBALS['TSFE']->id;

		$this->conf=$conf;				// Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();	// set the pi_vars
		$this->pi_loadLL();				// Loading the LOCAL_LANG values
		$this->pi_USER_INT_obj=1;		// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!

		// get the flexform "mode-selector" as configured in the backend
		$this->mode_selector=intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'mode_selector'));

		// get the uid of the target page (->redirect), if not set, use the current page
		$this->target_id=intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'target_id')) ? intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'target_id')) : $GLOBALS['TSFE']->id;

		// get the "no search results" text
		$this->no_results_text=$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'no_results_text') ? $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'no_results_text') : 'No results.';

		// check, if the static template is included. If not, stop and display an error message
		if (!is_array($this->conf['smalladForm.']['dataArray.'])) {
			return $this->pi_wrapInBaseClass('<div style="border:1px solid red; background:yellow; padding:1em;">'.
					$this->pi_getLL('no_static_template').
					'</div>');
		}

		// if the dropdown-mode-selector is beeing used:
		// check, if the first category has changed. If yes, clear the second
		// mode selector.
		// check, if the second category has changed. If yes, clear the third
		// mode selector.
		if (!$this->piVars['modeselector_cat_old']) {
			$this->piVars['modeselector_cat_old'] = $this->pi_getLL('list_mode_1');
		}
		if (!$this->piVars['modeselector_cat2_old']) {
			$this->piVars['modeselector_cat2_old'] = $this->pi_getLL('list_mode_1');
		}
		if (!$this->piVars['modeselector_cat3_old']) {
			$this->piVars['modeselector_cat3_old'] = $this->pi_getLL('list_mode_1');
		}
		if ($this->conf['modeSelectorType'] != 'buttons') {
			if ($this->piVars['modeselector_cat'] && $this->piVars['modeselector_cat'] != $this->piVars['modeselector_cat_old']) {
				unset($this->piVars['modeselector_cat2']);
				unset($this->piVars['modeselector_cat3']);
			}
			if ($this->piVars['modeselector_cat2'] && $this->piVars['modeselector_cat2'] != $this->piVars['modeselector_cat2_old']) {
				unset($this->piVars['modeselector_cat3']);
			}
			$this->piVars['modeselector_cat_old'] = $this->piVars['modeselector_cat'];
			$this->piVars['modeselector_cat2_old'] = $this->piVars['modeselector_cat2'];
		}

		switch($this->mode_selector)	{
			case 1:
				// show the form for new smallds or process a submitted form
				list($t)=explode(':',$this->cObj->currentRecord);
				$this->internal['currentTable']=$t;
				$this->internal['currentRow']=$this->cObj->data;
				if ($this->postVars['newad']) {
					$content .= $this->pi_wrapInBaseClass($this->processFormforNewAd());
				} else {
					$content .= $this->pi_wrapInBaseClass($this->showForm());
				}
				break;
			case 2:
				// List teaser
				$content .= $this->pi_wrapInBaseClass($this->listViewTeaser());
				break;
			case 4:
				// Let FE users edit/delete their own ads
				if ($this->postVars['edittype']=='update') {
					$content .= $this->pi_wrapInBaseClass($this->processFormforNewAd());
				}
				if (isset($this->piVars['deleteuid'])) {
					$content .= $this->pi_wrapInBaseClass($this->deleteAd($this->piVars['deleteuid']));
				}
				$content .= $this->pi_wrapInBaseClass($this->listOwnAds());
				break;
			case 3:
				// show short search results
				$this->searchmode=1;
				if (!$this->postVars['tx_kesmallads_pi1']['sword']) return '';
			default:
				// list view (default)
				$content .= $this->pi_wrapInBaseClass($this->listView());
			break;
		}

		return $content;
	}/*}}}*/

	function processFormforNewAd() {/*{{{*/
		$content.='';

		// make a local instance of tslib_cObj
		$lcObj=t3lib_div::makeInstance('tslib_cObj');

		// do some Checks
		if (empty($this->conf['pidList'])) return 'Plugin Error: no pidList selected';

		// collect errors in an array
		$errors = array();

		// Check, if fields have been filled in correctly
		if (intval($this->conf['ContentMaxChars']) > 0) {
			if (strlen($this->postVars['content']) > intval($this->conf['ContentMaxChars'])) {
				$errors[] = $lcObj->TEXT($this->conf['ContentTooManyCharsMessage.']);
			}
		}
		if (intval($this->conf['ContentAndTitleMaxChars']) > 0) {
			if (strlen($this->postVars['content']) + strlen($this->postVars['title']) > intval($this->conf['ContentAndTitleMaxChars'])) {
				$errors[] = $lcObj->TEXT($this->conf['ContentTooManyCharsMessage.']);
			}
		}

		// Special validation option: phone OR email have to be filled out
		if ($this->conf['phoneOrEmailHasToBeFilledOut'] && empty($this->postVars['phone']) && empty($this->postVars['email'])) {
			$errors[] = $this->pi_getLL('phone_or_email_has_to_be_filled_out');
		}

		// if email address has been filled out, validate it
		if ($this->postVars['email'] && !t3lib_div::validEmail($this->postVars['email'])) {
			$errors[] = $this->pi_getLL('email_address_not_valid');
		}

		// Check, if we want to do an update of an existing smallads entry
		// and if the user is allowed to
		if ($this->postVars['edittype']=='update' && $this->postVars['uid']) {
			$updateRecord = $this->pi_getRecord($this->table,$this->postVars['uid']);
			if (!$GLOBALS['TSFE']->fe_user->user['uid'] == $updateRecord['fe_user_uid']) unset($updateRecord);
			if (!is_array($updateRecord)) {
				$errors[] = $this->pi_getLL('no_allowed_to_update');
			}
		}

		// stop here if we have errors
		if (count($errors)) {
			return '<div class="error">' . implode('<br />', $errors) . '</div>';
		}

		// Insert the new Ad into the DB / Update the ad
		// store the category as cleartext, so it can be used in the backend, too
		$insertFields['cat']			= strip_tags($this->getCategoryName($this->postVars['cat'],$this->conf['smalladForm.']['dataArray.']['10.']['valueArray.']));
		$insertFields['cat2']			= strip_tags($this->getCategoryName($this->postVars['cat2'],$this->conf['smalladForm.']['dataArray.']['12.']['valueArray.']));
		$insertFields['cat3']			= strip_tags($this->postVars['cat3']);
		$insertFields['content']		= strip_tags($this->postVars['content']);
		$insertFields['phone']			= strip_tags($this->postVars['phone']);
		$insertFields['email']			= strip_tags($this->postVars['email']);
		$insertFields['displayemail']	= intval($this->postVars['displayemail']);
		$insertFields['title']			= strip_tags($this->postVars['title']);
		$insertFields['reviewed']		= intval($this->conf['markNewAdsAsReviewed']);
		$insertFields['iscommercial']	= intval($this->conf['markNewSmalladsAsCommercial']);

		// hide this ad from the beginning on?
		if (!is_array($updateRecord)) {
			$insertFields['hidden']		= intval($this->conf['hideNewAds']);
		} else {
			$insertFields['hidden']		= intval($this->conf['hideUpdatedAds']);
		}

		// set duration -> set endtime
		if (!empty($this->postVars['duration'])) {
			$insertFields['endtime'] = time() + 24 * 60 * 60 * (int)$this->postVars['duration'];
		}

		// 9 'user'-fields are checked, they are merged into 'user', comma separated
		$insertFields['user']='';
		for ($i=1; $i<10; $i++) {
			if (isset($this->postVars['user'.$i])) {
				if ($i>1) $insertFields['user'].=',';
				$insertFields['user'] .= $this->postVars['user'.$i];
			}
		}

		// if a frontend user is logged in, create the db relation
		if ($GLOBALS['TSFE']->fe_user->user) {
			$insertFields['fe_user_uid'] = $GLOBALS['TSFE']->fe_user->user['uid'];
		}

		// Handle File Upload
		if ($_FILES[$this->filevar]['name']) {
			$upload=$this->handleUpload();
			if ($upload[0]) {
				// success
				$insertFields['image']=basename($upload[1]);

				// delete the old image, if one exists
				if (is_array($updateRecord) && !empty($updateRecord['image'])) {
					@unlink($this->uploadFolder.$updateRecord['image']);
				}
			}
			$content.=$upload[2];
		}

		// Do the inserting / updating
		$fieldList='cat,cat2,cat3,content,user,image,phone,email,displayemail,title,reviewed,hidden,fe_user_uid,endtime,iscommercial';
		if (!is_array($updateRecord)) {
			$result = $this->cObj->DBgetInsert($this->table, $this->conf['pidList'] , $insertFields, $fieldList, 1);
		} else {
			$result = $this->cObj->DBgetUpdate($this->table, $updateRecord['uid'] , $insertFields, $fieldList, 1);
		}

		// Compile Userinfo for notify emails
		if ($GLOBALS['TSFE']->fe_user->user) {
			$fe_userinfo="\nUser:\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['username'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['username']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['company'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['company']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['name'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['name']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['address'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['address']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['zip'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['zip']." ";
			if (!empty($GLOBALS['TSFE']->fe_user->user['city'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['city']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['country'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['country']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['telephone'])) $fe_userinfo .= 'Tel: '.$GLOBALS['TSFE']->fe_user->user['telephone']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['telephone'])) $fe_userinfo .= 'Fax: '.$GLOBALS['TSFE']->fe_user->user['fax']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['email'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['email']."\n";
			if (!empty($GLOBALS['TSFE']->fe_user->user['www'])) $fe_userinfo .= $GLOBALS['TSFE']->fe_user->user['www']."\n";
		} else {
			$fe_userinfo='';
		}

		// Send emails
		if ($result) {

			// send an email to the admin, if configured so
			if ($this->conf['notifyEmailEditor'] && !is_array($updateRecord)) {
				$emaildata = $this->conf['notifyEmailEditor.'];
			}
			if ($this->conf['notifyEmailEditorOnUpdate'] && is_array($updateRecord)) {
				$emaildata = $this->conf['notifyEmailEditorOnUpdate.'];
			}
			if ($emaildata) {
				$emaildata['body']=str_replace("|","\n",$emaildata['body']);
				t3lib_div::plainMailEncoded($emaildata['toEmail'],
						$emaildata['subject'],
						sprintf($emaildata['body'],$GLOBALS['TSFE']->page['title'].', '.$insertFields['cat']."\n",html_entity_decode($insertFields['title'])."\n",html_entity_decode($insertFields['content'])."\n".$fe_userinfo),
						'From: '.$emaildata['fromName'].' <'.$emaildata['fromEmail'].'>'
						);
				unset($emaildata);
			}

			// send an email to the user, if configured so
			if ($this->conf['notifyEmailUser'] && !is_array($updateRecord)) {
				$emaildata = $this->conf['notifyEmailUser.'];
			}
			if ($this->conf['notifyEmailUserOnUpdate'] && is_array($updateRecord)) {
				$emaildata = $this->conf['notifyEmailUserOnUpdate.'];
			}
			if ($emaildata) {
				$emaildata['body']=str_replace("|","\n",$emaildata['body']);
				t3lib_div::plainMailEncoded($insertFields['email'],
						$emaildata['subject'],
						sprintf($emaildata['body'],$GLOBALS['TSFE']->page['title'].', '.$insertFields['cat']."\n",html_entity_decode($insertFields['title'])."\n",html_entity_decode($insertFields['content'])."\n"),
						'From: '.$emaildata['fromName'].' <'.$emaildata['fromEmail'].'>'
						);
			}

			if (!is_array($updateRecord)) {
				$content .= $lcObj->TEXT($this->conf['newadCreated.']);
			} else {
				$content .= $lcObj->TEXT($this->conf['newadUpdated.']);
			}

			unset($insertFields);

		} else {
			$content .= 'Error (DB Insert)';
		}

		return $content;
	}/*}}}*/

	/**
	 * handle the upload of the image
	 * partly based on extension "fileupload" by Mads Brunn (brunn@mail.dk)
	 */
	function handleUpload() {/*{{{*/
		$path = $this->uploadFolder;
		if (!is_dir($path)) return '<p>'.$this->pi_getLL('fileupload.error.nodir').'</p>';
		$content='';
		$success=true;

		// Dest. filename
		$filefuncs = new t3lib_basicFileFunctions();
		$uploadfile = $filefuncs->getUniqueName($filefuncs->cleanFileName($_FILES[$this->filevar]['name']), $this->uploadFolder);

		if($this->fileTooBig($_FILES[$this->filevar]['size'])){
			$content.='<p>'.$this->pi_getLL('fileupload.error.toobig').'</p>';
			$success=false;
		}

		if(!$this->mimeAllowed($_FILES[$this->filevar]['type'])){ //mimetype allowed?
			$content.='<p>'.$this->pi_getLL('fileupload.error.mimenotallowed').$_FILES[$this->filevar]['type'].'</p>';
			$success=false;
		}

		if(!$this->extAllowed($_FILES[$this->filevar]['name'])){ //extension allowed?
			$content.='<p>'.$this->pi_getLL('fileupload.error.extensionnotallowed').'</p>';
			$success=false;
		}

		if($success && move_uploaded_file($_FILES[$this->filevar]['tmp_name'], $uploadfile)) {//succes!
			$content='<p>'.$this->pi_getLL('fileupload.uploadsuccesfull').'</p>';
			if ($this->conf['uploadChmod']) {
				chmod($uploadfile,octdec($this->conf['uploadChmod']));
			}

 		} else {
			$content.=$this->handleError($_FILES[$this->filevar]['error']);
			$success=false;
		}

		return array($success,$uploadfile,$content);
	}/*}}}*/

	function handleError($error) {/*{{{*/
		$content='';
		switch ($error){
			case 0:
					break;
			case 1:
			case 2:
					$content.='<p>'.$this->pi_getLL('fileupload.error.toobig').'</p>';
					break;
			case 3:
					$content.='<p>'.$this->pi_getLL('fileupload.error.partial').'</p>';
					break;
			case 4:
					$content.='<p>'.$this->pi_getLL('fileupload.error.nofile').'</p>';
					break;
			default:
					$content.='<p>'.$this->pi_getLL('fileupload.error.unknown').'</p>';
		}
		return $content;
	}/*}}}*/

	function mimeAllowed($mime) {/*{{{*/
		if(!($this->conf['checkMime'])) return TRUE; 		//all mimetypes allowed
		$includelist = explode(",",$this->conf['mimeInclude']);
		$excludelist = explode(",",$this->conf['mimeExclude']);		//overrides includelist
		return (   (in_array($mime,$includelist) || in_array('*',$includelist))   &&   (!in_array($mime,$excludelist))  );
	}/*}}}*/

	function extAllowed($filename) {/*{{{*/
		if(!($this->conf['checkExt'])) return TRUE;			//all extensions allowed
		$includelist = explode(",",$this->conf['extInclude']);
		$excludelist = explode(",",$this->conf['extExclude']) 	;	//overrides includelist
		$extension='';
		if($extension=strstr($filename,'.')){
			$extension=strtolower(substr($extension, 1));
			return ((in_array($extension,$includelist) || in_array('*',$includelist)) && (!in_array($extension,$excludelist)));
		} else {
			return FALSE;
		}
	}/*}}}*/

	function fileTooBig($filesize) {/*{{{*/
		return $filesize > $this->conf['maxsize'];
	}/*}}}*/

	function deleteAd($deleteuid) {/*{{{*/
		$content = '';

		// Don't allow deletein for non-logged-in users
		if (!$GLOBALS['TSFE']->fe_user) return '<div class="error_not_allowed">'.$this->pi_getLL('no_user_logged_in_delete').'</div>';

		// get the record
		$record = $this->pi_getRecord($this->table,$deleteuid);

		// does this record exist?
		if (is_array($record)) {

			// Check, if the requested smallad belongs to this user
			if ($GLOBALS['TSFE']->fe_user->user['uid'] != $record['fe_user_uid']) return '<div class="error_not_allowed">'.$this->pi_getLL('not_your_smallad_delete').'</div>';

			// everything is OK, so delete the smallad (in fact, only set the "deleted" flag)
			$result = $this->cObj->DBgetDelete($this->table, $record['uid'], 1);

			if ($result) {
				$content .= '<div class="success">'.$this->pi_getLL('success_delete').'</div>';
			}

			// delete the image, if one exists
			if (is_array($record) && !empty($record['image'])) {
				@unlink($this->uploadFolder.$record['image']);
			}
		}

		return $content;
	}/*}}}*/

	/**
	 * listOwnAds
	 * Lists the ads of a logged in FE user and provides the possibility to
	 * edit/delete them.
	 *
	 * @access public
	 * @return void
	 */
	function listOwnAds() {/*{{{*/
		$content = '';

		// check, if a user is logged in
		if ($GLOBALS['TSFE']->fe_user->user) {
			if ($this->piVars['edituid']) {
				$content .= $this->showForm($this->piVars['edituid']);
			} else {
				$content .= $this->listView(1);
			}
		} else {
			$content .= '<div class="error_not_allowed">'.$this->pi_getLL('no_user_logged_in').'</div>';
		}

		return $content;
	}/*}}}*/

	/**
	 * shows the form for a new smallads can be configured via static TS in the
	 * Template-Setup (look into pi1/static/setup.txt)
	 *
	 * if $edituid is set, offer the smallad for editing (only for logged in
	 * users)
	 */
	function showForm($edituid=0)	{/*{{{*/
		$content = '';

		// get Form from static TS
		$lConf=$this->conf['smalladForm.'];

		// add the form name
		$lConf['formName'] = $this->formName;

		// Don't allow editing for non-logged-in users
		if (!$GLOBALS['TSFE']->fe_user && $edituid) return '<div class="error_not_allowed">'.$this->pi_getLL('no_user_logged_in').'</div>';

		// if a smallad is going to be edited, fill in all the necessary fields
		if ($edituid) {
			// used DB-fields:
			//$fieldList='cat,content,user,image,phone,email,displayemail,title,reviewed,hidden,fe_user_uid';

			// get the record
			$record = $this->pi_getRecord($this->table,$edituid);

			// Check, if the requested smallad belongs to this user
			if ($GLOBALS['TSFE']->fe_user->user['uid'] != $record['fe_user_uid']) return '<div class="error_not_allowed">'.$this->pi_getLL('not_your_smallad').'</div>';

			// set the edit-type (update/delete)
			$lConf['dataArray.']['4.']['type'] = 'edittype=hidden';
			$lConf['dataArray.']['4.']['value'] = 'update';

			// add the number of the smallad to the form
			$lConf['dataArray.']['5.']['type'] = 'uid=hidden';
			$lConf['dataArray.']['5.']['value'] = $record['uid'];

			// fill in the standard text fields
			$lConf['dataArray.']['20.']['value'] = $record['title'];
			$lConf['dataArray.']['30.']['value'] = $record['phone'];
			$lConf['dataArray.']['40.']['value'] = $record['email'];
			$lConf['dataArray.']['25.']['value'] = html_entity_decode($record['content']);
			if ($record['displayemail']) {
				$lConf['dataArray.']['50.']['value'] = 1;
			} else {
				$lConf['dataArray.']['50.']['value'] = 0;
			}

			// set the category
			foreach ($this->conf['smalladForm.']['dataArray.']['10.']['valueArray.'] as $catIndex => $cat ) {
				if ($cat['value']==$record['cat']) {
					$lConf['dataArray.']['10.']['valueArray.'][$catIndex]['selected'] = 1;
				}
			}

			// set the 9 user fields
			$userFieldValues=explode(',',$record['user']);
			$userFieldNo=0;

			// find the user fields
			foreach ($this->conf['smalladForm.']['dataArray.'] as $fieldIndex => $fieldConfig ) {
				// is it a user field?
				if (substr($fieldConfig['type'],0,strlen('user'))=='user') {
					// Which type is it?
					if (substr($fieldConfig['type'],strpos($fieldConfig['type'],'=')+1,strlen('input'))=='input') {
						$lConf['dataArray.'][$fieldIndex]['value'] = $userFieldValues[$userFieldNo];
					} else if (substr($fieldConfig['type'],strpos($fieldConfig['type'],'=')+1,strlen('select'))=='select') {
						foreach ($this->conf['smalladForm.']['dataArray.'][$fieldIndex]['valueArray.'] as $selectIndex => $select ) {
							if ($select['value']==$userFieldValues[$userFieldNo]) {
								$lConf['dataArray.'][$fieldIndex]['valueArray.'][$selectIndex]['selected'] = 1;
							}
						}
					}
					$userFieldNo++;
				}
			}

			// display the image
			if ($record['image']) {
				$lcObj=t3lib_div::makeInstance('tslib_cObj');
				$this->conf['smalladimage.']['file']=$this->uploadFolder.$record['image'];
				$this->conf['smalladimage.']['altText']=$record['title'];
				$content .= $lcObj->IMAGE($this->conf['smalladimage.']);
				unset($lcObj);
				$content .= '<div'.$this->pi_classParam('edit_image_note').'>'.$this->pi_getLL('edit_image_note').'</div>';
			}

		} else {
			// check, if a user is authenticated, if yes, preset some values
			// if you want' only authenticated users to be allowed to create smallads,
			// just set the permission restrictions to the plugin content object in the backend
			if ($GLOBALS['TSFE']->fe_user->user) {
				// set phone
				$lConf['dataArray.']['30.']['value'] = $GLOBALS['TSFE']->fe_user->user['telephone'];
				// set email
				$lConf['dataArray.']['40.']['value'] = $GLOBALS['TSFE']->fe_user->user['email'];
			}
		}

		// set the redirect page
		$lConf['redirect']=$this->target_id;

		// render the form
		$formContent .= $this->cObj->FORM($lConf);

		// if defined in typoscript, check for maximum characters
		// in the content field (using javascript)
		if ($this->conf['ContentMaxChars'] && $this->conf['checkCharactersLeftWhileTyping']) {
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId . '_js0'] = '<script type="text/javascript">'
. 'function checkContentLength() {
	maxChars = ' . $this->conf['ContentMaxChars'] . '
	if (document.'. $this->formName . '.content.value.length > maxChars) {
		document.'. $this->formName . '.content.value = document.'. $this->formName . '.content.value.substring(0, maxChars);
	} else {
		document.all.kesmallads_maxchars.innerHTML = "' .
		$this->pi_getLL('maxchars_begin') .
		'" + (maxChars - document.'. $this->formName . '.content.value.length) + "' .
		$this->pi_getLL('maxchars_end') . '";
	}
}'
			. '</script>';

			//$formContent = str_replace('textarea name="content"', 'textarea name="content" onKeyUp="checkContentLength();"', $formContent);
			$formContent = str_replace('</textarea>', '</textarea><p id="kesmallads_maxchars"></p>', $formContent);
			//debug($formContent);
		}

		// form content to the main content
		$content .= $formContent;

		// add some javascript to the header for the dynamic third category
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId . '_js1'] = $this->renderJavascriptFunctionsForDynamicForm();

		// add inline javascript
		$content .= $this->renderInlineJavascriptForDynamicForm();

		return $content;
	}/*}}}*/

	/**
	 * renderJavascriptFunctionsForDynamicForm
	 *
	 * renders javascript functions for the page header
	 *
	 * @access public
	 * @return string
	 */
	function renderJavascriptFunctionsForDynamicForm() {/*{{{*/
		$lcObj=t3lib_div::makeInstance('tslib_cObj');

		$content = '';

		// configuration for the second category
		$cat2_configlist = $this->conf['smalladForm.']['dataArray.']['12.']['valueArray.'];

		// configuration for the subcategory of the second category
		$cat3_configlist = $this->conf['cat3.'];

		if (is_array($cat2_configlist) && count($cat2_configlist) && is_array($cat3_configlist) && count($cat3_configlist)) {
			$content .= '<script type="text/javascript">' . "\n";
			$content .= '/* <![CDATA[ */' . "\n";

			// Label and Value List
			$content .= 'var subCategoryList=new Array();' . "\n";

			// One array function for each category
			$counter = 0;
			foreach ($cat2_configlist as $key => $cat2_config) {

				$content .= 'subCategoryList[' . $counter . ']=[';

				// render the subcategories from the list for this category
				$counter2 = 0;
				foreach ($cat3_configlist as $key => $cat3_config) {
					// use only the rows with the configuration (the ones with the "." in the key)
					if ((strstr($key, '.')) && $cat3_config['belongsTo'] == $cat2_config['value']) {
						if ($counter2 > 0) {
							$content .= ',';
						}
						$content .= '"' . $lcObj->TEXT($cat3_config) . '|' . $lcObj->TEXT($cat3_config) . '"';
						$counter2++;
					}
				}

				$content .= '];' . "\n";
				$counter++;

			}

			// the main function
			$content .= 'function renderSubCat() {' . "\n";

			// empty the select box "third category"
			$content .= 'document.' . $this->formName . '.cat3.options.length=0;' . "\n";

			// repopulate the select box
			$content .= 'selectedcat = document.' . $this->formName . '.cat2.selectedIndex;
for (i=0; i<subCategoryList[selectedcat].length; i++) {
	document.' . $this->formName . '.cat3.options[document.' . $this->formName . '.cat3.options.length]=new Option(subCategoryList[selectedcat][i].split("|")[0], subCategoryList[selectedcat][i].split("|")[1])
}';
			$content .= '}';

			$content .= '/* ]]> */' . "\n";
			$content .= '</script>' . "\n";
		}

		return $content;
	}/*}}}*/

	/**
	 * renderInlineJavascriptForDynamicForm
	 *
	 * renders on page javascript
	 *
	 * @access public
	 * @return void
	 */
	function renderInlineJavascriptForDynamicForm() {/*{{{*/
		$content = '<script type="text/javascript">' . "\n";
		$content .= '/* <![CDATA[ */' . "\n";

		// call the function once to initialize the box
		$content .= 'renderSubCat();' . "\n";

		// add event-listener the select box
		$content .= 'document.' . $this->formName . '.cat2.onchange = renderSubCat;' . "\n";

		// add even-listener to content field
		if ($this->conf['ContentMaxChars'] && $this->conf['checkCharactersLeftWhileTyping']) {
			$content .= 'document.' . $this->formName . '.content.onkeyup = checkContentLength;' . "\n";

			// call the function to initialize the note about how many characters are left
			$content .= 'checkContentLength();' . "\n";
		}

		$content .= '/* ]]> */' . "\n";
		$content .= '</script>' . "\n";

		return $content;
	}/*}}}*/

	/**
	 * listViewTeaser shows the only a few titles of the newest smallads and a link to the smallads main page
	 */
	function listViewTeaser() {/*{{{*/
		$content='';

		// make a local instance of tslib_cObj
		$lcObj=t3lib_div::makeInstance('tslib_cObj');

		// get values from static TS
		$lConf=$this->conf['teaserView.'];


		// enable backward compatibility for "teaserNum"
		if (!empty($this->conf['teaserNum']) && empty($lConf['results_at_a_time'])) $lConf['results_at_a_time']=$this->conf['teaserNum'];

		$this->internal['results_at_a_time']=t3lib_div::intInRange(intval($lConf['results_at_a_time']),0,1000,3);		// Number of results to show in a listing.
		$this->internal['maxPages']=t3lib_div::intInRange($lConf['maxPages'],0,1000,5);		// The maximum number of "pages" in the browse-box: "Page 1", "Page 2", etc.
		$this->internal['searchFieldList']='content,phone,email,title,cat';

		// will only work with MySQL
		$addWhere='ORDER BY RAND()';

		// Make listing query, pass query to SQL database:
		$res=$this->pi_exec_query($this->table,0,$addWhere);

		// create the output content
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$content.='<div'.$this->pi_classParam('teaser_row').'>';
			$content.='<span'.$this->pi_classParam('teaser_cat').'>'.htmlspecialchars($row['cat']).':</span> ';
			$content.='<span'.$this->pi_classParam('teaser_title').'>'.htmlspecialchars($row['title']).'</span>';
			$content.='</div>';
		}

		// ad a link to the smallads main page
		$this->conf['linktextTeaserShowAll.']['typolink.']['ATagParams']=$this->pi_classParam('teaser_link');
		$this->conf['linktextTeaserShowAll.']['typolink.']['parameter']=$this->target_id;
		$content.=$lcObj->TEXT($this->conf['linktextTeaserShowAll.']);

		// Returns the content from the plugin
		return $content;
	}/*}}}*/

	/**
	 * listView shows the smallads. There is no singleView, because Smallads
	 * are intended to be small, small enough to fit on a page with others.
	 *
	 * edit = 1 --> List only smallads which belong to the logged in FE user
	 * and provide a edit link
	 *
	 */
	function listView($edit=0)	{/*{{{*/
		// Don't allow editing for non-logged-in users
		if (!$GLOBALS['TSFE']->fe_user && $edit) return '<div class="error_not_allowed">'.$this->pi_getLL('no_user_logged_in').'</div>';

		// Local settings for the listView function
		$lConf=$this->conf['listView.'];

		// make a local instance of tslib_cObj
		$lcObj=t3lib_div::makeInstance('tslib_cObj');

		// Try to get the "No Image is available"-Image from TS.
		// Otherwise use the standard image.
		$this->conf['noImageAvailable']=$this->conf['noImageAvailable'] ? $this->conf['noImageAvailable'] : $this->siteRelPath.'images/noImageAvailable.gif';

		// Initializing the mode and page-pointer
		if (!isset($this->piVars['pointer'])) $this->piVars['pointer']=0;
		if (!isset($this->piVars['mode'])) {
			if ($lConf['mode']) {
				$this->piVars['mode']=$lConf['mode'];
			} else {
				$this->piVars['mode']=0;
			}
		}

		// make items for the mode (= categories)
		// first mode = all categories, text is defined in locallang.php
		// more modes = categories (defined in the typscript template)
		$items=array();
		if ($this->conf['modeSelectorType'] == 'buttons') {
			$i=0;
			$items[strval($i)] = $this->pi_getLL('list_mode_1');
			foreach ($this->conf['smalladForm.']['dataArray.']['10.']['valueArray.'] as $cat ) {
				$i++;
				$items[strval($i)]=$this->getCategoryName($cat['value'], $this->conf['smalladForm.']['dataArray.']['10.']['valueArray.']);
			}
		}

		// Add some WHERE conditons to the database query ...
		$db_whereClause = '';

		// Filter the elements according to the mode selector.
		// Transform integer value of the mode to the cleartext category value
		// stored in the database. o also could have stored the mode value, but
		// with the category value, the editor has a cleartext which he can
		// read in the backend
		if ($this->conf['modeSelectorType'] == 'buttons') {
			$i=0;
			foreach ($this->conf['smalladForm.']['dataArray.']['10.']['valueArray.'] as $cat) {
				$i++;
				if ($this->piVars['mode']==$i) {
					$db_whereClause=' AND cat LIKE "%'.$this->getCategoryName($cat['value'], $this->conf['smalladForm.']['dataArray.']['10.']['valueArray.']).'%"';
				}
			}
		} else {
			$db_whereClause .= $this->dropdownModeSelectorFilter();
		}

		// Find only smallads of this FE User if the "edit"-mode is selected
		if ($edit) {
			$db_whereClause .= ' AND fe_user_uid='.$GLOBALS['TSFE']->fe_user->user['uid'];
		}

		// Initializing the query parameters
		$this->internal['orderBy'] 				= $this->conf['listOrder'];
		$this->internal['descFlag'] 			= $this->conf['listOrderDescFlag'];
		$this->internal['orderByList'] 			= $this->conf['listOrder'];
		$this->internal['results_at_a_time'] 	= t3lib_div::intInRange($lConf['results_at_a_time'],0,1000,2);
		$this->internal['maxPages']				= t3lib_div::intInRange($lConf['maxPages'],0,1000,2);
		$this->internal['searchFieldList']		= 'content,phone,email,title,cat';

		// Get number of Smallads
		$res=$this->pi_exec_query($this->table,1,$db_whereClause);
		list($this->internal['res_count'])=$GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		// Put the whole list together
		$fullTable='';

		// Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query($this->table,0,$db_whereClause);
		$this->internal['currentTable'] = $this->table;

		// start the form for the mode selector and the searchbox
		$fullTable .= '<form action="' . $this->pi_linkTP_keepPIvars_url() . '" method="POST">';

		// Adds the mode selector (= categories)
		if (!$edit && $this->conf['showModeSelector'] && !$this->searchmode) {
			if ($this->conf['modeSelectorType'] == 'buttons') {
				$fullTable .= $this->pi_list_modeSelector($items);
			} else {
				$fullTable .= $this->renderDropdownModeSelector($res);
			}
		}

		// Adds the search box:
		if (!$edit && !$this->searchmode) {
			//$fullTable .= $this->pi_list_searchBox();
			$fullTable .= $this->renderSearchBox();
		}

		// End the form for the searchbox and the mode selector
		$fullTable .= '</form>';

		// Print message if no results found
		if (!$this->internal['res_count']) {
			$fullTable .= '<div'.$this->pi_classParam('searchresult-noresult').'>'.$this->no_results_text.'</div>';
		} else {
			// Adds the whole list table
			$fullTable .= $this->pi_list_makelist($res,$edit);

			// Adds the result browser:
			if (!$this->searchmode) {
				$fullTable .= $this->pi_list_browseresults();
			}
		}

		// Returns the content from the plugin.
		return $fullTable;
	}/*}}}*/

	/**
	 * dropdownModeSelectorFilter
	 *
	 * compiles the where clause used for filtering the elements selected by
	 * the dropdown mode selector.
	 *
	 * @param string $modes
	 * @access public
	 * @return string
	 */
	function dropdownModeSelectorFilter($queryForMode = 'all') {/*{{{*/
		$db_whereClause = '';
		$modes = array(
			'modeselector_cat' => 'cat',
			'modeselector_cat2' => 'cat2',
			'modeselector_cat3' => 'cat3'
		);
		foreach ($modes as $modeSelector => $dbFieldName) {
			if (($queryForMode == 'all' || $queryForMode == $modeSelector) && $this->piVars[$modeSelector] && $this->piVars[$modeSelector] != $this->pi_getLL('list_mode_1')) {
				$db_whereClause .= ' AND ' . $dbFieldName . ' LIKE "%' . $this->sanitizeData($this->piVars[$modeSelector]) . '%"';
			}
		}
		return $db_whereClause;
	}/*}}}*/

	/**
	 * renderSearchBox
	 *
	 * @access public
	 * @return string
	 */
	function renderSearchBox() {/*{{{*/
		$content = '';
		$content .= '<input type="text" name="tx_kesmallads_pi1[sword]" value="' . htmlspecialchars($this->piVars['sword']) . '" class="tx-kesmallads-pi1-searchbox-sword" />';
		$content .= '<input type="submit" value="' . $this->pi_getLL('pi_list_searchBox_search') . '" class="tx-kesmallads-pi1-searchbox-button" /><input type="hidden" name="no_cache" value="1" /><input type="hidden" name="tx_kesmallads_pi1[pointer]" value="" />';
		return $content;
	}/*}}}*/

	/**
	 * renderDropdownModeSelector
	 *
	 * Renders a mode selector with all three categoy-types, the third category depends
	 * on the second one and changes dynamically.
	 *
	 * @access public
	 * @return string
	 */
	function renderDropdownModeSelector() {
		$lcObj=t3lib_div::makeInstance('tslib_cObj');
		$content = '';

		// get all the first and second categories for which we have elements
		// and compile a comma separated list
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->table, 'pid IN (' . $this->conf['pidList'] . ')' . $lcObj->enableFields($this->table));
		$cat1_list = '';
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($row['cat']) {
				$cat1_list .= $row['cat'] . ',';
			}
		}
		$cat1_list = t3lib_div::uniqueList($cat1_list);

		// for the second category get only the entries which belong to the
		// already selected first one
		$cat2_list = '';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->table, 'pid IN (' . $this->conf['pidList'] . ')' . $this->dropdownModeSelectorFilter('modeselector_cat') . $lcObj->enableFields($this->table));
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($row['cat2']) {
				$cat2_list .= $row['cat2'] . ',';
			}
		}
		$cat2_list = t3lib_div::uniqueList($cat2_list);

		// for the third category get only the entries which belong to the
		// already selected second one
		$cat3_list = '';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->table, 'pid IN (' . $this->conf['pidList'] . ')' . $this->dropdownModeSelectorFilter('modeselector_cat2') . $lcObj->enableFields($this->table));
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($row['cat3']) {
				$cat3_list .= $row['cat3'] . ',';
			}
		}
		$cat3_list = t3lib_div::uniqueList($cat3_list);

		// Categories are stored as real names in the databases, so we
		// can use them directly as values for the select field
		if ($cat1_list) {
			$content .= '<select name="' . $this->prefixId . '[modeselector_cat]" id="kesmalladsform_modeselector1" size="1" class="modeselector_selectclass">';
			$content .= '<option value="' . $this->pi_getLL('list_mode_1') . '">' . $this->pi_getLL('list_mode_1') . '</option>';
			foreach(t3lib_div::trimExplode(',', $cat1_list) as $key => $value) {
				$selected = $this->piVars['modeselector_cat'] == $value ? ' selected' : '';
				$content .= '<option value="' . $value . '"' . $selected . '>' . $value . '</option>';
			}
			$content .= '</select>';
		}

		if ($cat2_list) {
			$content .= '<select name="' . $this->prefixId . '[modeselector_cat2]" id="kesmalladsform_modeselector2" size="1" class="modeselector_selectclass">';
			$content .= '<option value="' . $this->pi_getLL('list_mode_1') . '">' . $this->pi_getLL('list_mode_1') . '</option>';
			foreach(t3lib_div::trimExplode(',', $cat2_list) as $key => $value) {
				$selected = $this->piVars['modeselector_cat2'] == $value ? ' selected' : '';
				$content .= '<option value="' . $value . '"' . $selected . '>' . $value . '</option>';
			}
			$content .= '</select>';
		}

		// the third category only makes sense if the second has been selected
		if ($this->piVars['modeselector_cat2'] && $this->piVars['modeselector_cat2'] != $this->pi_getLL('list_mode_1')) {
			if ($cat3_list) {
				$content .= '<select name="' . $this->prefixId . '[modeselector_cat3]" id="kesmalladsform_modeselector3" size="1" class="modeselector_selectclass">';
				$content .= '<option value="' . $this->pi_getLL('list_mode_1') . '">' . $this->pi_getLL('list_mode_1') . '</option>';
				foreach(t3lib_div::trimExplode(',', $cat3_list) as $key => $value) {
					$selected = $this->piVars['modeselector_cat3'] == $value ? ' selected' : '';
					$content .= '<option value="' . $value . '"' . $selected . '>' . $value . '</option>';
				}
				$content .= '</select>';
			}
		}

		// get all the categories we have entries for
		// category 1
		return $content;
	}

	/**
	 * This one comes from tslib_pibase, I implemented it here, because I
	 * don't want to have any table-Tags in my list view, which are normaly
	 * rendered from pi_mlist_makelist.
	 *
	 * Returns the list of items based on the input SQL result pointer
	 * For each result row the internal var, $this->internal['currentRow'], is set with the row returned.
	 * $this->pi_list_header() makes the header row for the list
	 * $this->pi_list_row() is used for rendering each row
	 * Notice that these two functions are typically ALWAYS defined in the extension class of the plugin since they are directly concerned with the specific layout for that plugins purpose.
	 *
	 * @param	pointer		Result pointer to a SQL result which can be traversed.
	 * @param	string		Attributes for the table tag which is wrapped around the table rows containing the list --> DELETED
	 * @return	string		Output HTML, wrapped in <div>-tags with a class attribute
	 * @see pi_list_row(), pi_list_header()
	 */
	function pi_list_makelist($res,$edit=0)	{/*{{{*/
			// Make list table header:
		$tRows=array();
		$this->internal['currentRow']='';
		$tRows[] = $this->pi_list_header();

			// Make list table rows
		$c=0;
		while($this->internal['currentRow'] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$tRows[] = $this->pi_list_row($c,$edit);
			$c++;
		}

		$out = '<div'.$this->pi_classParam('listrow').'>'.implode('',$tRows).'</div>';

		return $out;
	}/*}}}*/

	/**
	 * Lists one Smallad
	 * $c : rowcounter
	 */
	function pi_list_row($c,$edit=0) {/*{{{*/
		$editPanel=$this->pi_getEditPanel();
		if (!$this->searchmode) {
			$deleteLinkURL = $this->pi_linkTP_keepPIvars_url(array('deleteuid'=>$this->getFieldContent('uid')));
			$deleteConfirmJS = 'if (confirm("'.$this->pi_getLL('really_delete','Really delete?').'")) window.location.href = "'.$deleteLinkURL.'";';
			// show standard list / long search results list
			return 	'<div'.($c%2 ? $this->pi_classParam('listrow-odd') : $this->pi_classParam('listrow')).'>'
						.'<div'.$this->pi_classParam('image').'>'.$this->getFieldContent('image').'</div>'
						.'<div'.$this->pi_classParam('textcontent').'>'

							.($edit ? '<span'.$this->pi_classParam('editlink').'>'.$this->pi_linkTP_keepPIvars($this->pi_getLL('edit_link'),array('edituid'=>$this->getFieldContent('uid'))).'</span>' : '')
							.($edit ? '<span'.$this->pi_classParam('deletelink').'>'.'<a href="#" onClick=\''.$deleteConfirmJS.'\'>'.$this->pi_getLL('delete_link').'</a>'.'</span>' : '')
							.(($edit && $this->internal['currentRow']['endtime']) ? '<span'.$this->pi_classParam('endDate').'>'.$this->pi_getLL('visible_until').$this->getFieldContent('endtime').'</span>' : '')
							.($this->conf['displaySubmitDate'] ? '<div'.$this->pi_classParam('submitDate').'>'.$this->getFieldContent('crdate').'</div>' : '')
							.'<div'.$this->pi_classParam('category').'>'.htmlspecialchars($this->getFieldContent('cat')).'</div>'
							.'<h2'.$this->pi_classParam('title').'>'.htmlspecialchars($this->getFieldContent('title')).'</h2>'
							.'<div'.$this->pi_classParam('content').'>'.$this->getFieldContent('content').'</div>'
							.($this->getFieldContent('phone') ? '<div'.$this->pi_classParam('phone').'>'.$this->pi_getLL('tx_kesmallads_smallads.phone','phone').': '.htmlspecialchars($this->getFieldContent('phone')).'</div>' : '')
							.($this->getFieldContent('displayemail') ? '<div'.$this->pi_classParam('email').'>'.$this->pi_getLL('tx_kesmallads_smallads.email','email').': '.$this->getFieldContent('email').'</div>' : '')
						.'</div>'
					.'</div>'
					.'<div'.$this->pi_classParam('listdivider').'></div>';
		} else {
			// show short results list
			$urlParameters=array('tx_kesmallads_pi1[sword]'=>$this->postVars['tx_kesmallads_pi1']['sword']);
			return '<div'.$this->pi_classParam('searchresult-shortlist').'>'.$this->pi_linkToPage(htmlspecialchars($this->getFieldContent('title')),$this->target_id,'',$urlParameters).'</div>';
		}
	}/*}}}*/

	/**
	 * we don't need a header for the smallads list
	 */
	function pi_list_header() {/*{{{*/
		return '';
	}/*}}}*/

	/**
	 * Renders the DB-Content into correct output
	 *	$fn: name of the db column
	 */
	function getFieldContent($fN) {/*{{{*/
		// make a local instance of tslib_cObj
		$lcObj=t3lib_div::makeInstance('tslib_cObj');
		switch($fN) {
			case "image":
				// This will output the image
				$this->internal['currentRow']['image'] ? $this->conf['smalladimage.']['file']=$this->uploadFolder.$this->internal['currentRow']['image'] : $this->conf['smalladimage.']['file']=$this->conf['noImageAvailable'];
				$this->conf['smalladimage.']['altText']=$this->internal['currentRow']['title'];
				return $lcObj->IMAGE($this->conf['smalladimage.']);
			break;
			case "email":
				$this->conf['smalladcontent.email.']['value']=$this->internal['currentRow']['email'];
				$this->conf['smalladcontent.email.']['stdWrap']=1;
				$this->conf['smalladcontent.email.']['stdWrap.']['typolink.']['parameter']=$this->internal['currentRow']['email'];
				return $lcObj->TEXT($this->conf['smalladcontent.email.']);
			break;
			case "content":
				//return str_replace("\n",'<br />',$this->internal['currentRow'][$fN]);
				return $this->pi_RTEcssText($this->internal['currentRow'][$fN]);
			break;
			case "endtime":
			case "crdate":
				return date($this->conf['submitDateFormat'],$this->internal['currentRow'][$fN]);
			break;
			default:
				return $this->internal['currentRow'][$fN];
			break;
		}
	}/*}}}*/

	/**
	 * getCategoryName
	 *
	 * returns the cleartext value of a category key
	 * $catkey: Key of a category like stored in the Typoscript Setup
	 * cat values and labels are found in
	 * smalladForm.dataArray.10.valueArray.
	 * check which TS-Index this "cat"-key has and return the matching label
	 *
	 * @param mixed $string
	 * @param array $valueArray
	 * @access public
	 * @return void
	 */
	function getCategoryName($catkey,$valueArray=array()) {/*{{{*/
		// make a local instance of tslib_cObj
		$lcObj=t3lib_div::makeInstance('tslib_cObj');
		if (is_array($valueArray)) {
			foreach ($valueArray as $cat) {
				// return cat as a TEXT-Element
				if (strtoupper($cat['value'])==strtoupper($catkey)) {
					$this->conf['smalladcontent.cat.'] = $cat['label.'];
					$this->conf['smalladcontent.cat.']['value'] = $cat['label'];
					return trim($lcObj->TEXT($this->conf['smalladcontent.cat.']));
				}
			}
		}

		// return nothing if no matching label has been found (for security reasons)
		return '';
	}/*}}}*/

	/**
	 * OBSOLETE -- categories are now configured via ts
	 * Get Values for select-fields from locallang.php (Of course, normally you
	 * would configure such things via flexforms or typoscript But I didn't
	 * know how to use that values in the backend (web->list modul), so I chose
	 * locallang.php as a config-file)
	 */
	function getValueArrayFromLL($lConf,$dataArrayRow,$locallang_index) {/*{{{*/
		unset ($lConf['dataArray.'][$dataArrayRow]['valueArray.']);
		$i=0;
		while ($this->pi_getLL($locallang_index.$i,'')) {
			$index=strval(($i+1)*10).'.';
			$lConf['dataArray.'][$dataArrayRow]['valueArray.'][$index]['label']=$this->pi_getLL($locallang_index.$i,'');
			$lConf['dataArray.'][$dataArrayRow]['valueArray.'][$index]['value']=$i;
			$i++;
		}
		return $lConf;
	}/*}}}*/

	/**
	 * sanitizeData
	 *
	 * sanitizeData
	 *
	 * @param string $data
	 * @access public
	 * @return string
	 */
	public function sanitizeData($data='') {/*{{{*/
		return htmlspecialchars($data, ENT_QUOTES, $GLOBALS['TSFE']->renderCharset);
	}/*}}}*/

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_smallads/pi1/class.tx_kesmallads_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_smallads/pi1/class.tx_kesmallads_pi1.php']);
}

?>
