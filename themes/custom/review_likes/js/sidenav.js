//sidenav

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
         
           // Set default view if none are visible
         views.forEach(v => v.style.display = "none");
         const defaultView = document.getElementById("userhome");
         if (defaultView) {
         defaultView.style.display = "block";
         document.querySelector('[data-view="userhome"]')?.classList.add("active");
         }
         });


         