/*
 * Copyright (C) Vulcan Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @file
 * @ingroup AutomaticSemanticFormsScripts
 * @author: Ingo Steinbauer
 */


var ASFAdmin = {

	refreshTabs : function(){
		
		var categories = jQuery('#asf_category_input').attr('value');
		
		var url = wgServer + wgScriptPath + "/index.php";
		jQuery.ajax({ url:  url, 
			data: {
				'action' : 'ajax',
				'rs' : 'asff_getFormPreview',
				'rsargs[]' : [categories]
			},
			success: ASFAdmin.refreshTabsCallBack
		});
	},
	
	refreshTabsCallBack : function(data){
		data = data.substr(data.indexOf('--##startasf##--') + 16, data.indexOf('--##endasf##--') - data.indexOf('--##startasf##--') - 16); 
		data = JSON.parse(data);
	
		data.source = data.source.replace(/>/g, "&gt;");
		data.source = data.source.replace(/</g, "&lt;");
		data.source = '<pre>' + data.source + '</pre>';
		
		jQuery('#asf_preview_tab').html(data.preview);
		
		jQuery('#asf_source_tab').html(data.source);
		
		
		jQuery('#asf_create_tab span:nth-child(1)').css('display', 'none');
		jQuery('#asf_create_tab span:nth-child(4)').css('display', 'none');
		if(data.success == 'true'){
			jQuery('#asf_create_tab span:nth-child(2)').css('display', 'none');
			jQuery('#asf_create_tab div:nth-child(3)').css('display', 'block');
		} else {
			jQuery('#asf_create_tab span:nth-child(2)').html(data.preview);
			jQuery('#asf_create_tab span:nth-child(2)').css('display', 'block');
			jQuery('#asf_create_tab div:nth-child(3)').css('display', 'none');
		}
		
		initializeNiceASFTooltips();
		
		//todo:Initialize autocompletion
	},
	
	displayPreview : function(){
		jQuery('#asf_source_tab').css('display', 'none');
		jQuery('.asf_tabs td:nth-child(3)').removeClass('asf_selected_tab');
		jQuery('.asf_tabs td:nth-child(3)').addClass('asf_unselected_tab');
		
		jQuery('#asf_create_tab').css('display', 'none');
		jQuery('.asf_tabs td:nth-child(5)').removeClass('asf_selected_tab');
		jQuery('.asf_tabs td:nth-child(5)').addClass('asf_unselected_tab');
		
		jQuery('#asf_preview_tab').css('display', 'inline-block');
		jQuery('.asf_tabs td:nth-child(1)').removeClass('asf_unselected_tab');
		jQuery('.asf_tabs td:nth-child(1)').addClass('asf_selected_tab');
	},
	
	displaySource : function(){
		jQuery('#asf_source_tab').css('display', 'inline-block');
		jQuery('.asf_tabs td:nth-child(3)').removeClass('asf_unselected_tab');
		jQuery('.asf_tabs td:nth-child(3)').addClass('asf_selected_tab');
		
		jQuery('#asf_create_tab').css('display', 'none');
		jQuery('.asf_tabs td:nth-child(5)').removeClass('asf_selected_tab');
		jQuery('.asf_tabs td:nth-child(5)').addClass('asf_unselected_tab');
		
		jQuery('#asf_preview_tab').css('display', 'none');
		jQuery('.asf_tabs td:nth-child(1)').removeClass('asf_selected_tab');
		jQuery('.asf_tabs td:nth-child(1)').addClass('asf_unselected_tab');
	},
	
	displayCreate : function(){
		jQuery('#asf_source_tab').css('display', 'none');
		jQuery('.asf_tabs td:nth-child(3)').removeClass('asf_selected_tab');
		jQuery('.asf_tabs td:nth-child(3)').addClass('asf_unselected_tab');
		
		jQuery('#asf_create_tab').css('display', 'inline-block');
		jQuery('.asf_tabs td:nth-child(5)').removeClass('asf_unselected_tab');
		jQuery('.asf_tabs td:nth-child(5)').addClass('asf_selected_tab');
		
		jQuery('#asf_preview_tab').css('display', 'none');
		jQuery('.asf_tabs td:nth-child(1)').removeClass('asf_selected_tab');
		jQuery('.asf_tabs td:nth-child(1)').addClass('asf_unselected_tab');
	},
	
	saveForm : function(){
		var formName = jQuery('#asf_formname_input').attr('value');
		var formDefinition = jQuery('#asf_source_tab pre').html();
		formDefinition = formDefinition.replace(/&gt;/g, ">");
		formDefinition = formDefinition.replace(/&lt;/g, "<");
		
		var url = wgServer + wgScriptPath + "/index.php";
		jQuery.ajax({ url:  url, 
			type : 'POST',
			data: {
				'action' : 'ajax',
				'rs' : 'asff_saveForm',
				'rsargs[]' : [formName, formDefinition]
			},
			success: ASFAdmin.saveFormCallBack
		});
	},
	
	saveFormCallBack : function(data){
		data = data.substr(data.indexOf('--##startasf##--') + 16, data.indexOf('--##endasf##--') - data.indexOf('--##startasf##--') - 16); 
		data = JSON.parse(data);
		
		jQuery('#asf_create_tab div:nth-child(3)').css('display', 'none');
		jQuery('#asf_create_tab span:nth-child(4)').css('display', 'block');
		if(data.success == 'true'){
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(1)').css('display', 'inline');
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(2)').css('display', 'inline');
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(2)').html(data.title);
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(3)').css('display', 'inline');
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(4)').css('display', 'none');
		} else {
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(1)').css('display', 'none');
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(2)').css('display', 'none');
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(3)').css('display', 'none');
			jQuery('#asf_create_tab span:nth-child(4) strong:nth-child(4)').css('display', 'inline');
		}
	},
	
	
	finishSaveRequest : function(){
		jQuery('#asf_create_tab div:nth-child(3)').css('display', 'block');
		jQuery('#asf_create_tab span:nth-child(4)').css('display', 'none');
	},
	
	checkFormName : function(){
		var formName = jQuery('#asf_formname_input').attr('value');
		var originalValue = jQuery('#asf_formname_input').attr('originalValue');
		
		if(formName != originalValue){
			jQuery('#asf_formname_input').attr('originalValue', formName);
			
			var url = wgServer + wgScriptPath + "/index.php";
			jQuery.ajax({ url:  url, 
				data: {
					'action' : 'ajax',
					'rs' : 'asff_checkFormName',
					'rsargs[]' : [formName]
				},
				success: ASFAdmin.checkFormNameCallBack
			});
		}
	},
	
	checkFormNameCallBack : function (data){
		data = data.substr(data.indexOf('--##startasf##--') + 16, data.indexOf('--##endasf##--') - data.indexOf('--##startasf##--') - 16); 
		
		if(data == 'exists'){
			jQuery('#asf_create_tab div:nth-child(3) small:nth-child(4)').css('display', 'inline');
		} else {
			jQuery('#asf_create_tab div:nth-child(3) small:nth-child(4)').css('display', 'none');
		}
	}
};

window.ASFAdmin = ASFAdmin;
