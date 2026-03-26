(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.otpVerification = {
    attach: function(context, settings) {
      // console.log('OTP Verification script loaded');
      
      // Use event delegation for better performance
      $(document).on('click', '#edit-button', function(e) {
        // console.log('Generate OTP button clicked');
        e.preventDefault();
        e.stopPropagation();
        Drupal.behaviors.otpVerification.generateOtp();
        return false;
      });

      // Create modal only once
      if ($('#otp-verification-modal').length === 0) {
        this.createOtpModal();
      }

      // Modal submit button handler
      $(document).on('click', '#modal-submit-otp', function(e) {
        e.preventDefault();
        Drupal.behaviors.otpVerification.verifyOtpThroughModal();
      });
    },
    
    createOtpModal: function() {
      // console.log('Creating OTP modal');
      
      if ($('#otp-verification-modal').length > 0) {
        return;
      }
      
      var modalHtml = `
        <div id="otp-verification-modal" class="modal fade" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">${Drupal.t('OTP Verification')}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <p>${Drupal.t('Please verify your phone number with OTP')}</p>
                <div class="form-group">
                  <label for="modal-otp-input">${Drupal.t('Enter OTP:')}</label>
                  <input type="text" class="form-control" id="modal-otp-input" placeholder="${Drupal.t('Enter OTP received on your phone')}">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">${Drupal.t('Cancel')}</button>
                <button type="button" class="btn btn-primary" id="modal-submit-otp">${Drupal.t('Verify OTP')}</button>
              </div>
            </div>
          </div>
        </div>
      `;
      
      $('body').append(modalHtml);
    },
    
    showOtpModal: function() {
      $('#otp-verification-modal').modal('show');
    },
    
    generateOtp: function() {
      // console.log('generateOtp function called');
      
      // FIND THE CORRECT PHONE FIELD SELECTOR
      // Look in your HTML for the actual phone input field
      // It might be something like: #edit-phone, #edit-mobile, #edit-phone-number, etc.
      var phoneNumber = $('#edit-phone').val(); // CHANGE THIS TO THE CORRECT SELECTOR
      
      // Get country code from dropdown
      var countryCode = $('#edit-input').val();
      
      // console.log('Phone number:', phoneNumber);
      // console.log('Country code:', countryCode);
      
      if (!phoneNumber) {
        alert(Drupal.t('Please enter a phone number first'));
        return;
      }
      
      var fullNumber = countryCode + phoneNumber;
      // console.log('Full number:', fullNumber);
      
      // Get CSRF token
      var csrfToken = drupalSettings.ajaxPageState ? drupalSettings.ajaxPageState.token : '';
      
      // Show loading state
      var $button = $('#edit-button');
      var originalText = $button.val();
      $button.prop('disabled', true).val(Drupal.t('Generating...'));
      
      // AJAX call to generate OTP
      $.ajax({
        url: Drupal.url('mobile-otp/generate'),
        type: 'POST',
        data: {
          mobile_number: fullNumber,
          form_id: 'profile_completion_form',
          token: csrfToken
        },
        success: function(response) {
          // console.log('Success response:', response);
          if (response.success) {
            alert(Drupal.t('OTP sent to your phone number'));
            Drupal.behaviors.otpVerification.showOtpModal();
            $('#modal-otp-input').focus();
          } else {
            alert(Drupal.t('Error: ') + (response.error || Drupal.t('Unknown error')));
          }
          $button.prop('disabled', false).val(originalText);
        },
        error: function(xhr, status, error) {
          // console.log('Error details:', {
          //   status: xhr.status,
          //   statusText: xhr.statusText,
          //   responseText: xhr.responseText,
          //   error: error
          // });
          
          alert(Drupal.t('Error generating OTP. Please try again.'));
          $button.prop('disabled', false).val(originalText);
        }
      });
    },
    
    verifyOtpThroughModal: function() {
      var otp = $('#modal-otp-input').val();
      
      // FIND THE CORRECT PHONE FIELD SELECTOR (same as above)
      var phoneNumber = $('#edit-phone').val(); // CHANGE THIS TO THE CORRECT SELECTOR
      var countryCode = $('#edit-input').val();
      var fullNumber = countryCode + phoneNumber;
      
      if (!otp) {
        alert(Drupal.t('Please enter the OTP'));
        return;
      }
      
      // Get CSRF token
      var csrfToken = drupalSettings.ajaxPageState ? drupalSettings.ajaxPageState.token : '';
      
      $('#modal-submit-otp').prop('disabled', true).text(Drupal.t('Verifying...'));
      
      $.ajax({
        url: Drupal.url('mobile-otp/verify'),
        type: 'POST',
        data: {
          mobile_number: fullNumber,
          otp_code: otp,
          form_id: 'profile_completion_form',
          token: csrfToken
        },
        success: function(response) {
          // console.log('Verify response:', response);
          $('#modal-submit-otp').prop('disabled', false).text(Drupal.t('Verify OTP'));
          //  document.getElementById('profile-completion-form').submit();
          if (response.success) {
          //  alert(Drupal.t('OTP verified successfully!'));
            $('#otp-verification-modal').modal('hide');
            if ($('#otp').length) {
              $('#otp').val(otp);
            }
            // // ✅ Trigger the Complete Profile button
            // $('#edit-submit').trigger('click');  
            // or directly submit the form:
            // $('#edit-submit').closest('form').submit();
          } else {
            alert(Drupal.t('Invalid OTP. Please try again.'));
          }
        },
        error: function(xhr, status, error) {
          // console.log('Verify error:', {
          //   status: xhr.status,
          //   statusText: xhr.statusText,
          //   responseText: xhr.responseText,
          //   error: error
          // });
          $('#modal-submit-otp').prop('disabled', false).text(Drupal.t('Verify OTP'));
          alert(Drupal.t('Error verifying OTP. Please try again.'));
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);