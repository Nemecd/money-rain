/*==================================================
                MONEY RAIN
              INVESTMENT PAGE
==================================================*/

document.addEventListener("DOMContentLoaded", () => {
  const packageButtons = document.querySelectorAll(".select-package-btn");

  const packageModalElement = document.getElementById("packageModal");

  const packageModal = new bootstrap.Modal(packageModalElement);

  const selectedLevel = document.getElementById("selectedLevel");
  const selectedAmount = document.getElementById("selectedAmount");
  const selectedROI = document.getElementById("selectedROI");
  const selectedProfit = document.getElementById("selectedProfit");
  const selectedTotal = document.getElementById("selectedTotal");

  const continueButton = document.getElementById("continueInvestment");

  let selectedPackageLevel = null;

  /*
      ================================================
              SELECT PACKAGE
      ================================================
      */

  packageButtons.forEach((button) => {
    button.addEventListener("click", () => {
      /*
                  The level is only used to identify
                  the selected package.

                  The amount shown here is for UI only.

                  The server will independently validate
                  the actual package amount.
                  */

      selectedPackageLevel = button.dataset.level;

      const amount = Number(button.dataset.amount);
      const roi = Number(button.dataset.roi);
      const profit = Number(button.dataset.profit);
      const total = Number(button.dataset.total);

      selectedLevel.textContent = `Level ${selectedPackageLevel}`;

      selectedAmount.textContent = `${amount.toLocaleString()} USDT`;

      selectedROI.textContent = `${roi}%`;

      selectedProfit.textContent = `${profit.toLocaleString()} USDT`;

      selectedTotal.textContent = `${total.toLocaleString()} USDT`;

      packageModal.show();
    });
  });

  /*
      ================================================
              CONTINUE TO PAYMENT
      ================================================
      */

  continueButton.addEventListener("click", async () => {
    showNotification(
      "Please select an investment package.",

      "warning",
    );

    const originalText = continueButton.innerHTML;

    continueButton.disabled = true;

    continueButton.innerHTML = `

            <span class="spinner-border spinner-border-sm me-2"></span>

            Preparing Investment...

        `;

    try {
      const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

      const response = await fetch("api/create-investment.php", {
        method: "POST",

        headers: {
          "Content-Type": "application/json",

          "X-Requested-With": "XMLHttpRequest",

          "X-CSRF-Token": csrfToken,
        },

        body: JSON.stringify({
          package_level: Number(selectedPackageLevel),
        }),
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || "Unable to create investment.");
      }

      /*
                  The server decides the investment
                  details and returns the investment ID.

                  The browser only receives the ID
                  needed to continue the payment flow.
                  */

      window.location.href = `crypto-payment.php?investment=${encodeURIComponent(data.investment_id)}`;
    } catch (error) {
      console.error("Investment Error:", error);
      showNotification(
        error.message,

        "error",
      );

      continueButton.disabled = false;

      continueButton.innerHTML = originalText;
    }
  });
});

const cancelInvestmentButton = document.getElementById("cancelInvestmentBtn");

if (cancelInvestmentButton) {
  cancelInvestmentButton.addEventListener(
    "click",

    () => {
      showConfirmModal(
        "Cancel Investment?",

        "Are you sure you want to cancel this incomplete investment?",

        async () => {
          await cancelInvestment();
        },
      );
    },
  );
}

async function cancelInvestment() {
  const investmentId = cancelInvestmentButton.dataset.investmentId;

  const csrfToken = document

    .querySelector('meta[name="csrf-token"]')

    ?.getAttribute("content");

  cancelInvestmentButton.disabled = true;

  cancelInvestmentButton.innerHTML = `

        <span

            class="spinner-border spinner-border-sm"

        ></span>

        Cancelling...

    `;

  try {
    const response = await fetch(
      "api/cancel-investment.php",

      {
        method: "POST",

        headers: {
          "Content-Type": "application/json",

          "X-Requested-With": "XMLHttpRequest",

          "X-CSRF-Token": csrfToken,
        },

        body: JSON.stringify({
          investment_id: investmentId,
        }),
      },
    );

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Unable to cancel investment.");
    }

    showNotification(
      data.message,

      "success",
    );

    setTimeout(
      () => {
        window.location.reload();
      },

      1200,
    );
  } catch (error) {
    showNotification(
      error.message,

      "error",
    );

    cancelInvestmentButton.disabled = false;

    cancelInvestmentButton.innerHTML = `

            <i class="bi bi-x-circle"></i>

            Cancel Investment

        `;
  }
}
