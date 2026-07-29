/* Hooks into #signupForm from signup.html.
   Add this file as an EXTRA <script> tag alongside your existing
   assets/js/signup.js (which likely just handles the password-eye toggles) —
   don't overwrite that file, just add this one below it. */

document.getElementById("signupForm")?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
        fullname: document.getElementById("fullname").value.trim(),
        username: document.getElementById("username").value.trim(),
        email: document.getElementById("email").value.trim(),
        phone: document.getElementById("phone").value.trim(),
        bank: document.getElementById("bank").value.trim(),
        accountName: document.getElementById("accountName").value.trim(),
        accountNumber: document.getElementById("accountNumber").value.trim(),
        password: document.getElementById("password").value,
        confirmPassword: document.getElementById("confirmPassword").value,
        referral: document.getElementById("referral").value.trim(),
        agreement: document.getElementById("agreement").checked,
    };

    const btn = e.target.querySelector(".signup-btn");
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "Creating account...";

    clearFormError();

    try {
        const res = await fetch("api/signup.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!data.success) {
            showFormError(data.errors?.join(" ") || "Signup failed. Please try again.");
            return;
        }

        window.location.href = "login.html?registered=1";
    } catch (err) {
        showFormError("Network error. Please check your connection and try again.");
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
});

function showFormError(message) {
    const form = document.getElementById("signupForm");
    let el = document.getElementById("formError");
    if (!el) {
        el = document.createElement("div");
        el.id = "formError";
        el.className = "alert alert-danger mb-3";
        form.prepend(el);
    }
    el.textContent = message;
}

function clearFormError() {
    document.getElementById("formError")?.remove();
}