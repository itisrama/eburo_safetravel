/**
 * @package		Joomla.JavaScript
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

(function($) {
	$.JTableGrid = function(elid, names, maximum) {
		var table,
		el = null,
		form, toggle, table, trCopy, tbRows, tbNoItem, rowIndex
		/**
		 * Delegate window add/remove events
		 */
		watchButtons = function() {
			var type, input, inputval, inputdisplay, rowcount;

			rowcount = $('tr', tbRows).length;
			toggle.click(function(){
				$('input, select, textarea', form).removeClass('invalid');
				$('h4.new', form).show();
				$('h4.edit', form).hide();
				resetForm(form, names);
				form.modal('show');
			})

			table.on('click', '.delItem', function(e) {
				var isDelete = confirm(Joomla.JText._('COM_GTPROJPLAN_CONFIRM_DELETE'));
				if (tr = findTr(e)) {
					if(isDelete) {
						tr.remove();
						rowcount--;
						toggleRow(rowcount, tbRows, tbNoItem);
						store(el, names, tbRows);
					}
				}
			}.bind(this));

			table.on('click', '.editItem', function(e) {
				$('input, select, textarea', form).removeClass('invalid');
				if (tr = findTr(e)) {
					$.each(names, function(index, k) {
						input = $('.inputField *[name$="' + k + ']"]', form).eq(0);
						inputval = $('.' + k + ' input', tr).val();
						type = input.attr('type');
						fieldset = input.closest('fieldset');

						inputval = inputval.split('|');
						
						if (type === 'radio' || type === 'checkbox') {
							$.each(inputval, function(r, s) {
								s = s.split(':')[0];
								$('input[value="'+s+'"]', fieldset).closest('label').trigger('click');
							});
						} else if (input.prop('tagName') === 'SELECT') {
							if(input.hasClass('selectized')) {
								var selectizeinput = $('#' + input.attr('id'), fieldset)[0];
								selectizeinput.selectize.clear();
								$.each(inputval, function(r, s) {
									var selectizeval = s.split(':')[0];
									var selectizetext = s.split(':')[1] ? s.split(':')[1] : s.split(':')[0];
									selectizeinput.selectize.addOption({id:selectizeval,name:selectizetext});
									selectizeinput.selectize.addItem(selectizeval);
								});
							} else {
								$.each(inputval, function(r, s) {
									inputval[r] = s.split(':')[0];
								});
								input.val(inputval).trigger('change');
							}
						} else {
							input.val(inputval);
						}
					});
					tr.addClass('info');
					rowIndex.val($('tr', tbRows).index(tr));
					$('.addItem', form).hide();
					$('.saveItem', form).show();
					$('h4.new', form).hide();
					$('h4.edit', form).show();
					form.modal('show');

				}
			}.bind(this));

			$('.saveItem', form).click(function(e) {
				if(validate(form)) {
					tr = $('tr', tbRows).eq(rowIndex.val());
					$('td', tr).css('backgroundColor', '');

					bindRow(tr, names, form);

					$('.addItem', form).show();
					$('.saveItem', form).hide();

					store(el, names, tbRows);
					form.modal('hide');
					$('tr', table).removeClass('info');
				}
			});

			$('.addItem', form).click(function(e) {
				if(validate(form)) {
					clone = trCopy.clone();
					bindRow(clone, names, form);
					clone.show();
					clone.appendTo(tbRows);
					rowcount++;
					toggleRow(rowcount, tbRows, tbNoItem);
					store(el, names, tbRows);
					form.modal('hide');
				}
			});

			$('.cancelItem', form).click(function(e) {
				form.modal('hide');
				$('tr', table).removeClass('info');
			});

			$('.close', form).click(function(){
				$('tr', table).removeClass('info');
			});
		};

		validate = function(form) {
			$('.inputField [tablegrid="required"]', form).addClass('required');
			var validation = document.formvalidator.isValid(form);
			$('.inputField [tablegrid="required"]', form).removeClass('required');
			$('.alert .close').trigger('click');
			return validation;
		}

		bindRow = function(rowTarget, names, form) {
			var type, input, inputval, inputdisplay;

			$.each(names, function(index, k) {
				input = $('.inputField *[name*="' + k + ']"]', form).eq(0);

				type = input.attr('type');

				if (type === 'radio' || type === 'checkbox') {
					inputval = [];
					inputdisplay = [];
					$('input:checked', input.parent().parent()).each(function(k,i){
						var display = $(this).parent('label').text();
						inputval[k] = $(this).val()+':'+display;
						inputdisplay[k] = display;
					});
					inputval = inputval.join('|');
					inputdisplay = inputdisplay.join('<br>');
				} else if (input.prop('tagName') === 'SELECT') {
					if(input.hasClass('selectized')) {
						selectizeval = $('#' + input.attr('id'))[0].selectize.getValue();
						if($.type(selectizeval) == 'string') {
							selectizeval = selectizeval.split();
						}
						inputval = [];
						inputdisplay = [];
						$.each(selectizeval, function(k, val) {
							var selectizedisplay = $('.selectize-input [data-value="'+val+'"]', input.next()).text();
							inputval[k] = val+':'+selectizedisplay;
							inputdisplay[k] = selectizedisplay
						});
						inputval = inputval.join('|');
						inputdisplay = inputdisplay.join('<br>');
					} else {
						inputval = [];
						inputdisplay = [];
						$('option:selected', input).each(function(k, i){
							inputval[k] = $(this).val()+':'+$(this).text();
							inputdisplay[k] = $(this).text();
						})
						inputval = inputval.join('|');
						inputdisplay = inputdisplay.join('<br>');
					}
				} else {
					inputval = input.val();
					inputdisplay = inputval.replace(/\n/g, "<br>");
				}
				rowTarget.find('.' + k + ' input').val(inputval);
				rowTarget.find('.' + k + ' span').html(inputdisplay);
			});
		}

		resetForm = function(form, names) {
			var type, input;

			$.each(names, function(index, k) {
				input = form.find('.inputField.reset *[name*="' + k + ']"]');
				type = input.attr('type');
				if (type === 'radio' || type === 'checkbox') {
					input.parent('label').removeClass(function(index, css) {
						return (css.match(/\bbtn-\S+/g) || []).join(' ');
					});
					input.parent('label').removeClass('active').addClass('btn-default');
					input.removeAttr('checked');
				} else {
					if (input.prop('tagName') === 'SELECT') {
						if(input.hasClass('selectized')) {
							$('#' + input.attr('id'))[0].selectize.clear();
						} else {
							input.val(input.find('option').first().val());
						}
					} else {
						input.val('').trigger('change');
					}
				}
			});

			$('.addItem', form).show();
			$('.saveItem', form).hide();
		}

		/**
		 * Get the <tr> from the event
		 *
		 * @param   Event  e  click event for add/remove
		 *
		 * @return  DOM Node <tr> or false
		 */
		findTr = function(e) {
			var tr = e.target.getParents().filter(function(p) {
				return p.get('tag') === 'tr';
			});
			return (tr.length === 0) ? false : $(tr[0]);
		};

		toggleRow = function(rowcount, tbRows, tbNoItem) {
			if (rowcount > 0) {
				tbRows.show();
				tbNoItem.hide();
			} else {
				tbRows.hide();
				tbNoItem.show();
			}
			table.sortable("refresh");
		}

		/**
		 * Create <tr>'s from the hidden fields JSON and the template HTML
		 */
		build = function() {
			var clone, a, keys, type, input, inputval, inputdisplay, rowcount;

			el = $('#' + elid);

			form = $('#' + elid + '_form');
			toggle = $('#' + elid + '_toggle');
			table = $('#' + elid + '_table');
			trCopy = table.find('thead tr.rowItem');
			tbRows = table.find('tbody.dataRows');
			tbNoItem = table.find('tbody.noItem');
			rowIndex = $('#' + elid + '_editIndex')

			// decode JSON
			a = JSON.decode(el.val());
			if (typeOf(a) === 'null' || a.length === 0) {
				a = {};
			} else {
				keys = Object.keys(a);
				rowcount = a[keys[0]].length;
			}

			// Build the rows from the json object
			for (var i = 0; i < rowcount; i++) {
				clone = trCopy.clone();
				$.each(keys, function(index, k) {
					input = form.find('.inputField *[name*="' + k + ']"]');
					inputval = a[k][i];
					type = input.attr('type');

					if (type === 'radio' || type === 'checkbox' || input.prop('tagName') === 'SELECT') {
						inputdisplay = inputval !== null ? inputval.split('|') : [];
						$.each(inputdisplay, function(r, s) {
							inputdisplay[r] = s.split(':')[1] ? s.split(':')[1] : s.split(':')[0];
						});
						inputdisplay = inputdisplay.join('<br>');

					} else {
						inputdisplay = inputval ? inputval.replace(/\n/g, "<br>") : inputval;
					}
					clone.find('.' + k + ' input').val(inputval);
					clone.find('.' + k + ' span').html(inputdisplay);
					clone.show();
				});
				clone.appendTo(tbRows);
			}
			table.sortable({
				containerSelector: 'table',
				itemPath: '> tbody',
				itemSelector: 'tr',
				placeholder: '<tr class="placeholder" />'
			});
			toggleRow(rowcount, tbRows, tbNoItem);
		};

		/**
		 * Save the window fields back to the hidden element field (stored as JSON)
		 */
		store = function(el, names, tbRows) {
			var i, row, type, json = {}, result, rowcount

			rowcount = $('tr', tbRows).length;
			if(!rowcount>0) {
				el.val(null);
				return true;
			}
			$.each(names, function(index, k) {
				json[k] = [];
			});

			for (var i = 0; i < rowcount; i++) {
				row = $('tr', tbRows).eq(i);
				$.each(names, function(index, k) {
					json[k].push(row.find('.' + k + ' input').val());
				});
			};
			// Store them in the parent field.
			result = JSON.encode(json);

			el.val(result);
			return true;
		};

		/**
		 * Main click event on 'Select' button to open the window.
		 */

		build();
		watchButtons();
	}

})(jQuery);