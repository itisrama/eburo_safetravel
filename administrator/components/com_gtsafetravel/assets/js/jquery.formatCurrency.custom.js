// Initiate currency locale
(function($) {
	$.formatCurrency.regions['custom'] = {
		symbol: 'Rp ',
		positiveFormat: '%s%n',
		negativeFormat: '(%s%n)',
		decimalSymbol: ',',
		digitGroupSymbol: '.',
		groupDigits: true,
		roundToDecimalPlace: 0
	};

	$.formatCurrency.regions['numeric'] = {
		symbol: '',
		positiveFormat: '%s%n',
		negativeFormat: '-%s%n',
		groupDigits: false,
		roundToDecimalPlace: -1
	};
})(jQuery);