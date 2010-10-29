<?php 


global $asfIP;
include_once($asfIP . '/languages/ASF_Language.php');

class ASFLanguageEn extends ASFLanguage {
	
	protected $asfUserMessages = array(
		'asf_free_text' => "Free text:",
		'asf_dummy_article_edit_comment' => "Created by the Automatic Semantic Forms Extension",
		'asf_dummy_article_content' => "'''This article is required by the Automatic Semantic Forms Extension. Please do not move, edit or delete this article.'''",
		'asf_category_section_label' => "Enter $1 data:",
		'asf_duplicate_property_placeholder' => "Please enter value in the input field above.",
		'asf_unresolved_annotations' => "Deal with unresolved annotations:",
		
		'asf_tt_intro' => "Click to open $1",
		'asf_tt_type' => "The <b>type</b> of this property is $1.",
		'asf_tt_autocomplete' => "This input field <b>autocompletes</b> on $1.",
		'asf_tt_delimiter' => "Several values are allowed in this input field. \"$1\" is used as <b>delimiter</b>."
	);

}


