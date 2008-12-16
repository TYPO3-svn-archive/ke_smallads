<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_kesmallads_smallads"] = Array (
	"ctrl" => $TCA["tx_kesmallads_smallads"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,fe_group,cat,user,content,image,email,phone,displayemail,title,reviewed",
		'maxDBListItems' => 25,
		'maxSingleDBListItems' => 25
	),
	"feInterface" => $TCA["tx_kesmallads_smallads"]["feInterface"],
	"columns" => Array (
		'sys_language_uid' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_kesmallads_smallads',
				'foreign_table_where' => 'AND tx_kesmallads_smallads.pid=###CURRENT_PID### AND tx_kesmallads_smallads.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"starttime" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.starttime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"default" => "0",
				"checkbox" => "0"
			)
		),
		"endtime" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.endtime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"cat" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.cat",		
			"config" => Array (
				"type" => "input",	
				"size" => "20",	
				"max" => "50",	
			)
		),
		"content" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.content",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"comment" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.comment",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"image" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.image",		
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],	
				"max_size" => 500,	
				"uploadfolder" => "uploads/tx_kesmallads",
				"show_thumbs" => 1,	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"phone" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.phone",		
			"config" => Array (
				"type" => "input",	
				"size" => "20",	
				"max" => "50",	
			)
		),
		"email" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.email",		
			"config" => Array (
				"type" => "input",	
				"size" => "20",	
				"max" => "50",	
			)
		),
		"displayemail" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.displayemail",		
			"config" => Array (
				"type" => "check",
				"default" => 1,
			)
		),
		"title" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.title",		
			"config" => Array (
				"type" => "input",	
				"size" => "20",	
				"max" => "50",	
				"eval" => "required",
			)
		),
		"reviewed" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.reviewed",		
			"config" => Array (
				"type" => "check",
			)
		),
		"iscommercial" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.iscommercial",        
            "config" => Array (
                "type" => "radio",
                "items" => Array (
                    Array("LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.iscommercial.I.0", "0"),
                    Array("LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.iscommercial.I.1", "1"),
                ),
            )
        ),
		"user" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.user",		
			"config" => Array (
				"type" => "input",	
				"size" => "20",	
				"max" => "50",	
			)
		),
		"fe_user_uid" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:ke_smallads/locallang_db.php:tx_kesmallads_smallads.fe_user_uid",        
            "config" => Array (
                "type" => "group",    
                "internal_type" => "db",    
                "allowed" => "fe_users",    
                "size" => 1,    
                "minitems" => 0,
                "maxitems" => 1,
            )
     ),	
	),
	"types" => Array (
		"0" => Array("showitem" => "
										l18n_parent, 
										l18n_diffsource, 
										hidden;;1, reviewed,
										title,
										cat, user,
										content;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/tx_kesmallads/], 
										phone,
										email, displayemail, iscommercial,
										image, 
										fe_user_uid,
										comment,
										")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime, endtime, fe_group")
	),
);
?>