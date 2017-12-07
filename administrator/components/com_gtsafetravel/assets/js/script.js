jQuery.noConflict();

(function($) {
	$.fn.formatNumber=function(e){
		var e = window.event || e;
		var keyUnicode = e.charCode || e.keyCode;
		
		if (e !== undefined) {
			switch (keyUnicode) {
				case 16:
					break; // Shift
				case 17:
					break; // Ctrl
				case 18:
					break; // Alt
				case 27:
					this.value = '';
					break; // Esc: clear entry
				case 35:
					break; // End
				case 36:
					break; // Home
				case 37:
					break; // cursor left
				case 38:
					break; // cursor up
				case 39:
					break; // cursor right
				case 40:
					break; // cursor down
				case 78:
					break; // N (Opera 9.63+ maps the "." from the number key section to the "N" key too!)
				case 110:
					break; // . number block (Opera 9.63+ maps the "." from the number block to the "N" key (78) !!!)
			}
		}

		var inputVal = this.val();
		inputVal = inputVal.replace(/\./g, '.');
		inputVal = inputVal.replace(/,{2,}/g, '.');
		this.val(inputVal);
	};

	$.fn.preventKey=function(event){
		// Allow: backspace, delete, tab, escape, enter and .
		if ($.inArray(event.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
			// Allow: Ctrl+A
			(event.keyCode == 65 && event.ctrlKey === true) ||
			// Allow: home, end, left, right
			(event.keyCode >= 35 && event.keyCode <= 39) || (event.keyCode == 188)) {
			// let it happen, don't do anything
			return;
		} else {
			// Ensure that it is a number and stop the keypress
			if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105)) {
				event.preventDefault();
			} else if(event.keyCode == 190) {
				event.preventDefault();
			}
		}
		
	};

	$(window).load(function() {
		/* NUMERIC
		 ---------------------- */
		// Format Numeric
		$('.numeric').formatCurrency({
			region: 'numeric'
		});
		$(document).on('keyup', '.numeric', function(e) {
			$(this).formatCurrency({ region: 'numeric' });
			$(this).formatNumber(e);
		});

		$(document).on('keydown', '.numeric', function(e) {
			$(this).preventKey(e);
		});

		$('.numeric').bind("paste", function(e) {
			e.preventDefault();
		});

		/* CURRENCY
		 ---------------------- */
		// Format Currency
		$('.currency').formatCurrency({
			region: 'custom'
		});

		$(document).on('keyup', '.currency', function(e) {
			$(this).formatCurrency({ region: 'custom' });
			$(this).formatNumber(e);
		});

		$(document).on('keydown', '.currency', function(e) {
			$(this).preventKey(e);
		});

		/* OTHER
		 ---------------------- */
		$('fieldset.filter legend').click(function() {
			jQuery(this).closest('fieldset').children().not('legend').toggle();
			return false;
		});

		$('#limit option').slice(7).remove();

		$('.btn-toolbar .btn-list').click(function() {

		});


		/* Button Dropdown
		 ---------------------- */
		$('.dropdownButton .option').click(function(){
			var dropdown = $(this).parent().parent().prev();
			var task = $('.task', dropdown).val();
			var is_list = $('.is_list', dropdown).val();
			var input = $('.input', dropdown);
			var val = $(this).attr('val');

			input.val(val);

			if(task) {
				if(is_list) {
					submitbuttonlist(task);
				} else {
					submitbutton(task);
				}
			} else {
				$('#adminForm').submit();
			}
			
			return false;
		});
	});
})(jQuery);

/* Override joomla.javascript, as form-validation not work with ToolBar */
function submitbutton(task, form) {
   	form = typeof form !== 'undefined' ? form : 'adminForm';
   	
    var f = jQuery('#'+form)[0];
    if (document.formvalidator.isValid(f)) {
        submitform(task, form);    
    }
}

function submitform(task, form) {
	form = typeof form !== 'undefined' ? form : 'adminForm';
	var f = jQuery('#'+form)[0];
    Joomla.submitform(task, f);
}

function submitbuttonlist(task, form) {
	if (document.adminForm.boxchecked.value == 0) {
		alert(Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));
		return false;
	} else {
		submitform(task, form);
	}
	return false;
}

function submitbuttonDelete(task, form) {
	if (confirm(Joomla.JText._('COM_GTSAFETRAVEL_CONFIRM_DELETE'))) {
		submitform(task, form);
	}
	return false;
}

function submitbuttonDeleteList(task, form) {
	if (document.adminForm.boxchecked.value == 0) {
		alert(Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));
		return false;
	} else {
		submitbuttonConf(task, form);
	}
}

