<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_kesmallads_smallads=1
');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_kesmallads_pi1 = < plugin.tx_kesmallads_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_kesmallads_pi1.php','_pi1','list_type',0);


t3lib_extMgm::addTypoScript($_EXTKEY,'setup','
	tt_content.shortcut.20.0.conf.tx_kesmallads_smallads = < plugin.'.t3lib_extMgm::getCN($_EXTKEY).'_pi1
	tt_content.shortcut.20.0.conf.tx_kesmallads_smallads.CMD = singleView
',43);
?>
