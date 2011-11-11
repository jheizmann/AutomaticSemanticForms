<?php


/*
 * This class provides some helper methods
 * for the form generation process
 */
class ASFFormGeneratorUtils {
	
	static private $semanticDataCache = array();
	
	/*
	 * Helper method for initializeFormCreationMetadata
	 */
	public static function getPropertyValue($semanticData, $propertyName, $getAll = false, $defaultValue = ''){
		$result = $defaultValue;
		
		$properties = $semanticData->getProperties();
		
		if(array_key_exists($propertyName, $properties)){
			$values = $semanticData->getPropertyValues($properties[$propertyName]);
			if(!$getAll){
				$idx = array_keys($values);
				$idx = $idx[0];
				if(!is_null($values[$idx])){						
					$result = SMWDataValueFactory::newDataItemValue($values[$idx], null)
						->getShortWikiText();
				}
			} else {
				$result = array();
				foreach($values as $v){
					$result[] = SMWDataValueFactory::newDataItemValue($v, null)
						->getShortWikiText();
				}
			}
		}
		
		return $result;
	}
	
/*
	 * Helper method for initializeFormCreationMetadata
	 */
	public static function getInheritedPropertyValue($semanticData, $propertyName, $getAll = false, $values = array(), $processedCategories = array()){
		$properties = $semanticData->getProperties();
		
		if(array_key_exists($propertyName, $properties)){
			$vals = $semanticData->getPropertyValues($properties[$propertyName]);
			if(!$getAll){
				$idx = array_keys($vals);
				$idx = $idx[0];
				$values[] = SMWDataValueFactory::newDataItemValue($vals[$idx], null)
					->getShortWikiText();
			} else {
				foreach($vals as $v){
					$values[] = SMWDataValueFactory::newDataItemValue($v, null)
						->getShortWikiText();
				}
			}
		} else {
			$title = $semanticData->getSubject()->getTitle();
			
			if(array_key_exists($title->getText(), $processedCategories)){
				//deal with cyrcles
				return $values;
			} else {
				$processedCategories[$title->getText()] = true;
			}
			
			$superCategories = $title->getParentCategories();
			if(array_key_exists($title->getFullText(), $superCategories)){
				unset($superCategories[$title->getFullText()]);
			}
			
			$store = smwfGetStore();
			foreach($superCategories as $c => $dc){
				$semanticData = $store->getSemanticData(
					SMWDIWikiPage::newFromTitle(Title::newFromText($c, NS_CATEGORY)));
				$values = self::getInheritedPropertyValue($semanticData, $propertyName, $getAll, $values, $processedCategories);
			}
		}
		
		return $values;
	}
	
	/*
	 * Helper method for initializeFormCreationMetadata
	 */
	public static function getPropertyValueOfTypeRecord($semanticData, $propertyName, $subPropertyName, $defaultValue = ''){
		$result = $defaultValue;
		
		$properties = $semanticData->getProperties();
		
		if(array_key_exists($propertyName, $properties)){
			$values = $semanticData->getPropertyValues($properties[$propertyName]);
			
			if(is_array($values)){
				$idx = array_keys($values);
				$idx = $idx[0];
				if($values[$idx] instanceof SMWDIContainer){
					$semanticData = $values[$idx]->getSemanticData(); 
					
					$result = self::getPropertyValue($semanticData, $subPropertyName, false, $defaultValue);
				}
			}
		}
		
		return $result;
	}

	/*
	 * Get all supercategories of a given category
	 */
	public static function getSuperCategories($categoryTitle, $asTree = false, $superCategoryTitles = array(), $processedCategories = array()){
		
		if(array_key_exists($categoryTitle->getText(), $processedCategories)){
			//deal with circles
			return $superCategoryTitles;
		}  else {
			$processedCategories[$categoryTitle->getText()] = true;
		}
		
		$directSuperCatgeories = $categoryTitle->getParentCategories();
		
		if($asTree){
			$superCategoryTitles[$categoryTitle->getText()] = array();
		}
		
		foreach($directSuperCatgeories as $category => $dC){

			if($asTree){
				$superCategoryTitles[$categoryTitle->getText()] = 
					self::getSuperCategories(Title::newFromText($category), $asTree, $superCategoryTitles[$categoryTitle->getText()], $processedCategories);
			} else {
				$superCategoryTitles[substr($category, strpos($category, ':') + 1)] =
					Title::newFromText($category);
				$superCategoryTitles = self::getSuperCategories(
					Title::newFromText($category), $asTree, $superCategoryTitles, $processedCategories);
			}
		}
		
		return $superCategoryTitles;
	}
	
/*
	 * Checks if automatic semantic forms can be created.
	 * for this article
	 */
	public static function canFormForArticleBeCreated(Title $title, $createInNSCategory = false){
		//Do not create forms in NS_Category if not explicitly stated
		if($title->getNamespace() == NS_CATEGORY && !$createInNSCategory){
			return false;
		}

		$categories = $title->getParentCategories();
		
		//do not use ASF if the instance has no category annotations
		if(count($categories) == 0){
			return false;
		}
		
		//check if there is a category that has no 'no automatic formedit' annotation
		$store = smwfGetStore();
		global $wgLang;
		foreach($categories as $category => $dC){
			
			if(strpos($category, $wgLang->getNSText(NS_CATEGORY).':') === 0){
				$category = substr($category, strpos($category, ":") +1);
			}
			$categoryObject = Category::newFromName($category);
			$categoryTitle = $categoryObject->getTitle();
			
			//ASF can be created if there is one category with no 'no automatic formedit' annotation
			$semanticData = self::getSemanticData($categoryTitle);
			if(ASFFormGeneratorUtils::getPropertyValue($semanticData, ASF_PROP_NO_AUTOMATIC_FORMEDIT)!= 'true'){
				return true;					
			}
		}
			
		//all categories had a 'no automatic formedit' annotation and the ASF cannot be created
		return false;
	}
	
	
	/*
	 * Create the form dummy which is used to trick
	 * the Semantic Forms extension
	 */
	public static function createFormDummyIfNecessary(){
		global $asfDummyFormName, $sfgContLang, $wgUser, $smwgNamespacesWithSemanticLinks, $smwgHaloIP;
		
		require_once($smwgHaloIP . '/includes/SMW_OntologyManipulator.php');
		
		//Make sure that annotations in SF_NS_FORM are possible
		$smwgNamespacesWithSemanticLinks[SF_NS_FORM] = true;
		
				
		//create default form annotation text
		$annotation = '[[';
		$annotation .= $sfgContLang->m_SpecialProperties[SF_SP_PAGE_HAS_DEFAULT_FORM];
		$annotation .= '::';
		$annotation .= $asfDummyFormName;
		$annotation .= '| ]]';
		
		$pageNameFormulaDummy = "{{{info| page name=Dummy <unique number>}}}";
		
		//check dummy title
		$dummyTitle = Title::newFromText($asfDummyFormName, SF_NS_FORM);
		if(!$dummyTitle->exists()){
			//dummy article must be created
			$dummyContent = wfMsg('asf_dummy_article_content');
			$dummyContent .= $annotation .= $pageNameFormulaDummy;
			
			$article = new Article($dummyTitle);
			$article->doEdit($dummyContent, wfMsg('asf_dummy_article_edit_comment'));
		} else {
			//check if page has default form annotation is there
			$rawText = Article::newFromID($dummyTitle->getArticleID())->getRawText();
			
			$doRefresh = false;
			if(strpos($rawText, $annotation) === false){
				$doRefresh = true;
			} else if (strpos($rawText, $pageNameFormulaDummy)=== false){
				$doRefresh = true;
			}
			
			if($doRefresh){
				$dummyContent = wfMsg('asf_dummy_article_content');
				$dummyContent .= $annotation .= $pageNameFormulaDummy;
				
				$article = new Article($dummyTitle);
				$res = $article->doEdit($dummyContent, wfMsg('asf_dummy_article_edit_comment'));	
			}
		}
	}
	
/*
	 * get all properties that use at least one of the given categories as domain
	 */
	public static function getPropertiesWithDomain($categoryTitles){
		$properties = array();
		
		foreach($categoryTitles as $cT){
			foreach(smwfGetSemanticStore()->getPropertiesWithDomain($cT) as $p){
				$properties[] = $p;
			} 
		}
		
		//filter properties with no automatic form edit
		foreach($properties as $k => $p){
			$semanticData = self::getSemanticData($p);
			$noAutomaticFormEdit =
				ASFFormGeneratorUtils::getPropertyValue($semanticData, ASF_PROP_NO_AUTOMATIC_FORMEDIT); 
			
			if(strtolower($noAutomaticFormEdit) == 'true'){
				unset($properties[$k]);
			}
		}
		
		return $properties;
	}
	
	
	public static function createParseSaveLink($titleText, $label = ''){
		$linker = new Linker();
		$link = $linker->makeLink($titleText, $label);
		$link = str_replace(array('<', '>'), array('*asf-st-*', '*asf-gt-*'), $link);
		return $link;
	}
	
	public static function retranslateParseSaveLink($text){
		$text = str_replace(array('*asf-st-*', '*asf-gt-*'), array('<', '>'), $text);
		return $text;
	}
	
	
/*
	 * Return a link to Special:FormEdit if the the article has to be created eith SF or ASF and False if the
	 * normal editor should be used
	 * 
	 * @param string articleName
	 * @param array of categorynames for the new instance. (category names without namespace prefixes.)
	 * 
	 * @return string link or false
	 */
	public static function getCreateNewInstanceLink($articleName, $categories){
		
		$store = smwfGetStore();

		//todo: consider namespace when detecting if there is a manually created semantic form
		
		$defaultForm = false;
		$catWithNoNoASFEditFound = false;
		foreach($categories as $category){
			$categoryTitle = Title::newFromText($category, NS_CATEGORY);

			$semanticData = self::getSemanticData($categoryTitle);
				
			$defaultForm = ASFFormGeneratorUtils::getPropertyValue(
				$semanticData, 'Has_default_form');
				
			if($defaultForm) break;
			
			//Check if ASF has a 'No automatic formedit' annotation
			if(ASFFormGeneratorUtils::getPropertyValue($semanticData, ASF_PROP_NO_AUTOMATIC_FORMEDIT) != 'true'){
				$catWithNoNoASFEditFound = true;					
			}
		}
		
		//Do not use ASF for instances in Category NS
		$inCategoryNS = false;
		$nsId = Title::newFromText($articleName)->getNamespace();
		if($nsId == NS_CATEGORY){
			$inCategoryNS = true;	
		}
		
		$link = SpecialPage::getPage( 'FormEdit' );
		$link = $link->getTitle()->getFullURL();
		
		if(strpos($link, '?') > 0) $link .= '&';
		else $link .= '?';
		$link .= 'target='.$articleName;
		
		if($defaultForm){ //SF
			$link .= '&form='.$defaultForm;
		} else if($catWithNoNoASFEditFound && !$inCategoryNS){ //ASF
			$link .= '&categories=';
			$link .= urlencode(implode(',', $categories));
		} else { //Wikitext editor
			$link = false;
		}
		
		return $link;
	}
	
	
	public static function getSemanticData(Title $title){
		if(!array_key_exists($title->getFullText(), self::$semanticDataCache)){
			$store = smwfGetStore();
			$semanticData = $store->getSemanticData(
				SMWWikiPageValue::makePageFromTitle($title)->getDataItem());
			self::$semanticDataCache[$title->getFullText()] = $semanticData; 
		}
		
		return self::$semanticDataCache[$title->getFullText()];
	}
	
	public static function getDisplayTemplateForCategory($category){
		global $wgLang;
		if(strpos($category, $wgLang->getNSText(NS_CATEGORY).':') === 0){
			$category = substr($category, strpos($category, ":") +1);
		}
		
		$category = Title::newFromText($category, NS_CATEGORY);
		
		$store = smwfGetStore();
		$semanticData = self::getSemanticData($category);
			
		$displayTemplate = 
			self::getInheritedPropertyValue($semanticData, ASF_PROP_USE_DISPLAY_TEMPLATE);
		
		echo('<pre>'.print_r($displayTemplate, ture).'</pte>');	
			
		return $displayTemplate;
	}
	
	 
} 






