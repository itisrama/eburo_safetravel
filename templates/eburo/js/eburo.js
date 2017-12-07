/** 
 *------------------------------------------------------------------------------
 * @package       T3 Framework for Joomla!
 *------------------------------------------------------------------------------
 * @copyright     Copyright (C) 2004-2013 JoomlArt.com. All Rights Reserved.
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 * @authors       JoomlArt, JoomlaBamboo, (contribute to this project at github 
 *                & Google group to become co-author)
 * @Google group: https://groups.google.com/forum/#!forum/t3fw
 * @Link:         http://t3-framework.org 
 *------------------------------------------------------------------------------
 */

jQuery.noConflict();
(function($) {
	$(document).ready(function() {
		//$(document).off('click.bs.dropdown.data-api');

		$('.hasTooltip').tooltip({container: 'body'});

		$('.t3-nav-sidebar li.current').parents('li').addClass('open');

		$('.btn-collapse').click(function(){
			var navbarWidthOpen = $('.t3-mainnav .t3-navbar').width();
			var navbarWidthClose = '0px';

			if($('.t3-navbar').is(':visible')) {
				$('.t3-mainnav').animate({width: navbarWidthClose});
				$('.t3-page-wrapper').animate({'margin-left': navbarWidthClose});
				$('.t3-footer').animate({'margin-left': navbarWidthClose});
				$('.t3-page-wrapper .blocker').fadeOut();
				$('.t3-navbar').fadeOut();
				fixTableResponsive();
			} else {
				$('.t3-mainnav').animate({width: navbarWidthOpen});
				$('.t3-page-wrapper').animate({'margin-left': navbarWidthOpen});
				$('.t3-footer').animate({'margin-left': navbarWidthOpen});
				$('.t3-page-wrapper .blocker').fadeIn();
				$('.t3-navbar').fadeIn();
				fixTableResponsive();

				$('.t3-page-wrapper').width('');
				$('.t3-page-wrapper').width($('.t3-mainbody').width());
			}
		});

		fixTableResponsive();
		function fixTableResponsive() {
			$('.t3-content .table-responsive').css('maxWidth', $('.t3-content').width());
		}

		function setHeight() {
			var windowHeight = $(window).innerHeight();
			var footerHeight = $('.t3-footer').outerHeight();
			if($('html').hasClass('guest')) {
				var marginGuest = windowHeight - footerHeight - $('.guest-wrap').outerHeight();
				marginGuest = marginGuest/2;
				marginGuest = marginGuest > 10 ? marginGuest : 10;
				$('.guest-wrap').css('margin-top', marginGuest);
				$('.guest-wrap').css('margin-bottom', marginGuest);
			} else {
				var headerHeight = $('.t3-header').height();
				$('.t3-navbar').css('max-height', windowHeight-headerHeight);
				$('.t3-page-wrapper').css('min-height', windowHeight-footerHeight);
			}
			//$('.t3-page-wrapper').width('');
			
		};
		
		setHeight();
		$(window).resize(function() {
			setHeight();
			fixTableResponsive();
			$('.t3-page-wrapper').width('');
			$('.t3-page-wrapper').width($('.t3-mainbody').width());
		});
	});
})(jQuery);