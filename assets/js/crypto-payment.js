/*==================================================
            MONEY RAIN
        CRYPTO PAYMENT JAVASCRIPT
==================================================*/

document.addEventListener("DOMContentLoaded", () => {

    const paymentForm = document.getElementById("paymentForm");

    const submitButton = document.getElementById("submitPayment");

    const copyButton = document.getElementById("copyWallet");

    const walletAddressElement =
        document.getElementById("walletAddress");

    const transactionHashInput =
        document.getElementById("transactionHash");

    const csrfTokenElement =
        document.querySelector('meta[name="csrf-token"]');


    /*
    ==================================================
                COPY WALLET ADDRESS
    ==================================================
    */

    copyButton.addEventListener("click", async () => {

        const walletAddress =
            walletAddressElement.textContent.trim();

        if (!walletAddress) {

            return;

        }

        try {

            await navigator.clipboard.writeText(
                walletAddress
            );

            copyButton.innerHTML = `

                <i class="bi bi-check-lg"></i>

                Copied

            `;

            copyButton.classList.add("copied");

            setTimeout(() => {

                copyButton.innerHTML = `

                    <i class="bi bi-copy"></i>

                    Copy

                `;

                copyButton.classList.remove("copied");

            }, 2500);

        } catch (error) {

            console.error(
                "Unable to copy wallet address:",
                error
            );

            showNotification(
                "Unable to copy automatically. Please copy the wallet address manually.",
                "error"
            );

        }

    });


    /*
    ==================================================
            TRANSACTION HASH VALIDATION
    ==================================================
    */

    transactionHashInput.addEventListener(
        "input",
        () => {

            transactionHashInput.value =
                transactionHashInput.value.trim();

        }
    );


    /*
    ==================================================
            SUBMIT PAYMENT
    ==================================================
    */

    paymentForm.addEventListener(
        "submit",
        async (event) => {

            event.preventDefault();


            const transactionHash =
                transactionHashInput.value.trim();


            if (!transactionHash) {

                showNotification(
                    "Please enter your transaction hash (TXID).",
                    "error"
                );

                transactionHashInput.focus();

                return;

            }


            if (transactionHash.length < 20) {

                showNotification(
                    "The transaction hash appears to be invalid.",
                    "error"
                );

                transactionHashInput.focus();

                return;

            }


            if (transactionHash.length > 255) {

                showNotification(
                    "The transaction hash is too long.",
                    "error"
                );

                transactionHashInput.focus();

                return;

            }


            const csrfToken =
                csrfTokenElement?.getAttribute(
                    "content"
                );


            if (!csrfToken) {

                showNotification(
                    "Security token missing. Please refresh the page and try again.",
                    "error"
                );

                return;

            }


            const originalButtonText =
                submitButton.innerHTML;


            submitButton.disabled = true;

            submitButton.innerHTML = `

                <span
                    class="spinner-border spinner-border-sm me-2"
                ></span>

                Submitting Payment...

            `;


            try {

                const response = await fetch(
                    "api/submit-payment.php",
                    {

                        method: "POST",

                        headers: {

                            "Content-Type":
                                "application/json",

                            "X-Requested-With":
                                "XMLHttpRequest",

                            "X-CSRF-Token":
                                csrfToken

                        },

                        body: JSON.stringify({

                            investment_id:
                                paymentForm
                                    .querySelector(
                                        '[name="investment_id"]'
                                    )
                                    .value,

                            transaction_hash:
                                transactionHash

                        })

                    }
                );


                const data =
                    await response.json();


                if (
                    !response.ok ||
                    !data.success
                ) {

                    throw new Error(

                        data.message ||

                        "Unable to submit payment."

                    );

                }


                submitButton.innerHTML = `

                    <i class="bi bi-check-circle me-2"></i>

                    Payment Submitted

                `;


                setTimeout(() => {

                    window.location.href = "invest.php";

                }, 1200);


            } catch (error) {

                console.error(
                    "Payment submission error:",
                    error
                );

                showNotification(

                    error.message,

                    "Something went wrong while submitting your payment."

                );



                submitButton.disabled = false;

                submitButton.innerHTML =
                    originalButtonText;

            }

        }

    );

});