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


document.addEventListener("DOMContentLoaded", function () {
  const adminContainer = document.getElementById("adminPlatformContainer");
  let platformCount = 0;

  // Load from localStorage
  function loadPlatforms() {
    const saved = JSON.parse(localStorage.getItem("adminPlatforms")) || [];
    adminContainer.innerHTML = ""; // clear
    saved.forEach((item, index) => {
      addPlatformSection(index + 1, item.value);
    });
    platformCount = saved.length;
  }

  // Save to localStorage
  function savePlatforms() {
    const data = [];
    adminContainer.querySelectorAll(".adminplatform-section input").forEach(input => {
      data.push({ value: input.value });
    });
    localStorage.setItem("adminPlatforms", JSON.stringify(data));
  }

  // Add new section
  function addPlatformSection(id, value = "") {
    const newSection = document.createElement("div");
    newSection.className = "adminplatform-section mt-2";
    newSection.id = "adminplatformSection-" + id;
    newSection.innerHTML = `
      <label for="platform-${id}" class="required mb-2">Online Selling Platforms (links)</label>
      <div class="d-flex storelink-section">
        <input type="text" id="platform-${id}" class="form-control me-4" placeholder="Platform Link" value="${value}" required>
        <button type="button" class="btn danger remove-btn">Remove</button>
      </div>
    `;
    adminContainer.appendChild(newSection);
  }

  // Add button
  document.getElementById("adminaddPlatformBtn").addEventListener("click", function () {
    if (platformCount >= 20) {
      alert("You can only add up to 20 platform links.");
      return;
    }
    platformCount++;
    addPlatformSection(platformCount);
    savePlatforms();
  });

  // Remove button
  adminContainer.addEventListener("click", function (e) {
    if (e.target && e.target.classList.contains("remove-btn")) {
      e.target.closest(".adminplatform-section").remove();
      platformCount = adminContainer.querySelectorAll(".adminplatform-section").length;
      savePlatforms();
    }
  });

  // Save changes on input
  adminContainer.addEventListener("input", savePlatforms);

  // Init on page load
  loadPlatforms();
});


// Legal business logic

document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("legalContainer");
  let legalCount = 1;

  // Load only brand sections
  if (localStorage.getItem("legals")) {
    container.innerHTML = localStorage.getItem("legals");
    legalCount = container.querySelectorAll(".legal-section").length;
  }

  function saveLegalNames() {
    localStorage.setItem("legals", container.innerHTML);
  }

  // Add new section
  document.getElementById("addLegalBtn").addEventListener("click", function () {
    if (legalCount >= 3) {
      alert("You can only add up to 3 add Legal business names.");
      return;
    }
    legalCount++;
    const newRow = document.createElement("div");
    newRow.className = "row legal-section";
    newRow.id = "brandSection-" + legalCount;
    newRow.innerHTML = `
      <div class="col-md-8">
        <label for="legalName-${legalCount}" class="required mb-2">Legal Business Name</label>
        <input type="text" id="legalName-${legalCount}" class="form-control mb-3" placeholder="Legal Business Name" required>
      </div>
      <div class="col-md-4">
        <button type="button" class="btn danger mt-4 remove-btn">Remove</button>
      </div>
    `;
    container.appendChild(newRow);
    saveLegalNames();
  });

  // Remove section
  container.addEventListener("click", function (e) {
    if (e.target && e.target.classList.contains("remove-btn")) {
      e.target.closest(".legal-section").remove();
      brandCount = container.querySelectorAll(".legal-section").length;
      saveLegalNames();
    }
  });

  // Save when typing
  container.addEventListener("input", saveLegalNames);
});


//Add a product section, show/hide functionality for input fields
document.addEventListener("DOMContentLoaded", function () {

    const productDetailsSection = document.getElementById('productDetailsSection');
    const extractBtn = document.getElementById('extractDataBtn');
    const manualBtn = document.getElementById('manualAddBtn');

    if (extractBtn && manualBtn && productDetailsSection) {

        extractBtn.addEventListener('click', () => {
            productDetailsSection.style.display = 'block';
        });

        manualBtn.addEventListener('click', () => {
            productDetailsSection.style.display = 'block';
        });

    }
});


