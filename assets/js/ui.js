function formatCurrency(amount) {
    return new Intl.NumberFormat("en-NG", {
        style: "currency",
        currency: "NGN"
    }).format(amount);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString();
}
function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    showToast("Copied Successfully");
}

function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        put.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
    }
}