/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

(function($, OC) {

	$(document).ready(function() {

		$('.subscription-toggle-subscription-key').on('click', function(e) {
			$('#subscription-key-section').removeClass('hidden');
		});

		$('#generate-report-button').on('click', function(e) {
			e.target.disabled = true;
			var $reportStatus = $('#report-status');

			$reportStatus.html('');
			$reportStatus.addClass('icon-loading');
			$.post(OC.generateUrl('apps/support/generateSystemReport?forceLanguage=en'))
				.always(function() {
					e.target.disabled = false;
					$reportStatus.removeClass('icon-loading');
				})
				.done(function(data) {
					var link = data.link;
					var password = data.password;
					var $link = $('<a>')
						.attr('href', link)
						.attr('target', '_blank')
						.html(link);

					$reportStatus.append(t('support', 'Link:') + ' ');
					$reportStatus.append($link);
					$reportStatus.append('<br />' + t('support', 'Password:') + ' ' + '<code>' + password + '</code>');
				})
				.fail(function(xhr) {
					var message = xhr.responseJSON.message;
					$reportStatus.html(t('support', 'Generating system report failed.') + ' ' + message);
				})
		});
	});
})(jQuery, OC);
