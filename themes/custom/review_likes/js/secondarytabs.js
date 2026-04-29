//category tabs

document.addEventListener("DOMContentLoaded", function () {
  const tabbuttons = document.querySelectorAll(".category-btn");

  tabbuttons.forEach((btn) => {
    btn.addEventListener("click", function () {
      tabbuttons.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
    });
  });
});


    document.addEventListener("DOMContentLoaded", function () {
           const navButtons = document.querySelectorAll(".nav-btn");
           const views = document.querySelectorAll(".view");
         
           navButtons.forEach(btn => {
             btn.addEventListener("click", function () {
               // Remove active class from all buttons
               navButtons.forEach(b => b.classList.remove("active"));
               // Hide all views
               views.forEach(v => v.style.display = "none");
         
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
     });

     // Toggle category panel visibility
        //  document.getElementById('toggleCategories').addEventListener('click', function () {
        //     const panel = document.getElementById('categoryPanel');
        //     panel.classList.toggle('show');
        //  });

        //  // Hide category panel when clicking outside
        //  document.addEventListener('click', function (event) {
        //     const panel = document.getElementById('categoryPanel');
        //     const button = document.getElementById('toggleCategories');
        //     if (!panel.contains(event.target) && !button.contains(event.target)) {
        //        panel.classList.remove('show');
        //     }
        //  });

  //Top navigation

  $(document).ready(function () {

    // Default: show Products section, hide others
    $("section[id]").hide();
    $("#productsSection").show();

    // Top navigation click handler
    $(".nav-link").on("click", function (e) {
        e.preventDefault();

        // Update active class
        $(".nav-link").removeClass("active");
        $(this).addClass("active");

        // Get section ID from data-section="#id"
        let target = $(this).data("section");

        // Hide all sections
        $("section[id]").hide();

        // Show the selected one
        $(target).fadeIn(200);
    });

    // Category dropdown toggle
    $("#toggleCategories").on("click", function () {
        $("#categoryPanel").slideToggle(200);
    });

});

//special nav tabs

$(document).ready(function () {

    $(".special-tab").on("click", function () {

        // remove active from tabs
        $(".special-tab").removeClass("active");
        $(this).addClass("active");

        // hide all panes
        $(".special-pane").removeClass("active");

        // show selected
        let target = $(this).data("tab");
        $("#" + target).addClass("active");
    });

});

//running challenges nav tabs



