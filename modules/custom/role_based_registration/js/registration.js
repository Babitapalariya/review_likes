(function($, Drupal) {
  Drupal.behaviors.roleBasedRegistration = {
    attach: function(context, settings) {
      // Update checkboxes when select changes
      $('#custom-role-select', context).change(function() {
        var role = $(this).val();
        // Uncheck all role checkboxes
        $('input[name^="roles["]').prop('checked', false);
        // Check the selected one
        if (role) {
          $('#edit-roles-' + role).prop('checked', true);
        }
      });
      
      // Update select when checkboxes change
      $('input[name^="roles["]', context).change(function() {
        if ($(this).is(':checked') && $(this).val() !== 'authenticated') {
          $('#custom-role-select').val($(this).val());
        }
      });
    }
  };
})(jQuery, Drupal);


(function($, Drupal, once) {
  'use strict';

  Drupal.behaviors.productFormPlatforms = {
    attach: function(context, settings) {
      // Add platform field
      $(once('add-platform', '#addPlatformBtn', context)).on('click', function(e) {
        e.preventDefault();
        var platformCount = $('.platform-wrapper', context).length;
        
        if (platformCount < 20) {
          // Clone the first platform wrapper and clear its values
          var $firstPlatform = $('.platform-wrapper:first', context);
          var $newPlatform = $firstPlatform.clone();
          
          // Update IDs and clear values
          var newId = platformCount + 1;
          $newPlatform.attr('id', 'adminplatformBlock-' + newId);
          $newPlatform.find('input').val('').attr('id', function(i, id) {
            return id.replace(/\d+$/, newId);
          });
          
          // Add remove button
          $newPlatform.find('.remove-btn').remove();
          $newPlatform.find('.storelink-section').append(
            '<button type="button" class="btn danger remove-btn" data-delta="' + newId + '">Remove</button>'
          );
          
          // Append to container
          $('#adminplatform', context).append($newPlatform);
        } else {
          alert(Drupal.t('Maximum 20 platform links allowed.'));
        }
      });

      // Remove platform field
      $(context).on('click', '.remove-btn', function(e) {
        e.preventDefault();
        var delta = $(this).data('delta');
        $('#adminplatformBlock-' + delta, context).remove();
      });
    }
  };

})(jQuery, Drupal, once);





(function ($, Drupal) {
  Drupal.behaviors.deleteProduct = {
    attach: function (context, settings) {
      let nidToDelete = null;

      // Open modal when delete link clicked
      $(document).on('click', '.delete-product', function (e) {
        e.preventDefault();
        nidToDelete = $(this).data('nid');
        // console.log("🟢 Clicked Delete for Node ID:", nidToDelete);
        $('#deleteConfirmModal').modal('show');
      });

      // Confirm delete
      $(document).on('click', '#confirmDeleteBtn', function () {
        // console.log("🟢 Confirm delete for Node ID:", nidToDelete);

        if (nidToDelete) {
                   $.ajax({
            url: Drupal.url('product/delete-node/' + nidToDelete),
            type: 'POST',
            data: { _drupal_ajax: true },
            success: function () {
              // console.log("✅ Node deleted successfully:", nidToDelete);
              $('#deleteConfirmModal').modal('hide');
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


document.getElementById('edit-product-id').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});


// document.addEventListener('DOMContentLoaded', function () {
//   // Select all rating fields
//   const ratingFields = document.querySelectorAll('.rating-field');

//   ratingFields.forEach(function(field) {
//     field.addEventListener('input', function() {
//       // Allow only numbers and commas
//       this.value = this.value.replace(/[^0-9,]/g, '');

//       // Optional: remove duplicate commas
//       this.value = this.value.replace(/,+/g, ',');

//       // Optional: remove leading or trailing commas
//       this.value = this.value.replace(/^,|,$/g, '');
//     });
//   });
// });


document.addEventListener('DOMContentLoaded', function () {
  const ratingFields = document.querySelectorAll('.rating-field');

  ratingFields.forEach(function (field) {
    field.addEventListener('input', function () {

      // Allow digits and dot
      this.value = this.value.replace(/[^0-9.]/g, '');

      // Allow only ONE dot
      const parts = this.value.split('.');
      if (parts.length > 2) {
        this.value = parts[0] + '.' + parts.slice(1).join('');
      }

    });
  });
});


(function ($, Drupal) {
  Drupal.behaviors.toggleWrapper = {
    attach: function (context, settings) {
      // console.log('here i am..');
      $(".toggle-wrapper.mt-3", context).wrapInner('<label class="toggle-switch"></label>');
    }
  };
})(jQuery, Drupal);



(function (Drupal, once) {
  Drupal.behaviors.showProductDetails = {
    attach: function (context) {
//  console.log('product i am..');
      once('showProductDetails', '#edit-manually-button', context).forEach(function (button) {

        const productDetailsSection = document.getElementById('productDetailsSection');
        if (!productDetailsSection) {
          return;
        }

        button.addEventListener('click', function (e) {
          e.preventDefault(); // stop form submit
          productDetailsSection.style.display = 'block';
        });

      });

    }
  };
})(Drupal, once);



(function (Drupal, once) {
  Drupal.behaviors.extractConfirm = {
    attach: function (context) {

      once('extractConfirm', '#extractDataBtn', context).forEach(function (button) {

        const modal = document.getElementById('extractConfirmModal');
        const confirmBtn = document.getElementById('confirmExtract');
        const cancelBtn = document.getElementById('cancelExtract');

        if (!modal || !confirmBtn || !cancelBtn) {
          return;
        }

        // Intercept submit click
        button.addEventListener('click', function (e) {
          e.preventDefault(); // STOP submit
          modal.style.display = 'flex';
        });

        // Confirm → submit form
        confirmBtn.addEventListener('click', function () {
          modal.style.display = 'none';
          button.closest('form').submit(); // REAL submit
        });

        // Cancel → close popup
        cancelBtn.addEventListener('click', function () {
          modal.style.display = 'none';
        });

      });
    }
  };
})(Drupal, once);
