jQuery.noConflict();
(function($) {
	$(function() {
		$(document).on('click', '.nav-tabs a', function(e) {
			$('#tab_position').val($(this).attr('href').replace('#', ''));
		});

		$('option').click(function() {
			var selOpt = $('option:selected', $(this).parent('select'));

			selOpt.each(function(i, selected){ 
				if($(selected).val() == 0) {
					selOpt.prop("selected", false);
					$(selected).prop("selected", true);
				} 
			});
				
		});

		$('.select_all').click(function(event) {  //on click 
			if(this.checked) { // check select status
				$('.show_fields').each(function() { //loop through each checkbox
					this.checked = true;  //select all checkboxes with class "checkbox1"               
				});
			} else{
				$('.show_fields').each(function() { //loop through each checkbox
					this.checked = false; //deselect all checkboxes with class "checkbox1"                       
				});
			}
		});

		$('.modalForm').click(function(e) {
			e.preventDefault();
			var link = $(this).attr('link');

			showModal(link);
		});

		$('#adminlist').on('click', 'button.modalForm', function(e) {
			e.preventDefault();
			var link = $(this).attr('link');

			showModal(link);
		});

		var reload = false;

		function showModal(href) {
			$('#formModal').on('shown.bs.modal', function() {
				reload = false;
				$(this).find('iframe').attr('src', href);
			});

			$('#formModal').on('hidden.bs.modal', function() {
				$('h4', $(this)).html('&nbsp;');
				$('.loading-msg', $(this)).show();
				$('iframe', $(this)).attr('src', 'about:blank');
				$('iframe', $(this)).css('opacity', 0);
				$('iframe', $(this)).height(0);

				if (typeof adminlist !== 'undefined') {
					adminlist.ajax.reload();
				} else {
					window.location.href = window.location.href;
				}
			});
			
			$('#formModal').modal({show:true});
		}
		$("#formModal iframe").load(function() {
			var iframe = $(this);
			if($('#com_gtsafetravel', iframe.contents()).length) {
				$('#formModal h4').html($('#com_gtsafetravel h1', iframe.contents()).html());
				$('#com_gtsafetravel .page-header', iframe.contents()).hide();
				$('#window-mainbody', iframe.contents()).css('padding', '0');
				iframe.css('opacity', 1);
				$('.btn-close', iframe.contents()).click(function(){
					$('#formModal').modal('hide');
				});
				new ResizeSensor(iframe.contents().find("#window-mainbody"), function(){
					$('#formModal .loading-msg').hide();
					iframe.height(iframe.contents().find("#window-mainbody").height()+10);
				});
			}
		});
	});

	
})(jQuery);

function decimalToRoman(num) { 
	// 3,888,888 is the longest number represented by Roman numerals 
	if (typeof num !== 'number')   
	return false;   
	  
	var digits = String(+num).split("");
	var key = [
		"","C","CC","CCC","CD","D","DC","DCC","DCCC","CM",  
		"","X","XX","XXX","XL","L","LX","LXX","LXXX","XC",  
		"","I","II","III","IV","V","VI","VII","VIII","IX"
	];
	var roman_num = "";
	var i = 3;  
	while (i--) {
		roman_num = (key[+digits.pop() + (i * 10)] || "") + roman_num;  
	}
	
	return Array(+digits.join("") + 1).join("M") + roman_num;  
}