jQuery.noConflict();
(function($) {
	inputmask.extendDefinitions({
		'Q': {
			validator: "[-a-zA-Z0-9]",
			cardinality: 1,
			casing: "lower"
		}
	});
	
	$(function() {
		if($("#jform_id_kasus_pasal_tipe").length) {
			$('#jform_nama').addClass('inputmask');
			setPasalMask();
		}

		function setPasalMask() {
			var pasal = $('#jform_nama').val();
			$('#jform_nama').val('');
			switch($('#jform_id_kasus_pasal_tipe').val()) {
				case '1':
					$('#jform_nama').removeAttr('readonly');
					$('#jform_nama').inputmask({ mask: 'KUHP P\\as\\al Q{1,10}[ \\Ay\\at 9{1,4}]', greedy: false, clearIncomplete: true });
					if(pasal.indexOf('KUHP') > -1) {
						$('#jform_nama').val(pasal);
					}
					break;
				case '2':
					$('#jform_nama').removeAttr('readonly');
					$('#jform_nama').inputmask({ mask: 'UU No. 9{1,4} T\\ahun 9{4}[ P\\as\\al Q{1,10}][ \\Ay\\at 9{1,4}]', greedy: false, clearIncomplete: true });
					if(pasal.indexOf('UU No.') > -1) {
						$('#jform_nama').val(pasal);
					}
					break;
				case '3':
					$('#jform_nama').removeAttr('readonly');
					$('#jform_nama').inputmask({ mask: 'Perpu No. 9{1,4} T\\ahun 9{4}[ P\\as\\al Q{1,10}][ \\Ay\\at 9{1,4}]', greedy: false, clearIncomplete: true });
					if(pasal.indexOf('Perpu No.') > -1) {
						$('#jform_nama').val(pasal);
					}
					break;
				case '4':
					$('#jform_nama').removeAttr('readonly');
					$('#jform_nama').inputmask({ mask: 'UU D\\arur\\at No. 9{1,4} T\\ahun 9{4}[ P\\as\\al Q{1,10}][ \\Ay\\at 9{1,4}]', greedy: false, clearIncomplete: true });
					if(pasal.indexOf('UU Darurat No.') > -1) {
						$('#jform_nama').val(pasal);
					}
					break;
				default:
					$('#jform_nama').inputmask('remove');
					$('#jform_nama').attr('readonly');
					break;
			}
		}

		$('#jform_id_kasus_pasal_tipe').change(function() {
			setPasalMask();
		});
	});

	
})(jQuery);
