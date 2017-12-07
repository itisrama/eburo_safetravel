/*
Input Mask plugin extensions
Copyright (c) 2015 - Yudhistira Ramadhan
Licensed under the MIT license (http://www.opensource.org/licenses/mit-license.php)
Version: 0.0.0-dev

Optional extensions on the jquery.inputmask base
*/
(function($) {
	//date & time aliases
	inputmask.extendDefinitions({
		'V': { //hours
			validator: "^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$",
			cardinality: 1
		}
	});

	return inputmask;
})(jQuery);