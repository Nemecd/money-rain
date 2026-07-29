/* Hooks into #loginForm from login.html.
   Add this file as an EXTRA <script> tag alongside your existing
   assets/js/login.js (which likely just handles the password-eye toggle) —
   don't overwrite that file, just add this one below it. */

document.getElementById("loginForm")?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const remember = document.getElementById("remember")?.checked ?? false;

    const btn = e.target.querySelector(".login-btn");
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "Signing in...";

    clearFormError();

    try {
        const res = await fetch("api/login.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password, remember }),
        });
        const data = await res.json();

        if (!data.success) {
            showFormError(data.errors?.[0] || "Login failed. Please try again.");
            return;
        }

        window.location.href = data.redirect;
    } catch (err) {
        showFormError("Network error. Please check your connection and try again.");
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
});

function showFormError(message) {
    const form = document.getElementById("loginForm");
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