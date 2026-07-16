document.addEventListener("DOMContentLoaded", () => {

    const toggle = document.querySelector(".toggle-password");

    const password = document.getElementById("password");

    const icon = toggle.querySelector("i");

    toggle.addEventListener("click", () => {

        if (password.type === "password") {

            password.type = "text";

            icon.classList.replace("bi-eye", "bi-eye-slash");

        } else {

            password.type = "password";

            icon.classList.replace("bi-eye-slash", "bi-eye");

        }

    });

    const form = document.getElementById("loginForm");

    const button = document.querySelector(".login-btn");

    form.addEventListener("submit", (e) => {

        e.preventDefault();

        button.disabled = true;

        button.innerHTML = `
<span class="spinner-border spinner-border-sm me-2"></span>
Logging In...
`;

        setTimeout(() => {

            button.disabled = false;

            button.innerHTML = "Login";

            /* Supabase login goes here */

            alert("Supabase Login Integration Coming Next");
        }, 2000);
    });
});