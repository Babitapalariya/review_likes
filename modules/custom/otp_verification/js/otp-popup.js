(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.otpModal = {
    attach: function (context, settings) {
      // Store form data when first submitted
      let formData = null;
      let $form = null;

      // Try multiple form selectors
      $form = $('#user-register-form, form.user-register-form, form[data-drupal-selector="user-register-form"]').first();

      if ($form.length === 0) {
        console.error('No form found! Check your selector');
        return;
      }

      if ($form.hasClass('js-otp-processed')) {
        return;
      }
      $form.addClass('js-otp-processed');

      // Bind submit handler
      $form.on('submit', function(e) {
        e.preventDefault();
        
        // Store form data for later submission
        formData = $(this).serialize();
        const email = $(this).find('input[name="mail"]').val();

        // Generate and send OTP
        $.ajax({
          url: '/otp/send',
          type: 'POST',
          data: { email: email },
          success: function() {
            showOtpModal(email);
          }
        });
      });

      function showOtpModal(email) {
        // Create and show modal
        const modal = `
          <div class="modal fade" id="otp-verification-modal" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">OTP Verification</h5>
                </div>
                <div class="modal-body">
                  <p>Enter the 6-digit OTP sent to ${email}</p>
                  <input type="text" id="otp-input" maxlength="6" class="form-control" placeholder="000000">
                </div>
                <div class="modal-footer">
                  <button id="resend-otp-btn" class="btn btn-secondary">
                    Resend OTP
                  </button>
                  <button id="verify-otp-btn" class="btn btn-primary">
                    Verify OTP
                  </button>
                </div>
              </div>
            </div>
          </div>
        `;
        
        $('body').append(modal);
        $('#otp-verification-modal').modal({backdrop: 'static', keyboard: false}).modal('show');

        // Start expiry timer (1 hour)
        const expiryTime = new Date().getTime() + 3600000;
        
        // Update resend button visibility every minute
        const expiryInterval = setInterval(function() {
          if (new Date().getTime() >= expiryTime) {
            $('#resend-otp-btn').show();
            clearInterval(expiryInterval);
          }
        }, 60000);
      }

      // Verify OTP handler
      $(document).on('click', '#verify-otp-btn', function() {
        const otp = $('#otp-input').val();
        const email = $('#edit-mail').val();
        
        if (otp.length !== 6) {
          alert('Please enter a 6-digit OTP');
          return;
        }

        // console.log('[15] Sending OTP verification request');
     $.ajax({
          url: '/otp/verify',
          type: 'POST',
          data: { email: email, otp: otp },
          success: function(response) {
            // console.log('[16] OTP verification response:', response);
            
            if (response.status === 'verified') {
              alert('OTP verified successfully');
              $('#otp-verification-modal').modal('hide');
              // console.log('[18] Modal hidden. Reloading page...');
              document.getElementById('user-register-form').submit();
              
              // window.location.reload();
            } else {
              // console.warn('[19] OTP verification failed:', response.status);
              alert('OTP verification failed: ' + response.status);
            }
          },
          error: function(xhr, status, error) {
            // console.error('[ERROR] OTP verification failed:', status, error);
          }
        });
      });

      // Resend OTP handler
      $(document).on('click', '#resend-otp-btn', function() {
        const email = $('#edit-mail').val();
        
        $.ajax({
          url: '/otp/resend',
          type: 'POST',
          data: { email: email },
          success: function() {
            alert('New OTP sent to your email');
            $('#resend-otp-btn').hide();
            $('#otp-input').val('');
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);