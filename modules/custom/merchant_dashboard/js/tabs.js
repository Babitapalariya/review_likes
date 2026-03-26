(function ($, Drupal) {
  Drupal.behaviors.deleteProduct = {
    attach: function (context, settings) {
      let nidToDelete = null;

      // Open modal when delete link clicked
      $(document).on('click', '.delete-claim-gift', function (e) {
        e.preventDefault();
        nidToDelete = $(this).data('nid');
        // console.log("🟢 Clicked Delete for Node ID:", nidToDelete);
        $('#deletegiftModal').modal('show');
      });

      // Confirm delete
      $(document).on('click', '#confirmDeleteBtn', function () {
        // console.log("🟢 Confirm delete for Node ID:", nidToDelete);

        if (nidToDelete) {
          $.ajax({
            url: Drupal.url('claims-gifts/delete-node/' + nidToDelete),
            type: 'POST',
            data: { _drupal_ajax: true },
            success: function () {
              // console.log("✅ Node deleted successfully:", nidToDelete);
              $('#deletegiftModal').modal('hide');
              location.reload(); // reload list after deletion
            },
            error: function () {
              console.error("❌ Error deleting product:", nidToDelete);
              alert('Error deleting product.');
            }
          });
        }
      });
    }
  };
})(jQuery, Drupal);


(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.couponToggle = {
    attach: function (context, settings) {
      // console.log("=== COUPON TOGGLE DEBUG ===");

      // Use context to ensure we're searching in the right scope
      // Look for the checkbox by its name or class
      const toggle = context.querySelector('input[name="coupon_toggle"]') ||
        context.querySelector('.couponToggle') ||
        document.querySelector('input[name="coupon_toggle"]');

      const field = context.getElementById('paffliatelink') ||
        document.getElementById('paffliatelink');

      // console.log("Toggle found:", toggle);
      // console.log("Field found:", field);

      if (!toggle) {
        console.error("Toggle checkbox not found! Searching for:");
        // console.log("input[name='coupon_toggle']:", context.querySelector('input[name="coupon_toggle"]'));
        // console.log(".couponToggle:", context.querySelector('.couponToggle'));
        return;
      }

      if (!field) {
        console.error("Field not found!");
        return;
      }

      function updateFieldState() {
        const isChecked = toggle.checked;
        // console.log("Toggle state changed:", isChecked);

        // Toggle field properties
        field.disabled = !isChecked;
        field.required = isChecked;

        // Visual feedback
        if (isChecked) {
          field.style.backgroundColor = "#ffffff";
          field.style.opacity = "1";
          field.placeholder = "Enter coupon or affiliate link";
          field.removeAttribute('readonly');
        } else {
          field.style.backgroundColor = "#f8f9fa";
          field.style.opacity = "0.6";
          field.placeholder = "Enable toggle to enter coupon link";
          field.value = '';
        }
      }

      // Initialize
      // console.log("Initial toggle state:", toggle.checked);
      updateFieldState();

      // Add event listeners
      toggle.addEventListener('change', updateFieldState);
      toggle.addEventListener('click', updateFieldState);

      // Also handle slider click
      const slider = context.querySelector('.slider') || document.querySelector('.slider');
      if (slider) {
        slider.addEventListener('click', function (e) {
          e.preventDefault();
          toggle.checked = !toggle.checked;
          const event = new Event('change', { bubbles: true });
          toggle.dispatchEvent(event);
        });
      }

      // console.log("Coupon toggle initialized successfully");
    }
  };

})(jQuery, Drupal);

(function ($, Drupal) {
  Drupal.behaviors.toggleWrapper = {
    attach: function (context, settings) {
      // console.log('here i am..');
      $(".toggle-wrapper.mt-3", context).wrapInner('<label class="toggle-switch"></label>');
    }
  };
})(jQuery, Drupal);

(function($) {
  'use strict';
  
  $(document).ready(function() {
    // Event delegation for dynamic elements
    $(document).on('input', '#edit-order-id', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    $(document).on('input', '#edit-shipping-zip', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    $(document).on('input', '#edit-user-phone', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
  });
  
})(jQuery);


(function($) {
  'use strict';
  
  $(document).ready(function() { 
    
    // Check the form
    var $form = $('#merchant-dashboard-discount-form');   
    
    // Check discount select
    var $select = $('#edit-discount-type');    
    
    // Find all containers
    var $containers = $('[id^="discount-container-"]');    
    $containers.each(function() {
    });    
    // Simple toggle function
    function toggleDiscounts() {
      var value = $select.val();      
      // Hide all
      $containers.hide();      
      // Show selected
      if (value) {
        $('#discount-container-' + value).show();
      }
    }    
    // Bind event
    $select.change(toggleDiscounts);    
    // Initial state
    toggleDiscounts();
  });
  
})(jQuery);

// (function($, Drupal, drupalSettings) {
//   Drupal.behaviors.productCategoryOther = {
//     attach: function(context, settings) {
//       const otherTermId = drupalSettings.merchant_dashboard.other_term_id;
      
//       function toggleOtherField() {
//         const selectedValue = $('input[name="product_category"]:checked', context).val();
//         const $otherField = $('#edit-product-category-other', context);
        
//         // Find the wrapper - try multiple possible selectors
//         const $otherFieldWrapper = $otherField.closest('.js-form-wrapper, .form-text');
        
//         // console.log('Selected value:', selectedValue);
//         // console.log('Other term ID:', otherTermId);
//         // console.log('Wrapper found:', $otherFieldWrapper.length);
        
//         if (selectedValue == otherTermId) {
//           // console.log('Showing other field');
//           // Show the textfield when "Other" is selected
//           $otherFieldWrapper.slideDown(300);
//           $otherField.attr('required', 'required');
//         } else {
//           // console.log('Hiding other field');
//           // Hide the textfield when other options are selected
//           $otherFieldWrapper.slideUp(300);
//           $otherField.removeAttr('required');
//           $otherField.val('');
//         }
//       }
      
//       // Hide the field initially
//       const $otherField = $('#edit-product-category-other', context);
//       const $otherFieldWrapper = $otherField.closest('.js-form-wrapper, .form-text');
//       $otherFieldWrapper.hide();
      
//       // Initialize on page load
//       toggleOtherField();
      
//       // Add change event listener to radio buttons
//       $('input[name="product_category"]', context).on('change', function() {
//         toggleOtherField();
//       });
//     }
//   };
// })(jQuery, Drupal, drupalSettings);


// (function($, Drupal, drupalSettings) {
//   Drupal.behaviors.productCategoryOther = {
//     attach: function(context, settings) {
//       const otherTermId = drupalSettings.merchant_dashboard.other_term_id;
//       const testingOtherId = 24; // Your testing type "Other" value
//       const testerOtherId = 27; // Your tester type "Other" value
//       const genderOtherValue = 'other'; // Gender preference "Other" value
//       const sportsOtherValue = 'other'; 
//       const cameraOtherValue = 'if_yes_smartphone_specs';
//       const outdoorOtherValue = 'outdoor_specific_regions';
//       const safetyOtherValue = 'other';
//       const licensesOtherValue = 'other';
//       const platformOtherValue = 'other';
//       const contentOtherValue = 'other';
      
//       // Function to toggle textfield visibility for all fields
//       function toggleOtherField() {
//         // Handle product category
//         const productSelectedValue = $('input[name="product_category"]:checked', context).val();
//         const $productOtherField = $('#edit-product-category-other', context);
//         const $productOtherFieldWrapper = $productOtherField.closest('.js-form-wrapper, .form-item');
        
//         // Handle testing type
//         const testingSelectedValue = $('input[name="testing_type"]:checked', context).val();
//         const $testingOtherField = $('#edit-testing-type-other', context);
//         const $testingOtherFieldWrapper = $testingOtherField.closest('.js-form-wrapper, .form-item');
        
//         // Handle tester type
//         const testerSelectedValue = $('input[name="tester_type"]:checked', context).val();
//         const $testerOtherField = $('#edit-tester-type-other', context);
//         const $testerOtherFieldWrapper = $testerOtherField.closest('.js-form-wrapper, .form-item');

//         // Handle gender preference
//         const genderSelectedValue = $('input[name="gender_preference"]:checked', context).val();
//         const $genderOtherField = $('#edit-gender-preference-other--2', context);
//         const $genderLabel = $('label[for="edit-gender-preference-other--2"]', context);
//         const $genderDescription = $('#edit-gender-preference-other--2--description', context);
//           // Handle Sportdpreference
//         const sportsSelectedValue = $('input[name="physical_abilities"]:checked', context).val();
//         const $sportsOtherField = $('#edit-sports-specification', context);
//         const $sportsLabel = $('label[for="edit-sports-specification"]', context);
//         const $sportsDescription = $('#edit-sports-specification', context);
//           // Handle camera_requirements
//         const cameraSelectedValue = $('input[name="camera_requirements"]:checked', context).val();
//         const $cameraOtherField = $('#edit-smartphone-specs', context);
//         const $cameraLabel = $('label[for="edit-smartphone-specs"]', context);
//         const $cameraDescription = $('#edit-smartphone-specs', context);

//             // Handle outdoor
//         const outdoorSelectedValue = $('input[name="location_preferences"]:checked', context).val();
//         const $outdoorOtherField = $('#edit-specific-regions', context);
//         const $outdoorLabel = $('label[for="edit-specific-regions"]', context);
//         const $outdoorDescription = $('#edit-specific-regions', context);
        
//              // Handle outdoor
//         const safetySelectedValue = $('input[name="safety_gear_needed"]:checked', context).val();
//         const $safetyrOtherField = $('#edit-safety-gear-other', context);
//         const $safetyLabel = $('label[for="edit-safety-gear-other"]', context);
//         const $safetyDescription = $('#edit-safety-gear-other', context);
//              // Handle licenses
//         const licensesSelectedValue = $('input[name="licenses_certifications"]:checked', context).val();
//         const $licensesOtherField = $('#edit-licenses-other', context);
//         const $licensesLabel = $('label[for="edit-licenses-other"]', context);
//         const $licensesDescription = $('#edit-licenses-other', context);
//              // Handle platform
//         const platformSelectedValue = $('input[name="platform_preferences"]:checked', context).val();
//         const $platformOtherField = $('#edit-platform-other', context);
//         const $platformLabel = $('label[for="edit-platform-other"]', context);
//         const $platformDescription = $('#edit-platform-other', context);
//              // Handle outdoor
//         const contentSelectedValue = $('input[name="content_style"]:checked', context).val();
//         const $contentOtherField = $('#edit-content-style-other--2', context);
//         const $contentLabel = $('label[for="edit-content-style-other--2"]', context);
//         const $contentDescription = $('#edit-content-style-other--2', context);
//         // Toggle product category other field
//         if (productSelectedValue == otherTermId) {
//           $productOtherFieldWrapper.slideDown(300);
//           $productOtherField.attr('required', 'required');
//         } else {
//           $productOtherFieldWrapper.slideUp(300);
//           $productOtherField.removeAttr('required');
//           $productOtherField.val('');
//         }
        
//         // Toggle testing type other field
//         if (testingSelectedValue == testingOtherId) {
//           $testingOtherFieldWrapper.slideDown(300);
//           $testingOtherField.attr('required', 'required');
//         } else {
//           $testingOtherFieldWrapper.slideUp(300);
//           $testingOtherField.removeAttr('required');
//           $testingOtherField.val('');
//         }
        
//         // Toggle tester type other field
//         if (testerSelectedValue == testerOtherId) {
//           $testerOtherFieldWrapper.slideDown(300);
//           $testerOtherField.attr('required', 'required');
//         } else {
//           $testerOtherFieldWrapper.slideUp(300);
//           $testerOtherField.removeAttr('required');
//           $testerOtherField.val('');
//         }

//         // Toggle gender preference other field (special handling)
//         if (genderSelectedValue === genderOtherValue) {
//           // Show all gender other related elements
//           $genderOtherField.stop(true, true).slideDown(300);
//           $genderLabel.stop(true, true).slideDown(300);
//           $genderDescription.stop(true, true).slideDown(300);
//           $genderOtherField.attr('required', 'required');
//         } else {
//           // Hide all gender other related elements
//           $genderOtherField.stop(true, true).slideUp(300);
//           $genderLabel.stop(true, true).slideUp(300);
//           $genderDescription.stop(true, true).slideUp(300);
//           $genderOtherField.removeAttr('required');
//           $genderOtherField.val('');
//         }

//          // Toggle Sports other field (special handling)
//         if (sportsSelectedValue === sportsOtherValue) {
//           // Show all gender other related elements
//           $sportsOtherField.stop(true, true).slideDown(300);
//           $sportsLabel.stop(true, true).slideDown(300);
//           $sportsDescription.stop(true, true).slideDown(300);
//           $sportsOtherField.attr('required', 'required');
//         } else {
//           // Hide all gender other related elements
//           $sportsOtherField.stop(true, true).slideUp(300);
//           $sportsLabel.stop(true, true).slideUp(300);
//           $sportsDescription.stop(true, true).slideUp(300);
//           $sportsOtherField.removeAttr('required');
//           $sportsOtherField.val('');
//         }
//            // Toggle Camera field (special handling)
//         if (cameraSelectedValue === cameraOtherValue) {
//           // Show all gender other related elements
//           $cameraOtherField.stop(true, true).slideDown(300);
//           $cameraLabel.stop(true, true).slideDown(300);
//           $cameraDescription.stop(true, true).slideDown(300);
//           $cameraOtherField.attr('required', 'required');
//         } else {
//           // Hide all gender other related elements
//           $cameraOtherField.stop(true, true).slideUp(300);
//           $cameraLabel.stop(true, true).slideUp(300);
//           $cameraDescription.stop(true, true).slideUp(300);
//           $cameraOtherField.removeAttr('required');
//           $cameraOtherField.val('');
//         }
//         // Toggle Location field (special handling)
//         if (outdoorSelectedValue === outdoorOtherValue) {
//           // Show all gender other related elements
//           $outdoorOtherField.stop(true, true).slideDown(300);
//           $outdoorLabel.stop(true, true).slideDown(300);
//           $outdoorDescription.stop(true, true).slideDown(300);
//           $cameraOtherField.attr('required', 'required');
//         } else {
//           // Hide all gender other related elements
//           $outdoorOtherField.stop(true, true).slideUp(300);
//           $outdoorLabel.stop(true, true).slideUp(300);
//           $outdoorDescription.stop(true, true).slideUp(300);
//           $outdoorOtherField.removeAttr('required');
//           $outdoorOtherField.val('');
//         }
//            // Toggle Location field (special handling)
//         if (safetySelectedValue === safetyOtherValue) {
//           // Show all gender other related elements
//           $outdoorOtherField.stop(true, true).slideDown(300);
//           $outdoorLabel.stop(true, true).slideDown(300);
//           $outdoorDescription.stop(true, true).slideDown(300);
//           $cameraOtherField.attr('required', 'required');
//         } else {
//           // Hide all gender other related elements
//           $outdoorOtherField.stop(true, true).slideUp(300);
//           $outdoorLabel.stop(true, true).slideUp(300);
//           $outdoorDescription.stop(true, true).slideUp(300);
//           $outdoorOtherField.removeAttr('required');
//           $outdoorOtherField.val('');
//         }
//            // Toggle Location field (special handling)
//         if (licensesSelectedValue ===licensesOtherValue) {
//           // Show all gender other related elements
//           $outdoorOtherField.stop(true, true).slideDown(300);
//           $outdoorLabel.stop(true, true).slideDown(300);
//           $outdoorDescription.stop(true, true).slideDown(300);
//           $cameraOtherField.attr('required', 'required');
//         } else {
//           // Hide all gender other related elements
//           $outdoorOtherField.stop(true, true).slideUp(300);
//           $outdoorLabel.stop(true, true).slideUp(300);
//           $outdoorDescription.stop(true, true).slideUp(300);
//           $outdoorOtherField.removeAttr('required');
//           $outdoorOtherField.val('');
//         }
//            // Toggle Location field (special handling)
//         if (platformSelectedValue === platformOtherValue) {
//           // Show all gender other related elements
//           $outdoorOtherField.stop(true, true).slideDown(300);
//           $outdoorLabel.stop(true, true).slideDown(300);
//           $outdoorDescription.stop(true, true).slideDown(300);
//           $cameraOtherField.attr('required', 'required');
//         } else {
//           // Hide all gender other related elements
//           $outdoorOtherField.stop(true, true).slideUp(300);
//           $outdoorLabel.stop(true, true).slideUp(300);
//           $outdoorDescription.stop(true, true).slideUp(300);
//           $outdoorOtherField.removeAttr('required');
//           $outdoorOtherField.val('');
//         }
//            // Toggle Location field (special handling)
//         if (contentSelectedValue === contentOtherValue) {
//           // Show all gender other related elements
//           $outdoorOtherField.stop(true, true).slideDown(300);
//           $outdoorLabel.stop(true, true).slideDown(300);
//           $outdoorDescription.stop(true, true).slideDown(300);
//           $cameraOtherField.attr('required', 'required');
//         } else {
//           // Hide all gender other related elements
//           $outdoorOtherField.stop(true, true).slideUp(300);
//           $outdoorLabel.stop(true, true).slideUp(300);
//           $outdoorDescription.stop(true, true).slideUp(300);
//           $outdoorOtherField.removeAttr('required');
//           $outdoorOtherField.val('');
//         }
//       }
      
//       // Hide all fields initially
//       $('#edit-product-category-other', context).closest('.js-form-wrapper, .form-item').hide();
//       $('#edit-testing-type-other', context).closest('.js-form-wrapper, .form-item').hide();
//       $('#edit-tester-type-other', context).closest('.js-form-wrapper, .form-item').hide();
      
//       // Hide gender field elements individually (since they don't have a common wrapper)
//       $('#edit-gender-preference-other--2', context).hide();
//       $('label[for="edit-gender-preference-other--2"]', context).hide();
//       $('#edit-gender-preference-other--2--description', context).hide();


//        $('#edit-sports-specification', context).hide();
//       $('label[for="edit-sports-specification"]', context).hide();
//       $('#edit-sports-specification', context).hide();
//       // Hide Camera elements individually (since they don't have a common wrapper)
//       $('#edit-smartphone-specs', context).hide();
//       $('label[for="edit-smartphone-specs"]', context).hide();
//       $('#edit-smartphone-specs', context).hide();

//       // Hide Location individually (since they don't have a common wrapper)
//       $('#edit-specific-regions', context).hide();
//       $('label[for="edit-specific-regions"]', context).hide();
//       $('#edit-specific-regions', context).hide();
      
//       // Initialize on page load
//       toggleOtherField();
      
//       // Add change event listener to all radio groups
//       $('input[name="product_category"], input[name="testing_type"], input[name="tester_type"], input[name="gender_preference"], input[name="physical_abilities"], input[name="camera_requirements"], input[name="location_preferences"]', context).on('change', function() {
//         toggleOtherField();
//       });
//     }
//   };
// })(jQuery, Drupal, drupalSettings);





(function($, Drupal, drupalSettings) {
  Drupal.behaviors.productCategoryOther = {
    attach: function(context, settings) {
      const configs = [
        // Radio groups with wrapper
        { radio: 'product_category', field: 'edit-product-category-other', value: drupalSettings.merchant_dashboard.other_term_id, hasWrapper: true },
        { radio: 'testing_type', field: 'edit-testing-type-other', value: 22, hasWrapper: true },
        { radio: 'tester_type', field: 'edit-tester-type-other', value: 25, hasWrapper: true },
        
        // Radio groups without wrapper (individual elements)
        { radio: 'gender_preference', field: 'edit-gender-preference-other--2', value: 'other', hasWrapper: false },
        { radio: 'physical_abilities', field: 'edit-sports-specification', value: 'other', hasWrapper: false },
        { radio: 'camera_requirements', field: 'edit-smartphone-specs', value: 'if_yes_smartphone_specs', hasWrapper: false },
        { radio: 'location_preferences', field: 'edit-specific-regions', value: 'outdoor_specific_regions', hasWrapper: false },
        { radio: 'safety_gear_needed', field: 'edit-safety-gear-other', value: 'other', hasWrapper: false },
        { radio: 'licenses_certifications', field: 'edit-licenses-other', value: 'other', hasWrapper: false },
        { radio: 'platform_preferences', field: 'edit-platform-other', value: 'other', hasWrapper: false },
        { radio: 'content_style', field: 'edit-content-style-other--2', value: 'other', hasWrapper: false }
      ];

      function toggleOtherField() {
        configs.forEach(config => {
          const selectedValue = $(`input[name="${config.radio}"]:checked`, context).val();
          const $field = $(`#${config.field}`, context);
          
          if (config.hasWrapper) {
            const $wrapper = $field.closest('.js-form-wrapper, .form-item');
            if (selectedValue == config.value) {
              $wrapper.slideDown(300);
              $field.attr('required', 'required');
            } else {
              $wrapper.slideUp(300);
              $field.removeAttr('required');
              $field.val('');
            }
          } else {
            const $label = $(`label[for="${config.field}"]`, context);
            const $description = $(`#${config.field}--description`, context);
            
            if (selectedValue == config.value) {
              $field.stop(true, true).slideDown(300);
              $label.stop(true, true).slideDown(300);
              if ($description.length) $description.stop(true, true).slideDown(300);
              $field.attr('required', 'required');
            } else {
              $field.stop(true, true).slideUp(300);
              $label.stop(true, true).slideUp(300);
              if ($description.length) $description.stop(true, true).slideUp(300);
              $field.removeAttr('required');
              $field.val('');
            }
          }
        });
      }

      // Hide all fields initially
      configs.forEach(config => {
        const $field = $(`#${config.field}`, context);
        
        if (config.hasWrapper) {
          $field.closest('.js-form-wrapper, .form-item').hide();
        } else {
          $field.hide();
          $(`label[for="${config.field}"]`, context).hide();
          $(`#${config.field}--description`, context).hide();
        }
      });

      // Initialize and add event listeners
      toggleOtherField();
      
      const radioSelectors = configs.map(config => `input[name="${config.radio}"]`).join(', ');
      $(radioSelectors, context).on('change', toggleOtherField);
    }
  };
})(jQuery, Drupal, drupalSettings);