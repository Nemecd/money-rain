/*==================================================
                MONEY RAIN
                SIGNUP PAGE
==================================================*/

document.addEventListener("DOMContentLoaded", () => {
    /*====================================
        AGREEMENT STATUS
    ====================================*/

    const agreementCheckbox = document.getElementById("agreement");

    const agreementAccepted = localStorage.getItem("agreementAccepted");

    if (agreementAccepted === "true") {

        agreementCheckbox.checked = true;

    }
    /*====================================
            PASSWORD TOGGLE
    ====================================*/

    const toggleButtons = document.querySelectorAll(".toggle-password");

    toggleButtons.forEach(button => {

        button.addEventListener("click", () => {

            const input = button.previousElementSibling;
            const icon = button.querySelector("i");

            if (input.type === "password") {

                input.type = "text";

                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");

            } else {

                input.type = "password";

                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");

            }

        });

    });

    /*====================================
            PASSWORD STRENGTH
    ====================================*/

    const password = document.getElementById("password");

    const strengthBar = document.createElement("div");
    strengthBar.className = "password-strength";

    const strengthFill = document.createElement("div");
    strengthFill.className = "password-strength-fill";

    const strengthText = document.createElement("small");
    strengthText.className = "password-strength-text";

    strengthBar.appendChild(strengthFill);

    password.parentElement.parentElement.appendChild(strengthBar);
    password.parentElement.parentElement.appendChild(strengthText);

    password.addEventListener("input", () => {

        const value = password.value;

        let score = 0;

        if (value.length >= 8) score++;

        if (/[A-Z]/.test(value)) score++;

        if (/[0-9]/.test(value)) score++;

        if (/[^A-Za-z0-9]/.test(value)) score++;

        switch (score) {

            case 0:
            case 1:

                strengthFill.style.width = "25%";
                strengthFill.style.background = "#EF4444";
                strengthText.textContent = "Weak Password";

                break;

            case 2:

                strengthFill.style.width = "50%";
                strengthFill.style.background = "#F59E0B";
                strengthText.textContent = "Fair Password";

                break;

            case 3:

                strengthFill.style.width = "75%";
                strengthFill.style.background = "#3B82F6";
                strengthText.textContent = "Good Password";

                break;

            case 4:

                strengthFill.style.width = "100%";
                strengthFill.style.background = "#22C55E";
                strengthText.textContent = "Strong Password";

                break;

        }

    });

    /*====================================
        CONFIRM PASSWORD
    ====================================*/

    const confirmPassword = document.getElementById("confirmPassword");

    const matchText = document.createElement("small");

    matchText.className = "mt-2 d-block";

    confirmPassword.parentElement.parentElement.appendChild(matchText);

    function validatePassword() {

        if (confirmPassword.value === "") {

            matchText.innerHTML = "";

            return;

        }

        if (password.value === confirmPassword.value) {

            matchText.style.color = "#16A34A";

            matchText.innerHTML = '<i class="bi bi-check-circle-fill"></i> Passwords Match';

        }

        else {

            matchText.style.color = "#DC2626";

            matchText.innerHTML = '<i class="bi bi-x-circle-fill"></i> Passwords Do Not Match';

        }

    }

    password.addEventListener("keyup", validatePassword);

    confirmPassword.addEventListener("keyup", validatePassword);

    /*====================================
            ACCOUNT NUMBER
    ====================================*/

    const accountNumber = document.getElementById("accountNumber");

    accountNumber.addEventListener("input", function () {

        this.value = this.value.replace(/\D/g, '');

        if (this.value.length > 10) {

            this.value = this.value.slice(0, 10);

        }

    });

    /*====================================
            PHONE
    ====================================*/

    const phone = document.getElementById("phone");

    phone.addEventListener("input", function () {

        this.value = this.value.replace(/[^\d+]/g, '');

    });

    /*====================================
            USERNAME
    ====================================*/

    const username = document.getElementById("username");

    const usernameText = document.createElement("small");

    username.parentElement.appendChild(usernameText);

    username.addEventListener("keyup", () => {

        const value = username.value.trim();

        if (value.length < 4) {

            usernameText.style.color = "#DC2626";

            usernameText.textContent = "Minimum 4 characters";

            return;

        }

        usernameText.style.color = "#16A34A";

        usernameText.innerHTML = '<i class="bi bi-check-circle-fill"></i> Looks Good';

    });

    /*====================================
            FORM
    ====================================*/

    const form = document.getElementById("signupForm");

    const submitBtn = document.querySelector(".signup-btn");

    form.addEventListener("submit", (e) => {

        e.preventDefault();

        if (password.value !== confirmPassword.value) {

            alert("Passwords do not match.");

            return;

        }

        if (!agreementCheckbox.checked) {

            if (confirm("You must accept the Investor Agreement before creating an account.\n\nWould you like to read it now?")) {

                window.location.href = "agreement.html";

            }

            return;

        }

        submitBtn.disabled = true;

        submitBtn.innerHTML = `

            <span class="spinner-border spinner-border-sm me-2"></span>

            Creating Account...

        `;

        /*====================================
            SUPABASE GOES HERE
        ====================================*/

        setTimeout(() => {

            submitBtn.disabled = false;

            submitBtn.innerHTML = "Create Account";

            // alert("Supabase Registration Coming Next.");

        }, 2000);

    });

});