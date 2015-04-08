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
t3lib_extMgm::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_kesmallads_smallads", field "content"
	# ***************************************************************************************
RTE.config.tx_kesmallads_smallads.content {
  proc.exitHTMLparser_db=1
  proc.exitHTMLparser_db {
    keepNonMatchedTags=1
    tags.font.allowedAttribs= color
    tags.font.rmTagIfNoAttrib = 1
    tags.font.nesting = global
  }
  showButtons = bold,italic,unorderedlist,orderedlistlink,formatblock,link,textstyle
  hideButtons = image,table,blockstylelabel,blockstyle,textstylelabel,fontstyle,fontsize,formatblock,unterline,strikethrough,subscript,superscript,lefttoright,textcolor,bgcolor,emoticon,textindicator,insertcharacter,line,link,
  hidePStyleItems = address,div,pre,h3,h4,h5,h6
  showTagFreeClasses = 1
  ignoreMainStyleOverride = 1
}
');
?>
