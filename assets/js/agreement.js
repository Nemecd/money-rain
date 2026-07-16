/*==================================================
                MONEY RAIN
            INVESTOR AGREEMENT
==================================================*/

document.addEventListener("DOMContentLoaded", () => {

    const agreementCheckbox = document.getElementById("agree");
    const acceptButton = document.getElementById("acceptAgreement");

    //====================================
    // Enable / Disable Button
    //====================================

    agreementCheckbox.addEventListener("change", () => {

        acceptButton.disabled = !agreementCheckbox.checked;

    });

    //====================================
    // Accept Agreement
    //====================================

    acceptButton.addEventListener("click", () => {

        // Save agreement locally
        localStorage.setItem("agreementAccepted", "true");

        // Save date accepted
        localStorage.setItem(
            "agreementAcceptedDate",
            new Date().toISOString()
        );

        // Success animation
        acceptButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2"></span>
            Processing...
        `;

        acceptButton.disabled = true;

        setTimeout(() => {

            window.location.href = "signup.html";

        }, 1500);

    });

});