<?php

if ( !defined( 'MEDIAWIKI' ) ) die();

/*
 * This class adds Automatic Semantic Forms features
 * to the special page Special:FormEdit
 */
class ASFFormEdit extends SFFormEdit {
	
	/*
	 * This method is called if one opens Special:FormEdit
	 * 
	 * It adds some ASF features and then calls its parent method
	 */
function execute($query) {
		//get get parameters
		global $wgRequest;
		$categoryParam = $wgRequest->getVal('categories');
		$targetName = $wgRequest->getVal('target');
		
		if(!$categoryParam && !$targetName){
			$queryparts = explode( '/', $query, 2 );
			if(isset($queryparts[0]) && strpos($queryparts[0], 'categories=') === 0){
				$categoryParam = substr($queryparts[0], strlen('categories='));
				$targetName = isset( $queryparts[1] ) ? $queryparts[1] : '';
			}
		}
		
		$formName = $wgRequest->getVal('form');
		
		if(is_null($categoryParam)){
			$requestURL = $wgRequest->getRequestURL();
			$requestURL = explode('/', $requestURL);
			
			if(strpos($requestURL[count($requestURL)-2], 'categories') === 0){
				$categoryParam = $requestURL[count($requestURL)-2];
				$categoryParam = substr($categoryParam, strlen('categories'));
				$targetName = $requestURL[count($requestURL)-1];
			}
		}
		
		//Initialize category names array
		$categoryNames = array();
		if($categoryParam){
			$categoryParam = str_replace('_', ' ', $categoryParam);
			$categoryNames = explode(',', $categoryParam);
			global $wgLang;
			foreach($categoryNames as $key => $category){
				$category = trim($category);
				if(strpos($category, $wgLang->getNSText(NS_CATEGORY).':') !== 0){
					$category = $wgLang->getNSText(NS_CATEGORY).':'.$category;
				}
				$categoryNames[$key] = $category;
			}
		}
		
		if(count($categoryNames) == 0 && !is_null($categoryParam)){
			global $wgOut;
			$wgOut->addHTML( '<p><b>Error:</b> No category name was passed for automatic form creation</p>');
			return;
		}
		
		//Automatically create a new target name if category names
		//but no target name was passed
		if(count($categoryNames) > 0 && !$targetName){
			global $wgOut;
			$wgOut->addHTML( '<p><b>Error:</b> No target article name was given for automatic form creation</p>');
			return;
		}
		
		if(count($categoryNames) > 0 && $targetName){
			//The given instance will be edited with forms for the given categories
			
			//TODO: What to do with non existing or empty  category names?
			
			$targetTitle = Title::newFromText($targetName);
			
			$formDefinition = ASFFormGenerator::getInstance()->generateFormForCategories($categoryNames, $targetTitle);
			if($formDefinition){
				//Set the dummy form name to trick the Semantic Forms extension
				global $asfDummyFormName;
				ASFFormGeneratorUtils::createFormDummyIfNecessary();
				$wgRequest->setVal('form', $asfDummyFormName);
				$wgRequest->setVal('target', $targetName);
			
				global $asfFormDefData;
				$asfFormDefData = array();		
				$asfFormDefData['formdef'] = $formDefinition;
				
				//deal with additional category annotations
				$categoryNames = $this->getAdditionalCategoryAnnotations($categoryNames, $targetName);
				if(count($categoryNames) > 0){
					$asfFormDefData['additional catehory annotations'] = $categoryNames;
				}
			} else {
				global $wgOut;
				$wgOut->addHTML( '<p><b>Error:</b> No automatic form could be created for given category name(s).</p>');
				return;
			}
		} else if(count($categoryNames) == 0 && $targetName && !$formName){
			//Automatically create a form for this instance based on its category annotations
			//if the target exists
			
			$title = Title::newFromText($targetName);
			if($title->exists()){
				$formDefinition = ASFFormGenerator::getInstance()->generateFromTitle($title, true);
		
				if($formDefinition){
					global $asfFormDefData;
					$asfFormDefData = array();
					$asfFormDefData['formdef'] = $formDefinition;

					global $asfDummyFormName;
					ASFFormGeneratorUtils::createFormDummyIfNecessary();
					$wgRequest->setVal('form', $asfDummyFormName); 
				} else {
					global $wgOut;
					$wgOut->addHTML( '<p><b>Error:</b> No automatic form could be created for given article name.</p>');
					return;
				}
			} else {
				global $wgOut;
				$wgOut->addHTML( '<p><b>Error:</b> No automatic form could be created for given article name.</p>');
				return;
			}
		}
		
		parent::execute($query);
	}
	
	/*
	 * Compute which additional category annotations to add
	 * to the free text text area
	 */
	private function getAdditionalCategoryAnnotations($categoryNames, $targetName){
		$title = Title::newFromText($targetName);
		if($title->exists()){
			$annotatedParentCategories = $title->getParentCategories();
			if(count($annotatedParentCategories) > 0){
				foreach($categoryNames as $key => $category){
					if(array_key_exists(str_replace(' ', '_', $category), $annotatedParentCategories)){
						unset($categoryNames[$key]);
					} else {
						$categoryNames[$key] = $category;
					}	
				}
			}
		} 
		return $categoryNames;
	}
}