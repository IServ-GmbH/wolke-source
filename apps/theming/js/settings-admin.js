/**
 * @author Björn Schießle <bjoern@schiessle.org>
 *
 * @copyright Copyright (c) 2016, Bjoern Schiessle
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your opinion) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

function startLoading() {
	OC.msg.startSaving('#theming_settings_msg');
	$('#theming_settings_loading').show();
}

function setThemingValue(setting, value) {
	startLoading();
	$.post(
		OC.generateUrl('/apps/theming/ajax/updateStylesheet'), {'setting' : setting, 'value' : value}
	).done(function() {
		hideUndoButton(setting, value);
		preview(setting, value);
	}).fail(function(response) {
		OC.msg.finishedSaving('#theming_settings_msg', response.responseJSON);
		$('#theming_settings_loading').hide();
	});
}

function preview(setting, value, serverCssUrl) {
	OC.msg.startAction('#theming_settings_msg', t('theming', 'Loading preview…'));

	// Get all theming themes css links and force reload them
	[...document.querySelectorAll('link.theme')]
		.forEach(theme => {
			// Only edit the clone to not remove applied one
			var clone = theme.cloneNode()
			var url = new URL(clone.href)
			// Set current timestamp as cache buster
			url.searchParams.set('v', Date.now())
			clone.href = url.toString()
			clone.onload = function() {
				theme.remove()
			}
			document.head.append(clone)
		})

	if (setting === 'name') {
		window.document.title = t('core', 'Admin') + " - " + value;
	}
	
	// Finish
	$('#theming_settings_loading').hide();
	var response = { status: 'success', data: {message: t('theming', 'Saved')}};
	OC.msg.finishedSaving('#theming_settings_msg', response);
	hideUndoButton(setting, value);
}

function hideUndoButton(setting, value) {
	var themingDefaults = {
		name: 'Nextcloud',
		slogan: t('lib', 'a safe home for all your data'),
		url: 'https://nextcloud.com',
		color: '#0082c9',
		logoMime: '',
		backgroundMime: '',
		imprintUrl: '',
		privacyUrl: ''
	};

	if (value === themingDefaults[setting] || value === '') {
		$('.theme-undo[data-setting=' + setting + ']').hide();
	} else {
		$('.theme-undo[data-setting=' + setting + ']').show();
	}

	if(setting === 'backgroundMime' && value !== 'backgroundColor')  {
		$('.theme-remove-bg').show();
	}
	if(setting === 'backgroundMime' && value === 'backgroundColor')  {
		$('.theme-remove-bg').hide();
		$('.theme-undo[data-setting=backgroundMime]').show();
	}
}

window.addEventListener('DOMContentLoaded', function () {
	$('#theming [data-toggle="tooltip"]').tooltip();

	// manually instantiate jscolor to work around new Function call which violates strict CSP
	var colorElement = $('#theming-color')[0];
	colorElement.setAttribute('aria-expanded', 'false');
	var jscolor = new window.jscolor(colorElement, {hash: true});

	$('#theming .theme-undo').each(function() {
		var setting = $(this).data('setting');
		var value = $('#theming-'+setting).val();
		hideUndoButton(setting, value);
	});

	$('.fileupload').fileupload({
		pasteZone: null,
		dropZone: null,
		done: function (e, response) {
			var $form = $(e.target).closest('form');
			var key = $form.data('image-key');

			preview(key + 'Mime', response.result.data.name, response.result.data.serverCssUrl);
			$form.find('.image-preview').css('backgroundImage', response.result.data.url + '?v=' + new Date().getTime());
			OC.msg.finishedSaving('#theming_settings_msg', response.result);
			$form.find('label.button').addClass('icon-upload').removeClass('icon-loading-small');
			$form.find('.theme-undo').show();
		},
		submit: function(e, response) {
			var $form = $(e.target).closest('form');
			var key = $form.data('image-key');
			startLoading();
			$form.find('label.button').removeClass('icon-upload').addClass('icon-loading-small');
		},
		fail: function (e, response){
			var $form = $(e.target).closest('form');
			const responseJSON = response._response.jqXHR.responseJSON;
			OC.msg.finishedError('#theming_settings_msg', responseJSON && responseJSON.data && responseJSON.data.message ? responseJSON.data.message : t('theming', 'Error uploading the file'));
			$form.find('label.button').addClass('icon-upload').removeClass('icon-loading-small');
			$('#theming_settings_loading').hide();
		}
	});

	// clicking preview should also trigger file upload dialog
	$('#theming-preview-logo').on('click', function(e) {
		e.stopPropagation();
		$('#uploadlogo').click();
	});
	$('#theming-preview').on('click', function() {
		$('#upload-login-background').click();
	});

	function checkName () {
		var length = $('#theming-name').val().length;
		try {
			if (length > 0) {
				return true;
			} else {
				throw t('theming', 'Name cannot be empty');
			}
		} catch (error) {
			$('#theming-name').attr('title', error);
			$('#theming-name').tooltip({placement: 'top', trigger: 'manual'});
			$('#theming-name').tooltip('_fixTitle');
			$('#theming-name').tooltip('show');
			$('#theming-name').addClass('error');
		}
		return false;
	}

	$('#theming-name').keyup(function() {
		if (checkName()) {
			$('#theming-name').tooltip('hide');
			$('#theming-name').removeClass('error');
		}
	});

	$('#theming-name').change(function(e) {
		var el = $(this);
	});

	$('#userThemingDisabled').change(function(e) {
		var checked = e.target.checked
		setThemingValue('disable-user-theming', checked ? 'yes' : 'no')
	});

	function onChange(e) {
		var el = $(this);
		var setting = el.parent().find('div[data-setting]').data('setting');
		var value = $(this).val();

		if(setting === 'color') {
			if (value.indexOf('#') !== 0) {
				value = '#' + value;
			}
		}
		if(setting === 'name') {
			if(checkName()){
				$.when(el.focusout()).then(function() {
					setThemingValue('name', value);
				});
				if (e.keyCode == 13) {
					setThemingValue('name', value);
				}
			}
		}

		$.when(el.focusout()).then(function() {
			setThemingValue(setting, value);
		});
		if (e.keyCode == 13) {
			setThemingValue(setting, value);
		}
	};

	$('#theming input[type="text"]').change(onChange);
	$('#theming input[type="url"]').change(onChange);

	$('.theme-undo').click(function (e) {
		var setting = $(this).data('setting');
		var $form = $(this).closest('form');
		var image = $form.data('image-key');

		startLoading();
		$('.theme-undo[data-setting=' + setting + ']').hide();
		$.post(
			OC.generateUrl('/apps/theming/ajax/undoChanges'), {'setting' : setting}
		).done(function(response) {
			if (setting === 'color') {
				var colorPicker = document.getElementById('theming-color');
				colorPicker.style.backgroundColor = response.data.value;
				colorPicker.value = response.data.value.slice(1).toUpperCase();
			} else if (!image) {
				var input = document.getElementById('theming-'+setting);
				input.value = response.data.value;
			}
			preview(setting, response.data.value, response.data.serverCssUrl);
		});
	});

	$('.theme-remove-bg').click(function() {
		startLoading();
		$.post(
			OC.generateUrl('/apps/theming/ajax/updateStylesheet'), {'setting' : 'backgroundMime', 'value' : 'backgroundColor'}
		).done(function(response) {
			preview('backgroundMime', 'backgroundColor', response.data.serverCssUrl);
		}).fail(function(response) {
			OC.msg.finishedSaving('#theming_settings_msg', response);
			$('#theming_settings_loading').hide();
		});
	});

});
