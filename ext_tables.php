<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::allowTableOnStandardPages("tx_kesmallads_smallads");

t3lib_extMgm::addToInsertRecords("tx_kesmallads_smallads");

$TCA["tx_kesmallads_smallads"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads",		
		"thumbnail" => "image",
		"label" => "title",	
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"versioning" => "1",	
		"languageField" => "sys_language_uid",	
		"transOrigPointerField" => "l18n_parent",	
		"transOrigDiffSourceField" => "l18n_diffsource",	
		"sortby" => "sorting",	
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",	
			"starttime" => "starttime",	
			"endtime" => "endtime",	
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_kesmallads_smallads.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, cat, content, image, phone, email, displayemail, title, reviewed",
	)
);

// Show FlexForm field in plugin configuration
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

// Configure FlexForm field
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1','FILE:EXT:ke_smallads/flexform_ds.xml');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(Array('LLL:EXT:ke_smallads/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/','Smallads');


if (TYPO3_MODE=="BE")	{
	t3lib_extMgm::addModule("web","txkesmalladsM1","",t3lib_extMgm::extPath($_EXTKEY)."mod1/");
}
?>
