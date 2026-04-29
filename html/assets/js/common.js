  //fixed header logic
  if ($(window).width() > 992) {
      $(window).scroll(function() {
          if ($(this).scrollTop() > 1) {
              $("header").addClass("sticky");
          } else {
              $("header").removeClass("sticky");
          }
      });
  }

  //home page active class for main menu
  $(".main-navbar .nav-item .nav-link").on("click", function() {
      $(".main-navbar .nav-item .nav-link").removeClass("active");
      $(this).addClass("active");
  });

  //close post
  function closeCard() {
      document.getElementById("myCard").style.display = "none";
  }
  //more option
  function toggleDescription() {
      var shortDesc = document.getElementById("shortDesc");
      var fullDesc = document.getElementById("fullDesc");
      var toggleBtn = document.getElementById("toggleBtn");

      if (fullDesc.style.display === "none") {
          shortDesc.style.display = "none";
          fullDesc.style.display = "inline";
          toggleBtn.textContent = "Read Less";
      } else {
          shortDesc.style.display = "inline";
          fullDesc.style.display = "none";
          toggleBtn.textContent = "Read More";
      }
  }
  //like script
  $(".like-btn").click(function() {
      $(this).find("img").toggleClass("liked");
  });

  //Add more button
  document.addEventListener("DOMContentLoaded", function() {
      var checkboxes = document.querySelectorAll(".checkboxsection .form-check");
      var moreLink = document.getElementById("toggleLink");

      if (checkboxes.length > 4) {
          // Hide extra checkboxes
          for (var i = 4; i < checkboxes.length; i++) {
              checkboxes[i].style.display = "none";
          }
          moreLink.style.display = "inline";
      }
  });

  function toggleCategories() {
      var checkboxes = document.querySelectorAll(".checkboxsection .form-check");
      var moreLink = document.getElementById("toggleLink");

      var isExpanded = moreLink.innerText === "Less";
      for (var i = 4; i < checkboxes.length; i++) {
          checkboxes[i].style.display = isExpanded ? "none" : "block";
      }

      moreLink.innerText = isExpanded ? "More" : "Less";
  }


  //popup for product details screen

  $('.thumb-img').on('click', function() {
      const imgSrc = $(this).data('large');
      $('#mainImage').attr('src', imgSrc);
      $('.thumb-img').removeClass('active');
      $(this).addClass('active');
  });

  //Product images in product details page

  $(document).ready(function() {
      // Configure Lightbox
      lightbox.option({
          'resizeDuration': 200,
          'wrapAround': true,
          'albumLabel': "Image %1 of %2"
      });

      // Thumbnail switching
      $('.thumbnail').click(function() {
          $('.thumbnail').removeClass('active');
          $(this).addClass('active');

          const largeImageSrc = $(this).data('large');
          $('#mainProductImage').attr('src', largeImageSrc);
          $('#mainProductImage').parent().attr('href', largeImageSrc);
      });


      // Enter key for comment submission
      $('#comment').keypress(function(e) {
          if (e.which == 13) {
              $('.commentbox .btn').click();
          }
      });
  });

  function toggleDescription() {
      const shortDesc = document.getElementById('shortDesc');
      const fullDesc = document.getElementById('fullDesc');
      const toggleBtn = document.getElementById('toggleBtn');

      if (fullDesc.style.display === 'none') {
          shortDesc.style.display = 'none';
          fullDesc.style.display = 'inline';
          toggleBtn.textContent = 'Read Less';
      } else {
          shortDesc.style.display = 'inline';
          fullDesc.style.display = 'none';
          toggleBtn.textContent = 'Read More';
      }
  }

  function playVideo() {
      alert('Video playback would start here');
  }

  function showMoreImages() {
      // Trigger light
  }

  //slick slider in product description
  $(document).ready(function() {
      $('.screenshot-slider').slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          arrows: true,
          dots: false,
          infinite: false,
          adaptiveHeight: true,
          autoplay: false,
          responsive: [{
                  breakpoint: 768,
                  settings: {
                      slidesToShow: 2
                  }
              },
              {
                  breakpoint: 480,
                  settings: {
                      slidesToShow: 1
                  }
              }
          ]
      });
  });

  

  $('.send-button').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation(); // Prevents affecting parent elements like sliders

      const commentInput = $(this).closest('.commentbox').find('input');
      const comment = commentInput.val().trim();

      if (comment) {
          const newComment = $('<div class="cmnt">' + comment + '</div>');
          $('.comment-view-section').append(newComment);
          commentInput.val('');
      }
  });

  //other fileds show-hide
 $(document).ready(function () {
   function toggleFields() {
      var selected = $('#userinfo').val();
      if (selected === 'Other') {
         $('.other-only').show();
         $('.default-only').hide();
      } else {
         $('.other-only').hide();
         $('.default-only').show();
      }
   }

   toggleFields(); // On page load

   $('#userinfo').on('change', function () {
      toggleFields(); // On dropdown change
   });
});

//Merchant Registration duplicate code

document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("brandContainer");
  let brandCount = 1;

  // Load only brand sections
  if (localStorage.getItem("brands")) {
    container.innerHTML = localStorage.getItem("brands");
    brandCount = container.querySelectorAll(".brand-section").length;
  }

  function saveBrands() {
    localStorage.setItem("brands", container.innerHTML);
  }

  // Add new section
  document.getElementById("addBrandBtn").addEventListener("click", function () {
    if (brandCount >= 3) {
      alert("You can only add up to 3 brands.");
      return;
    }
    brandCount++;
    const newRow = document.createElement("div");
    newRow.className = "row brand-section";
    newRow.id = "brandSection-" + brandCount;
    newRow.innerHTML = `
      <div class="col-md-8">
        <label for="Bnames-${brandCount}" class="required mb-2">Brand Name / Doing Business As</label>
        <input type="text" id="Bnames-${brandCount}" class="form-control mb-3" placeholder="Brand Name / Doing Business As" required>
      </div>
      <div class="col-md-4">
        <button type="button" class="btn danger mt-4 remove-btn">Remove</button>
      </div>
    `;
    container.appendChild(newRow);
    saveBrands();
  });

  // Remove section
  container.addEventListener("click", function (e) {
    if (e.target && e.target.classList.contains("remove-btn")) {
      e.target.closest(".brand-section").remove();
      brandCount = container.querySelectorAll(".brand-section").length;
      saveBrands();
    }
  });

  // Save when typing
  container.addEventListener("input", saveBrands);
});


//Platform duplicate code

  
let platformCount = document.querySelectorAll("#adminplatform .platform-wrapper").length || 1;
const maxPlatforms = 20;
const container = document.getElementById("adminplatform");

// Function to create new platform block
function createPlatformBlock(id) {
  return `
    <div class="platform-wrapper mt-5" id="adminplatformBlock-${id}">
      <div class="row">
        <div class="col-md-5 mb-3">
          <label for="platform-${id}" class="required mb-1">Online Selling Platforms (links)</label>
          <input type="text" id="platform-${id}" class="form-control" placeholder="Amazon, eBay, Walmart, flipkart, etc" required>
        </div>
        <div class="col-md-2 mb-3">
          <label for="platformprice-${id}" class="required mb-1">Price</label>
          <input type="text" id="platformprice-${id}" class="form-control" placeholder="Price" required>
        </div>
        <div class="col-md-2 mb-3">
          <label for="platformrating-${id}" class="required mb-1">Rating</label>
          <input type="text" id="platformrating-${id}" class="form-control" placeholder="Rating" required>
        </div>
        <div class="col-md-3 mb-3">
          <label for="couponcode-${id}" class="mb-1">Coupon Code</label>
          <input type="text" id="couponcode-${id}" class="form-control" placeholder="Coupon Code" required>
        </div>
        <div class="col-md-3 mb-3">
          <label for="pcexpiry-date-${id}" class="required mb-1">Coupon Expiry Date</label>
          <input type="date" class="form-control" id="pcexpiry-date-${id}" required>
        </div>
        <div class="col-md-5 mb-3">
          <label for="paffliatelink-${id}" class="required mb-1">Affiliate Link</label>
          <input type="text" id="paffliatelink-${id}" class="form-control" placeholder="Affiliate Link" disabled>
        </div>
        <div class="col-md-1 mb-3 linktoggle-btn">
          <div class="toggle-wrapper mt-3">
            <label class="toggle-switch">
              <input type="checkbox" class="affiliateToggle" data-target="paffliatelink-${id}">
              <span class="slider"></span>
            </label>
          </div>
        </div>
        <div class="col-md-1 mb-3 linktoggle-btn">
          <button type="button" class="btn btn-danger remove-btn mt-4">Remove</button>
        </div>
      </div>
    </div>
  `;
}

// Add new platform section
document.getElementById("OnlineaddPlatformBtn").addEventListener("click", function () {
  if (platformCount >= maxPlatforms) {
    alert("You can only add up to 20 platform links.");
    return;
  }

  platformCount++;
  const newSection = document.createElement("div");
  newSection.innerHTML = createPlatformBlock(platformCount);
  container.appendChild(newSection.firstElementChild);

  savePlatforms();
});

// Remove platform section (at least one required)
container.addEventListener("click", function (e) {
  if (e.target && e.target.classList.contains("remove-btn")) {
    const wrappers = container.querySelectorAll(".platform-wrapper");
    if (wrappers.length > 1) {
      e.target.closest(".platform-wrapper").remove();
      platformCount = container.querySelectorAll(".platform-wrapper").length;
      savePlatforms();
    } else {
      alert("At least one platform section is required.");
    }
  }
});


// Save whenever input changes
container.addEventListener("input", savePlatforms);

// Dummy save function (replace with your logic)
function savePlatforms() {
  console.log("Saving platform data...");
}

// Initialize first toggle on page load
function initFirstToggle() {
  const firstToggle = container.querySelector(".affiliateToggle");
  if (firstToggle) {
    const targetId = firstToggle.getAttribute("data-target");
    const input = document.getElementById(targetId);
    if (input) input.disabled = !firstToggle.checked;
  }
}
initFirstToggle();



//Add social media code//

document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("socialContainer");
  let socialCount = 1;

  // Load saved socials
  if (localStorage.getItem("socials")) {
    container.innerHTML = localStorage.getItem("socials");
    socialCount = container.querySelectorAll(".social-section").length;
  }

  // Save current socials
  function saveSocials() {
    localStorage.setItem("socials", container.innerHTML);
  }

  // Add new social section
  document.getElementById("addSocialBtn").addEventListener("click", function () {
    if (socialCount >= 10) {
      alert("You can only add up to 10 social handles.");
      return;
    }

    socialCount++;
    const newSection = document.createElement("div");
    newSection.className = "social-section item";
    newSection.id = "socialSection-" + socialCount;
    newSection.innerHTML = `
      <div class="row">
        <div class="col-md-4">
          <label class="mb-2">Platform Name</label>
          <input type="text" id="socialPlatform-${socialCount}" name="socialPlatform[]" class="form-control mb-2" placeholder="Facebook, Instagram, YouTube..." required>
        </div>
        <div class="col-md-6">
          <label class="mb-2">Page/Profile Link</label>
          <input type="url" id="socialLink-${socialCount}" name="socialLink[]" class="form-control mb-2" placeholder="https://facebook.com/yourpage" required>
        </div>
        <div class="col-md-2">
          <button type="button" class="btn danger remove-btn mt-4">Remove</button>
        </div>
      </div>
    `;
    container.appendChild(newSection);
    saveSocials();
  });

  // Remove section
  container.addEventListener("click", function (e) {
    if (e.target && e.target.classList.contains("remove-btn")) {
      const section = e.target.closest(".social-section");
      section.remove();
      socialCount = container.querySelectorAll(".social-section").length;
      saveSocials();
    }
  });

  // Save while typing
  container.addEventListener("input", saveSocials);
  //Sidenavigation


});

// Select all elements you want to toggle active on
const buttons = document.querySelectorAll('.nav-btn');

buttons.forEach(btn => {
  btn.addEventListener('click', () => {
    // Remove active from all
    buttons.forEach(b => b.classList.remove('active'));
    // Add active to the clicked one
    btn.classList.add('active');
  });
});

//merchant dashboard clone code

// $(document).ready(function () {
//     const container = $("#adminplatform");
//     let platformCount = 0;

//     // Load saved data from localStorage
//     if (localStorage.getItem("adminPlatforms")) {
//         container.html(localStorage.getItem("adminPlatforms"));
//         platformCount = container.find(".platform-wrapper").length;
//     } else {
//         platformCount = container.find(".platform-wrapper").length;
//     }

//     // Function to save to localStorage
//     function savePlatforms() {
//         localStorage.setItem("adminPlatforms", container.html());
//     }

//     // Add new platform block
//     $("#addPlatformBtn").on("click", function () {
//         if (platformCount >= 20) {
//             alert("You can only add up to 20 platform links.");
//             return;
//         }

//         platformCount++;
//         let newBlock = `
//             <div class="platform-wrapper mt-3" id="adminplatformBlock-${platformCount}">
//                 <label for="platform-${platformCount}" class="required mb-3">Online Selling Platforms (links)</label>
//                 <div class="row">
//                     <div class="col-md-6">
//                         <div class="platform-section" id="adminplatformSection-${platformCount}">
//                             <div class="d-flex storelink-section">
//                                 <input type="text" id="platform-${platformCount}" class="form-control me-4" placeholder="Platform Link" required>
//                                 <button type="button" class="btn danger remove-btn">Remove</button>
//                             </div>
//                         </div>
//                     </div>
//                     <div class="col-md-2">
//                         <input type="text" id="platformprice-${platformCount}" class="form-control me-4" placeholder="Price" required>
//                     </div>
//                     <div class="col-md-4">
//                         <input type="text" id="couponcode-${platformCount}" class="form-control me-4" placeholder="Coupon Code" required>
//                     </div>
//                 </div>
//             </div>
//         `;
//         container.append(newBlock);
//         savePlatforms();
//     });

//     // Remove platform block
//     $(document).on("click", ".remove-btn", function () {
//         $(this).closest(".platform-wrapper").remove();
//         platformCount = container.find(".platform-wrapper").length;
//         savePlatforms();
//     });

//     // Save whenever any input changes
//     $(document).on("input", "#adminplatform input", function () {
//         savePlatforms();
//     });
// });


//Delete functionality in all products tab

let rowToDelete; // store row reference

$(document).on("click", ".delete-product", function (e) {
    e.preventDefault();
    rowToDelete = $(this).closest("tr"); // store the row
    $("#deleteConfirmModal").modal("show"); // show modal
});

$("#confirmDeleteBtn").on("click", function () {
    if (rowToDelete) {
        rowToDelete.remove(); // remove row after confirmation
        rowToDelete = null;
    }
    $("#deleteConfirmModal").modal("hide");
});



//message popup
  document.addEventListener("DOMContentLoaded", function () {
    const viewButtons = document.querySelectorAll(".view-btn");
    const modalTitle = document.getElementById("messageModalLabel");
    const modalBody = document.getElementById("messageModalBody");
    const messageModal = new bootstrap.Modal(document.getElementById("messageModal"));

    viewButtons.forEach(btn => {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const title = this.getAttribute("data-title");
        const message = this.getAttribute("data-message");
        modalTitle.textContent = title;
        modalBody.textContent = message;
        messageModal.show();
      });
    });
  });


//Sidenav performance menu
   $(document).ready(function(){
         
            // Regular sidebar buttons (except Performance)
            $(".nav-btn").not(".performance-btn").on("click", function(){
               $(".nav-btn").removeClass("active");
               $(this).addClass("active");
         
               $(".view").hide();
               $("#" + $(this).data("view")).show();
            });
         
            // Performance dropdown links
            $(".perf-link").on("click", function(e){
               e.preventDefault();
         
               $(".nav-btn").removeClass("active");
               // Keep Performance highlighted
               $(".performance-btn").addClass("active");
         
               $(".view").hide();
               $("#" + $(this).data("view")).show();
            });
         });

//sidenav rebuttal and gift tab

   $(document).ready(function(){
         
            // Regular sidebar buttons (except Performance)
            $(".nav-btn").not(".rebuttalgifts-btn").on("click", function(){
               $(".nav-btn").removeClass("active");
               $(this).addClass("active");
         
               $(".view").hide();
               $("#" + $(this).data("view")).show();
            });
         
            // Performance dropdown links
            $(".rebuttal-link").on("click", function(e){
               e.preventDefault();
         
               $(".nav-btn").removeClass("active");
               // Keep rebuttal highlighted
               $(".rebuttalgifts-btn").addClass("active");
         
               $(".view").hide();
               $("#" + $(this).data("view")).show();
            });
         });


// Generic back-to-menu handler
document.querySelectorAll("[data-back-to='all-products']").forEach(backBtn => {
  backBtn.addEventListener("click", function (e) {
    e.preventDefault();

    // Find parent "view" container (the section that should be hidden)
    const currentView = this.closest(".view");
    if (currentView) {
      currentView.style.display = "none";
    }

    // Show All Products section
    document.getElementById("all-products").style.display = "block";

    // Update sidebar active state
    const navButtons = document.querySelectorAll("aside .nav-btn");
    navButtons.forEach(btn => btn.classList.remove("active"));

    const allProductsBtn = document.querySelector('aside .nav-btn[data-view="all-products"]');
    if (allProductsBtn) {
      allProductsBtn.classList.add("active");
    }
  });
});

//video preview
 function loadVideo(url) {
      document.getElementById("videoFrame").src = url;
    }

    // Stop video when modal closes
    document.addEventListener('DOMContentLoaded', () => {
      let videoModal = document.getElementById('videoModal');
      videoModal.addEventListener('hidden.bs.modal', function () {
        let iframe = document.getElementById("videoFrame");
        iframe.src = ""; // clears the video -> stops sound
      });
    });

//discount dropdown code

document.addEventListener('DOMContentLoaded', function () {
    const discountTypeSelect = document.getElementById('discountType');

    // Section classes
    const sections = {
      "flat-off": document.querySelector('.flatdiscount'),
      "coupon": document.querySelector('.coupondiscount'),
      "bundle": document.querySelector('.bundlediscount'),
      "custom": document.querySelector('.customdiscount')
    };

    // Hide all sections initially
    Object.values(sections).forEach(section => section.style.display = 'none');

    discountTypeSelect.addEventListener('change', function () {
      const selected = this.value;

      // Hide all
      Object.values(sections).forEach(section => {
        if (section) section.style.display = 'none';
      });

      // Show selected
      if (sections[selected]) {
        sections[selected].style.display = 'flex'; // Use 'block' or 'flex' based on layout
      }
    });
  });

  // Show modal with tester details
document.querySelectorAll('.tester-link').forEach(link => {
  link.addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('modalName').textContent = this.dataset.name;
    document.getElementById('modalEmail').textContent = this.dataset.email;
    document.getElementById('modalExperience').textContent = this.dataset.experience;
    document.getElementById('modalProduct').textContent = this.dataset.product;
    document.getElementById('modalVideo').textContent = this.dataset.video;
    new bootstrap.Modal(document.getElementById('testerModal')).show();
  });
});

// Filter table rows by tester name
document.getElementById('searchInput').addEventListener('input', function () {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll('#allTestersTable tbody tr');

  rows.forEach(row => {
    const nameCell = row.querySelector('td a').textContent.toLowerCase();
    row.style.display = nameCell.includes(filter) ? '' : 'none';
  });
});


 document.addEventListener("DOMContentLoaded", function () {
           const testernavButtons = document.querySelectorAll(".testerdashboard .nav-btn");
           const testerviews = document.querySelectorAll(".testerdashboard .view");
         
           testernavButtons.forEach(btn => {
             btn.addEventListener("click", function () {
               // Remove active class from all buttons
               testernavButtons.forEach(b => b.classList.remove("active"));
               // Hide all views
               testerviews.forEach(v => v.style.display = "none");
         
               // Activate clicked button
               this.classList.add("active");
         
               // Get target view from data-view attribute
               const viewId = this.getAttribute("data-view");
               const targetView = document.getElementById(viewId);
               if (targetView) {
                 targetView.style.display = "block";
               }
             });
           });
         
           // Set default view if none are visible
         testerviews.forEach(v => v.style.display = "none");
         const defaultView = document.getElementById("testerProfile");
         if (defaultView) {
         defaultView.style.display = "block";
         document.querySelector('[data-view="testerProfile"]')?.classList.add("active");
         }
         });

