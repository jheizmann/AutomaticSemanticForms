<?php

define('ASF_ALLOWED_EXPLICIT_INPUT_TYPES', '-text- -textarea-');
/*
 * This class provides the form definition syntax for the section, which
 * holds the form input fields for unresolved annotations
 */
class ASFUnresolvedAnnotationsFormData extends ASFCategoryFormData {
	
	/*
	 * Discovers unresolved annotations and initializes the corresponding
	 * property form fields
	 */
	public function __construct($instanceTitleObject, $categoryFormDataInstances = array()){
		if(!$instanceTitleObject->exists()){
			$this->propertiesFormData = array();
			return;
		}
		
		$allAnnotations = $this->getAllAnnotations($instanceTitleObject);
		
		$unresolvedAnnotations = 
			$this->getUnresolvedAnnotations($categoryFormDataInstances, $allAnnotations);
		
		$this->initializePropertiesFormData($unresolvedAnnotations);
		
		$this->setPropertyFormFieldsInputTypes();
		
		$this->sortProperties();
	}
	
	/*
	 * Get all annotations made with the silent annotations parser function
	 */
	private function getAllAnnotations($instanceTitleObject){
		$store = smwfGetStore();
		$allAnnotations = 
			ASFFormGeneratorUtils::getSemanticData($instanceTitleObject)->getProperties();
		return $allAnnotations;
	}
	
	
	/*
	 * Find all silent annotations which are not covered by one
	 * of the other category form data sections
	 */
	private function getUnresolvedAnnotations($categoryFormDataInstances, $allAnnotations){
		
		foreach($categoryFormDataInstances as $categoryData){
			foreach($categoryData->propertiesFormData as $propertyFormData){
				$propertyName = $propertyFormData->titleObject->getText();
				if(array_key_exists(str_replace(' ', '_', $propertyName), $allAnnotations)){
					unset($allAnnotations[str_replace(' ', '_', $propertyName)]);
				}
			}
		}
		
		foreach($allAnnotations as $key => $annotation){
			if(substr($key, 0, 1) == '_'){
				unset($allAnnotations [$key]);
			}
		}
		
		return $allAnnotations;
	}
	
	/*
	 * Initialize the property form data for unresolved annotations
	 */
	private function initializePropertiesFormData($unresolvedAnnotations){
		
		$this->propertiesFormData = array();
		foreach($unresolvedAnnotations as $name => $dontCare){
			$this->propertiesFormData[$name] = 
				new ASFPropertyFormData(Title::newFromText($name, SMW_NS_PROPERTY));
		}
		
	}
	
	/*
	 * Create the section intro
	 */
	public function getCategorySectionIntro(){
		if(!is_null($this->categorySectionIntro)) return $this->categorySectionIntro;
		
		if(count($this->propertiesFormData) == 0){
			return '';
		}
		
		if($this->isEmptyCategory()){
			return '';
		}
		
		//create collapsed version of section
		$intro = "\n{{#collapsableFieldSetStart:";
		$intro .= wfMsg('asf_unresolved_annotations');
		$intro .= "}}";
		
		$intro .= "\n\n{|";
		$intro .= "\n";
		
		$this->categorySectionIntro = $intro;
		return $this->categorySectionIntro;
	}
	
	/*
	 * Some form field input types are not allowed in this section,
	 * since they are not suitable for removing an annotation, i.e. checkbox
	 */
	private function setPropertyFormFieldsInputTypes(){
		foreach($this->propertiesFormData as $name => $data){
			
			$setExplicitly = false;
			
			if(!$data->explicitInputType){
				$objectType = '-'.strtolower($data->objectType).'-';
				if(strpos(DATETIMEDATATYPES, $objectType) !== false 
						|| strpos(CHECKBOXDATATYPES, $objectType) !== false){
					
					$setExplicitly = true;
				} 
			} else {
				if(strpos(ASF_ALLOWED_EXPLICIT_INPUT_TYPES, '-'.strtolower($data->explicitInputType).'-')){
					$setExplicitly = true;
				}				
			}
			
			if($setExplicitly){
				$this->propertiesFormData[$name]->explicitInputType = "text";
			}
		}
	}
	
	public function getPreloadingArticles(){
		return array();		
	}
	
	public function getPageNameTemplate(){
		return array(true, '');
	}
	
	public function hideFreeText(){
		return false;
	}
	
}