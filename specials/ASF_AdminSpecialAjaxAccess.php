<?php

/*
 * This file provides some methods, that are called by
 * the ASF special page via Ajax.
 */

global $wgAjaxExportList;
$wgAjaxExportList[] = 'asff_getFormPreview';
$wgAjaxExportList[] = 'asff_saveForm';
$wgAjaxExportList[] = 'asff_checkFormName';

/*
 * Load asf preview and source code
 */
function asff_getFormPreview($categories){
	
	$categories = explode(';', $categories);
	
	list($formDefinition, $dontCare) = ASFFormGenerator::getInstance()
		->generateFormForCategories($categories);
		
	if($formDefinition){
		global $asfDummyFormName;
		$errors = ASFFormGeneratorUtils::createFormDummyIfNecessary();
		$form_name = $asfDummyFormName;
		
		global $asfFormDefData;
		$asfFormDefData = array();
		$asfFormDefData['formdef'] = $formDefinition;

		$target_name = SFUtils::titleString( Title::newFromText('xyz'));
		SFFormEdit::printForm( $form_name, $target_name );
		
		global $wgOut;
		$formHTML = $wgOut->mBodytext;
		$formHTML = substr($formHTML, strpos($formHTML, '<fieldset'));
		$formHTML = substr($formHTML, 0, strrpos($formHTML, '</fieldset'));
		
		$formDefinition = substr($formDefinition, 0, strpos($formDefinition, wfMsg('asf_autogenerated_msg')));
		
		$success = 'true';
	} else {
		//todo: Language
		$formHTML = $formDefinition = 'A Semantic Form could not be created for the chosen categories.';
		
		$success = 'false';
	}
	
	$response = array('preview' => $formHTML, 'source' => $formDefinition, 'success' => $success);
	$response = json_encode($response);
	$response = '--##startasf##--'.$response.'--##endasf##--';
	
	return $response;
}


function asff_saveForm($formName, $formDefinition){
	global $wgContLang;
	if(strpos($formName, $wgContLang->getNsText(SF_NS_FORM).':') !== 0){
		$formName = $wgContLang->getNsText(SF_NS_FORM).':'.$formName; 
	}
	
	$title = Title::newFromText($formName);
	
	global $wgUser;
	if(!is_null($title) && smwf_om_EditArticle($title->getFullText(), $wgUser, $formDefinition, '')){
		$linker = new Linker();
		$title = $linker->makeLink($title->getFullText());
		$success = 'true';	
	} else {
		$title = $formName;
		$success = 'false';	
	}
	
	$response = array('title' => $title, 'success' => $success);
	$response = json_encode($response);
	$response = '--##startasf##--'.$response.'--##endasf##--';
	
	return $response;
}


function asff_checkFormName($formName){
	global $wgContLang;
	if(strpos($formName, $wgContLang->getNsText(SF_NS_FORM).':') !== 0){
		$formName = $wgContLang->getNsText(SF_NS_FORM).':'.$formName; 
	}
	
	$title = Title::newFromText($formName);
	
	$response = '';
	if(!is_null($title) && $title->exists()){
		$response = 'exists';
	}
	
	$response = '--##startasf##--'.$response.'--##endasf##--';
	
	return $response;
}





