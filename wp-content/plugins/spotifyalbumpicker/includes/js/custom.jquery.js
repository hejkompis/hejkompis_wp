// När sidan laddat klart
jQuery(document).ready(function() {

	jQuery(document).on('keydown', 'input.testdrive-postcode', function(e) {
		if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
             // Allow: Ctrl+A
            (e.keyCode == 65 && e.ctrlKey === true) ||
             // Allow: Ctrl+C
            (e.keyCode == 67 && e.ctrlKey === true) ||
             // Allow: Ctrl+X
            (e.keyCode == 88 && e.ctrlKey === true) ||
             // Allow: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
                 // let it happen, don't do anything
                 return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }

		var addressVal = jQuery(this).val();        

		if(addressVal.length == 5) {
			e.preventDefault();
		}

	});

	// när man skickar formuläret med postnummer
	jQuery(document).on('submit', 'form.testdrive-form', function(e) {

		e.preventDefault();

		$this_form = jQuery(this);
		$this_form.find('input[type="submit"]').val('Skickar...');

		$error = false;

		if($this_form.find('select.testdrive-retailer-list').val() == '0') { $this_form.find('select.testdrive-retailer-list').addClass('error'); $error = true; } else { $this_form.find('select.testdrive-retailer-list').removeClass('error'); }
		if($this_form.find('input.testdrive-input-firstname').val() == '') { $this_form.find('input.testdrive-input-firstname').addClass('error'); $error = true; } else { $this_form.find('input.testdrive-input-firstname').removeClass('error'); }
		if($this_form.find('input.testdrive-input-lastname').val() == '') { $this_form.find('input.testdrive-input-lastname').addClass('error'); $error = true; } else { $this_form.find('input.testdrive-input-lastname').removeClass('error'); }
		if($this_form.find('input.testdrive-input-email').val() == '') { $this_form.find('input.testdrive-input-email').addClass('error'); $error = true; } else { $this_form.find('input.testdrive-input-email').removeClass('error'); }
		if($this_form.find('input.testdrive-input-phone').val() == '') { $this_form.find('input.testdrive-input-phone').addClass('error'); $error = true; } else { $this_form.find('input.testdrive-input-phone').removeClass('error'); }
		if($this_form.find('input.testdrive-input-carmodel').val() == '') { $this_form.find('input.testdrive-input-carmodel').addClass('error'); $error = true; } else { $this_form.find('input.testdrive-input-carmodel').removeClass('error'); }

		if($error) {

			$this_form.closest('.testdrive-content').find('.testdrive-form-message').html('Alla fält måste fyllas i.');
			$this_form.find('input[type="submit"]').val('Boka provkörning');
			return false;

		}

		else {

			$this_form.closest('.testdrive-content').find('.testdrive-form-message').html('');

		}

		if($this_form.find('input.testdrive-input-newsletter:checked').length === 0) {
			$newsletter = false;
		}
		else {
			$newsletter = 'on';
		}

		var inputData = {
			'action': 'testdrive_json',
			'retailer': $this_form.find('select.testdrive-retailer-list').val(),
			'firstname': $this_form.find('input.testdrive-input-firstname').val(),
			'lastname': $this_form.find('input.testdrive-input-lastname').val(),
			'phone': $this_form.find('input.testdrive-input-phone').val(),
			'email': $this_form.find('input.testdrive-input-email').val(),
			'carmodel': $this_form.find('input.testdrive-input-carmodel').val(),
			'newsletter': $newsletter,
			'campaign': $this_form.find('input.testdrive-input-campaign').val()
		}

		// använd WPs inbyggda ajaxfunktion för att hämta data
		jQuery.post(
			ajax_url,
			inputData, 
			function(data) {  

				//console.log(data);
				$this_form.find('input[type="submit"]').val('Boka provkörning');

				// spara till konsolen för att se vad som returneras, bortkommenterad vid skarp körning
	    		if(data.success) {                      
    			
	    			$this_form.hide().find('input').val('');

	    			$this_form.closest('.testdrive-content').find('.testdrive-form-message').html("Tack för din bokning, vi återkommer snarast!");

				}

				else {

					$this_form.closest('.testdrive-content').find('.testdrive-form-message').html("Något gick fel. Vänligen försök igen om en liten stund.");

				}

    		}
    	
    	)		
	
	});

	jQuery(document).on('click', '.close-element', function(e) {

		e.preventDefault();

		closetestdrive(jQuery(this));

		return false;

	});

});

function closetestdrive(e) {

	$this = e;
	$this_wrap = e.closest('.testdrive-wrap');

	// Fadea ut hela boxen
	$this_wrap.fadeOut(function() {

		placeholderText = $this_wrap.find('.testdrive-visible-content').attr('data-placeholder');

		// Ta bort .active från testdrive-element
		$this_wrap.removeClass('active');
		$this_wrap.find('.testdrive-content, .testdrive-visible-content, .testdrive-hidden-content').removeClass('active');
		// Ta bort .testdrive-active från huvudelement (ta bort mobilfixering)
		jQuery('html, body, div#page-container').removeClass('testdrive-active');
		// Dölj hidden-content igen
		$this_wrap.find('.testdrive-hidden-content').css('display', 'none');
		// Återställ kontent i #testdrive-visible-content
		$this_wrap.find('.testdrive-visible-content').html('<form class="testdrive-form" method="post" action=""><input type="tel" name="testdrive-input" class="testdrive-input" placeholder="'+placeholderText+'" autocomplete="off" /><input type="submit" class="testdrive-submit" value="Sök" disabled="disabled"/></form>');

		// Fadea in #testdrive-wrap
		$this_wrap.fadeIn();

	});	

}