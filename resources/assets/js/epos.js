$(function() {
	$('body').on('ready', '.tender-voucher', function() {
		// Add a voucher returned from a search result
		$(this).on('submit', '.result form.use', function() {
			var self           = $(this),
				targetInput    = $('#' + self.parents('.tender-voucher').attr('data-linked-to'));
				code           = self.parent('.result').find('ul li strong').text(),
				voucherListing = self.parents('.tender-voucher').find('ul.payment-listing');

			// Clear the voucher search box value
			$('#modal-tender .tender-voucher form#find-voucher input[type=text]').val('');

			$.ajax({
				url:        self.attr('action'),
				data:       self.serialize(),
				beforeSend: function() {
					// Disable the button and add a loading graphic
					self.addClass('loading').find('button').attr('disabled', true);
				},
				success:    function(data) {
					var newRow = $('.payment-listing li#voucher-' + code, data.self).hide();

					// Fade in the new row in the list of vouchers
					voucherListing.prepend(newRow);
					voucherListing.find('li#voucher-' + code).fadeIn(300);

					// Fade out the result
					self.parent('.result').slideUp(300, function() {
						$(this).remove();
					});

					// Update the tender & maximum amount
					targetInput.attr('data-maximum-payment', data.maximumPayment);
					targetInput.val(Math.abs(data.tenderAmount).formatMoney(2)).change();
				}
			});

			return false;
		});

		// Remove a voucher that's being used
		$(this).on('submit', 'form.remove', function() {
			var self        = $(this),
				targetInput = $('#' + self.parents('.tender-voucher').attr('data-linked-to'));

			$.ajax({
				url:        self.attr('action'),
				data:       self.serialize(),
				beforeSend: function() {
					// Disable the button and add a loading graphic
					self.addClass('loading').find('button').attr('disabled', true);
				},
				success:    function(data) {
					// Fade out the removed item
					self.parent('li').fadeOut(300, function() {
						$(this).remove();
					});

					// Update the tender & maximum amount
					targetInput.attr('data-maximum-payment', data.maximumPayment);
					targetInput.val(Math.abs(data.tenderAmount).formatMoney(2)).change();
				}
			});

			return false;
		});

		// Search for a voucher
		$(this).on('submit', 'form#find-voucher', function() {
			var self = $(this);

			$.ajax({
				url:        self.attr('action'),
				data:       self.serialize(),
				beforeSend: function() {

				},
				success:    function(data) {
					var voucherDetailHTML = $('.result', data.self).hide();

					// Remove any remaining voucher detail
					self.siblings('.result').remove();

					// Add voucher detail if the voucher was found
					if (voucherDetailHTML.length > 0) {
						self.after(voucherDetailHTML).next('.result').slideDown(300);
					}
				}
			});

			return false;
		});
	});
});