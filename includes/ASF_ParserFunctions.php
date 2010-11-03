<?php

/*
 * This class provides some Parser Functions
 * that are shipped together with the Automatic
 * Semantic Forms Extension
 */
class ASFParserFunctions {
	
	/*
	 * Setup parser functions
	 */
	static function registerFunctions( &$parser ) {
		$parser->setFunctionHook( 'CreateSilentAnnotations', 
			array( 'ASFParserFunctions', 'renderSilentAnnotationsTemplate' ), SFH_NO_HASH + SFH_OBJECT_ARGS);
		$parser->setFunctionHook( 'shownow', 
			array( 'ASFParserFunctions', 'renderShowNow' ), SFH_OBJECT_ARGS); 
		
		global $wgHooks;
		$wgHooks['ParserAfterTidy'][] = 'ASFParserFunctions::finallyRenderShowNow';
		
		return true;
	}
	
	/*
	 * Initialize magic words
	 */
	static function languageGetMagic( &$magicWords, $langCode = "en" ) {
		$magicWords['CreateSilentAnnotations']	= array ( 0, 'CreateSilentAnnotations' );
		$magicWords['shownow']	= array ( 0, 'shownow' );
		
		return true;
	}
	
	/*
	 * The CreateSilentAnnotations parser function
	 */
	static function renderSilentAnnotationsTemplate( &$parser, $frame, $args) {
		$result = "";
		
		global $smwgBaseStore;
		$store = new $smwgBaseStore();
		
		global $asfSilentAnnotations;
		if(!is_array($asfSilentAnnotations)) $asfSilentAnnotations = array();
		foreach($args as $arg){
			$arg = explode("=",  trim($frame->expand( $arg)), 2);
			
			if(count($arg) != 2) continue;
			
			$propertyName = $arg[0];
			$value = $arg[1];
			
			$title = Title::newFromText($propertyName, SMW_NS_PROPERTY);
			
			$asfSilentAnnotations[$propertyName] = $value;
			
			if(!$title->exists()){
				$result .= '[['.$propertyName.'::'.$value.'| ]]';
				continue;
			};
			
			$semanticData = $store->getSemanticData($title);
			$maxCardinality = 
				ASFFormGeneratorUtils::getPropertyValue($semanticData, ASF_PROP_HAS_MAX_CARDINALITY);
			$delimiter = 
				ASFFormGeneratorUtils::getPropertyValue($semanticData, ASF_PROP_DELIMITER);
			
			if($maxCardinality != 1 || $delimiter){
				if(!$delimiter) $delimiter = ';';
				
				foreach(explode($delimiter, $value) as $val){
					if(strlen(trim($val)) == 0) continue;
					$result .= '[['.$propertyName.'::'.$val.'| ]]';
				}
			} else {
				$result .= '[['.$propertyName.'::'.$value.'| ]]';
			}
		}
		
		return $result;
	}
	
	
	/*
	 * The #shownow parser function
	 */
	static function renderShowNow( &$parser, $frame, $args) {
		$result = "";
		
		$showProperty = array();
		
		if(array_key_exists(0, $args)){
			$result .= "__asf_shownow:";
			$showProperty['name'] = str_replace(' ', '_' , trim($args[0]));
			$result .= $showProperty['name'];
		}
		
		if(array_key_exists(1, $args)){
			$showProperty['linked'] = trim($frame->expand($args[1]));
			if($showProperty['linked'] == 'false') $showProperty['linked'] = false;
		} else {
			$showProperty['linked'] = true;
		}
		
		if(array_key_exists(2, $args) && array_key_exists(3, $args)){
			$showProperty['variable'] = trim($frame->expand($args[2]));
			
			$showProperty['evaluate'] = trim($args[3]);
			$showProperty['evaluate'] = self::getArgValue($showProperty['evaluate'], 'inner');	
		}
		
		if(count($showProperty) > 0){
			global $asfShowNowProperties;
			$asfShowNowProperties[]= $showProperty;
		}
		
		return $result;
	}
	
	/*
	 * Helper method for the #show parser function
	 */
	private static function getArgValue($value, $tag = 'value'){
		$value = explode('<'.$tag.'>', $value);
		$value = substr($value[0], 0, strrpos($value[1], '</'.$tag.'>'));
		$value = trim($value);
		return $value;
	}
	
	/*
	 * Finally process the #shownow parser function and replace it
	 * with data from the semantic store
	 */
	public static function finallyRenderShowNow( &$parser, &$text ) {
		global $asfShowNowProperties;
		
		global $asfFinallyRenderShowNowStarted;
		if($asfFinallyRenderShowNowStarted) return true;
		$asfFinallyRenderShowNowStarted = true;
		
		if(count($asfShowNowProperties) == 0) return true;		
		
		$semanticData = $parser->getOutput()->mSMWData;
		
		foreach($asfShowNowProperties as $key => $prop){
			if(strpos($text, "asf_shownow:".$prop['name']) === false) continue;
			
			$value = 	
				ASFFormGeneratorUtils::getLongPropertyValues($semanticData, $prop['name'], $prop['linked']);
			
			if(array_key_exists('variable', $prop) && array_key_exists('evaluate', $prop)){
				$value = str_replace($prop['variable'], $value, $prop['evaluate']);
			}
			
			if($prop['linked'] || (array_key_exists('variable', $prop) && array_key_exists('evaluate', $prop))){
				$p = new Parser();
				$popts = new ParserOptions();
				$value = $p->parse($value, $parser->getTitle(), $popts)->getText();
			}	
				
			$text = str_replace('__asf_shownow:'.$prop['name'], $value, $text);
			unset($asfShowNowProperties[$key]);
		}
		
		return true;
	}
}



