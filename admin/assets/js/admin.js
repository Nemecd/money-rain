/*
==================================================
            MONEY RAIN ADMIN PANEL
==================================================
*/

document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("adminLoginForm");
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");

  const loginButton = document.getElementById("loginBtn");
  const loginText = loginButton?.querySelector(".login-text");
  const loginSpinner = document.getElementById("loginSpinner");

  const togglePassword = document.getElementById("togglePassword");

  /*
    ==========================================
            PASSWORD TOGGLE
    ==========================================
    */

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", () => {
      const isPassword = passwordInput.type === "password";

      passwordInput.type = isPassword ? "text" : "password";

      togglePassword.innerHTML = isPassword
        ? '<i class="bi bi-eye-slash"></i>'
        : '<i class="bi bi-eye"></i>';
    });
  }

  /*
    ==========================================
            LOGIN SUBMIT
    ==========================================
    */

  if (!loginForm) return;

  loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const email = emailInput.value.trim();

    const password = passwordInput.value;

    if (email === "") {
      showNotification("Please enter your email address.", "warning");

      emailInput.focus();

      return;
    }

    if (password === "") {
      showNotification("Please enter your password.", "warning");

      passwordInput.focus();

      return;
    }

    setLoading(true);

    try {
      const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

      const response = await fetch(
        "authenticate.php",

        {
          method: "POST",

          headers: {
            "Content-Type": "application/json",

            "X-Requested-With": "XMLHttpRequest",

            "X-CSRF-Token": csrfToken,
          },

          body: JSON.stringify({
            email,
            password,
          }),
        },
      );

      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result.message || "Login failed.");
      }

      showNotification(
        "Login successful. Redirecting...",

        "success",
      );

      setTimeout(() => {
        window.location.href = "dashboard.php";
      }, 1200);
    } catch (error) {
      showNotification(
        error.message || "Unable to login.",

        "error",
      );
    } finally {
      setLoading(false);
    }
  });

  /*
    ==========================================
            LOADING STATE
    ==========================================
    */

  function setLoading(state) {
    loginButton.disabled = state;

    if (state) {
      loginSpinner.classList.remove("d-none");

      loginText.textContent = "Signing In...";
    } else {
      loginSpinner.classList.add("d-none");

      loginText.textContent = "Login to Dashboard";
    }
  }

  /*
    ==========================================
            ENTER KEY SUPPORT
    ==========================================
    */

  document.addEventListener("keydown", (event) => {
    if (
      event.key === "Enter" &&
      document.activeElement.tagName !== "TEXTAREA"
    ) {
      loginForm.requestSubmit();
    }
  });
});
/*
=========================================================
                MONEY RAIN ADMIN
=========================================================
*/

document.addEventListener("DOMContentLoaded", () => {
  initializeSidebar();

  initializeDropdown();

  initializeCounters();

  initializeAnimations();

  initializeSearch();

  initializeTooltips();
});

/*=========================================================
                MOBILE SIDEBAR
=========================================================*/

function initializeSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const toggle = document.querySelector(".sidebar-toggle");

  if (!sidebar || !toggle) return;

  let overlay = document.querySelector(".sidebar-overlay");

  if (!overlay) {
    overlay = document.createElement("div");

    overlay.className = "sidebar-overlay";

    document.body.appendChild(overlay);
  }

  toggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");

    overlay.classList.toggle("active");

    document.body.classList.toggle("sidebar-open");
  });

  overlay.addEventListener("click", () => {
    sidebar.classList.remove("active");

    overlay.classList.remove("active");

    document.body.classList.remove("sidebar-open");
  });
}

/*=========================================================
                PROFILE DROPDOWN
=========================================================*/

function initializeDropdown() {
  const profile = document.querySelector(".admin-profile");

  const dropdown = document.querySelector(".profile-dropdown");

  if (!profile || !dropdown) return;

  profile.addEventListener("click", (e) => {
    e.stopPropagation();

    dropdown.classList.toggle("show");
  });

  document.addEventListener("click", () => {
    dropdown.classList.remove("show");
  });
}

/*=========================================================
                COUNTER ANIMATION
=========================================================*/

function initializeCounters() {
  const counters = document.querySelectorAll("[data-counter]");

  counters.forEach((counter) => {
    const target = parseFloat(counter.dataset.counter);

    let current = 0;

    const duration = 1200;

    const increment = target / (duration / 16);

    function update() {
      current += increment;

      if (current >= target) {
        counter.textContent = formatNumber(target);

        return;
      }

      counter.textContent = formatNumber(current);

      requestAnimationFrame(update);
    }

    update();
  });
}

/*=========================================================
                CARD FADE ANIMATION
=========================================================*/

function initializeAnimations() {
  const cards = document.querySelectorAll(
    ".stat-card,.dashboard-card,.details-card",
  );

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("fade-in");
        }
      });
    },

    {
      threshold: 0.15,
    },
  );

  cards.forEach((card) => observer.observe(card));
}

/*=========================================================
                SEARCH FILTER
=========================================================*/

function initializeSearch() {
  const input = document.querySelector(".table-search");

  if (!input) return;

  input.addEventListener("keyup", function () {
    const value = this.value.toLowerCase();

    document.querySelectorAll("tbody tr").forEach((row) => {
      row.style.display = row.innerText.toLowerCase().includes(value)
        ? ""
        : "none";
    });
  });
}

/*=========================================================
                TOOLTIPS
=========================================================*/

function initializeTooltips() {
  if (typeof bootstrap === "undefined") return;

  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
  );

  tooltipTriggerList.forEach((el) => {
    new bootstrap.Tooltip(el);
  });
}

/*=========================================================
                NUMBER FORMAT
=========================================================*/

function formatNumber(number) {
  return new Intl.NumberFormat("en-US", {
    maximumFractionDigits: 0,
  }).format(number);
}

/*=========================================================
                LOADING BUTTON
=========================================================*/

function setButtonLoading(button, loading) {
  if (!button) return;

  if (loading) {
    button.disabled = true;

    button.dataset.original = button.innerHTML;

    button.innerHTML =
      '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
  } else {
    button.disabled = false;

    button.innerHTML = button.dataset.original;
  }
}

/*=========================================================
                COPY TO CLIPBOARD
=========================================================*/

function copyText(text) {
  navigator.clipboard
    .writeText(text)

    .then(() => {
      if (typeof showNotification === "function") {
        showNotification(
          "Copied to clipboard.",

          "success",
        );
      }
    })

    .catch(() => {
      if (typeof showNotification === "function") {
        showNotification(
          "Unable to copy.",

          "error",
        );
      }
    });
}

/*=========================================================
                LOGOUT CONFIRM
=========================================================*/

function confirmLogout(url) {
  if (confirm("Are you sure you want to logout?")) {
    window.location = url;
  }
}

/*=========================================================
                ESC CLOSE SIDEBAR
=========================================================*/

document.addEventListener("keydown", (e) => {
  if (e.key !== "Escape") return;

  document

    .querySelector(".sidebar")

    ?.classList.remove("active");

  document

    .querySelector(".sidebar-overlay")

    ?.classList.remove("active");
});

/*=========================================================
                AUTO CLOSE ALERTS
=========================================================*/

setTimeout(() => {
  document
    .querySelectorAll(".alert-dismissible")

    .forEach((alert) => {
      alert.remove();
    });
}, 5000);

/*=========================================================
                SESSION WARNING
=========================================================*/

const SESSION_TIMEOUT = 25 * 60 * 1000;

let warningShown = false;

setInterval(() => {
  if (warningShown) return;

  warningShown = true;

  if (typeof showNotification === "function") {
    showNotification(
      "Your session will expire soon due to inactivity.",

      "warning",
    );
  }
}, SESSION_TIMEOUT);
